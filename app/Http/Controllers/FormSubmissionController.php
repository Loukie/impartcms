<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormRecipientRule;
use App\Models\FormSubmission;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\Setting;

class FormSubmissionController extends Controller
{
    public function submit(Request $request, Form $form): RedirectResponse
    {
        if (!$form->is_active) {
            abort(404);
        }

        // Honeypot (simple, effective spam protection)
        $honeypot = (string) $request->input('__hp', '');
        if (trim($honeypot) !== '') {
            $this->storeSubmission(
                request: $request,
                form: $form,
                page: $this->resolvePage($request),
                data: [],
                recipients: [],
                mailStatus: 'skipped',
                spamReason: 'honeypot'
            );
            return back()->with('status', 'Thanks — we received your message ✅');
        }

        // Extra rate limit (per form + IP) to complement route throttle.
        $rateKey = 'forms:' . $form->id . ':ip:' . ($request->ip() ?? 'unknown');
        if (RateLimiter::tooManyAttempts($rateKey, 8)) {
            $this->storeSubmission(
                request: $request,
                form: $form,
                page: $this->resolvePage($request),
                data: [],
                recipients: [],
                mailStatus: 'skipped',
                spamReason: 'rate_limit'
            );
            return back()->with('status', 'Thanks — we received your message ✅');
        }
        RateLimiter::hit($rateKey, 60); // 8 per minute

        $data = $request->validate($this->rulesFromForm($form));

        $page = $this->resolvePage($request);

        $recipients = $this->resolveRecipients(
            form: $form,
            page: $page,
            userId: auth()->id(),
            overrideTo: $request->input('_override_to')
        );

        $submission = $this->storeSubmission(
            request: $request,
            form: $form,
            page: $page,
            data: $data,
            recipients: $recipients,
            mailStatus: 'pending',
            spamReason: null
        );

        // If we have no recipients, we still store the submission but mark as skipped.
        if (empty($recipients)) {
            $submission->update([
                'mail_status' => 'skipped',
                'spam_reason' => 'no_recipients',
            ]);
            return back()->with('status', 'Thanks — we received your message ✅');
        }

        // Sending strategy:
        // - inherit: use app mailer
        // - smtp: use Forms SMTP override
        // - log: do not send (useful for local testing)
        $mode = strtolower((string) Setting::get('forms_mail_mode', 'inherit'));
        if (!in_array($mode, ['inherit', 'smtp', 'log'], true)) {
            $mode = 'inherit';
        }

        if ($mode === 'log') {
            $submission->update([
                'mail_status' => 'skipped',
                'spam_reason' => 'log_mode',
            ]);
            return back()->with('status', 'Thanks — we received your message ✅');
        }

        try {
            $mailer = $mode === 'smtp'
                ? $this->formsSmtpMailer()
                : Mail::mailer();

            $fromName = (string) Setting::get('forms_from_name', Setting::get('site_name', config('app.name')));
            $fromEmail = (string) Setting::get('forms_from_email', config('mail.from.address'));

            $body = "New form submission: {$form->name}\n";
            if ($page) {
                $body .= "Page: {$page->title} ({$page->slug})\n";
            }
            $body .= "\n" . json_encode($data, JSON_PRETTY_PRINT);

            $mailer->raw($body, function ($message) use ($recipients, $fromName, $fromEmail, $form) {
                $message->to($recipients)
                    ->subject('New form submission: ' . $form->name);

                if ($fromEmail) {
                    $message->from($fromEmail, $fromName ?: null);
                }
            });

            $submission->update([
                'mail_status' => 'sent',
                'mail_sent_at' => now(),
                'mail_error' => null,
            ]);
        } catch (\Throwable $e) {
            $submission->update([
                'mail_status' => 'failed',
                'mail_error' => substr($e->getMessage(), 0, 5000),
            ]);
        }

        return back()->with('status', 'Thanks — we received your message ✅');
    }

    private function resolvePage(Request $request): ?Page
    {
        $pageId = $request->input('_page_id');
        return $pageId ? Page::query()->find($pageId) : null;
    }

    private function storeSubmission(
        Request $request,
        Form $form,
        ?Page $page,
        array $data,
        array $recipients,
        string $mailStatus,
        ?string $spamReason,
    ): FormSubmission {
        return FormSubmission::create([
            'form_id' => $form->id,
            'page_id' => $page?->id,
            'user_id' => auth()->id(),
            'payload' => $data,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'to_email' => !empty($recipients) ? implode(', ', $recipients) : null,
            'mail_status' => $mailStatus,
            'spam_reason' => $spamReason,
            'created_at' => now(),
        ]);
    }

    private function formsSmtpMailer()
    {
        $host = (string) Setting::get('forms_smtp_host', '');
        $port = (int) (Setting::get('forms_smtp_port', '587') ?? 587);
        $encryption = (string) Setting::get('forms_smtp_encryption', 'tls');
        $username = (string) Setting::get('forms_smtp_username', '');
        $passwordEnc = (string) Setting::get('forms_smtp_password', '');
        $password = '';
        if ($passwordEnc !== '') {
            try {
                $password = Crypt::decryptString($passwordEnc);
            } catch (\Throwable $e) {
                $password = '';
            }
        }

        // Configure a dedicated mailer at runtime
        config()->set('mail.mailers.forms_smtp', [
            'transport' => 'smtp',
            'host' => $host,
            'port' => $port,
            'encryption' => $encryption !== '' ? $encryption : null,
            'username' => $username !== '' ? $username : null,
            'password' => $password !== '' ? $password : null,
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
        ]);

        return Mail::mailer('forms_smtp');
    }

    private function rulesFromForm(Form $form): array
    {
        $rules = [];
        foreach (($form->fields ?? []) as $field) {
            $name = $field['name'] ?? null;
            if (!$name) {
                continue;
            }

            $required = !empty($field['required']);
            $type = $field['type'] ?? 'text';

            $r = [];
            $r[] = $required ? 'required' : 'nullable';

            if ($type === 'email') {
                $r[] = 'email';
                $r[] = 'max:255';
            } elseif ($type === 'textarea') {
                $r[] = 'string';
                $r[] = 'max:5000';
            } elseif ($type === 'select' || $type === 'cards') {
                $r[] = 'string';
                $r[] = 'max:255';
            } elseif ($type === 'cards_multi') {
                $r[] = 'array';
                $r[] = 'max:50';
            } else {
                $r[] = 'string';
                $r[] = 'max:255';
            }

            $rules[$name] = $r;
        }

        return $rules;
    }

    /**
     * Recipient precedence:
     * 1) overrideTo (shortcode)
     * 2) rule matching (form + page + user)
     * 3) rule matching (form + page)
     * 4) rule matching (form + user)
     * 5) form.settings.default_recipients
     *
     * @return array<int, string>
     */
    private function resolveRecipients(Form $form, ?Page $page, ?int $userId, ?string $overrideTo): array
    {
        if ($overrideTo) {
            return $this->splitEmails($overrideTo);
        }

        $q = FormRecipientRule::query()->where('form_id', $form->id);

        $rule = $q->clone()
            ->when($page, fn($qq) => $qq->where('page_id', $page->id))
            ->when($userId, fn($qq) => $qq->where('user_id', $userId))
            ->first();

        if (!$rule && $page) {
            $rule = $q->clone()->where('page_id', $page->id)->whereNull('user_id')->first();
        }
        if (!$rule && $userId) {
            $rule = $q->clone()->where('user_id', $userId)->whereNull('page_id')->first();
        }

        if ($rule && is_array($rule->recipients)) {
            return array_values(array_filter(array_map('trim', $rule->recipients)));
        }

        $defaults = $form->settings['default_recipients'] ?? [];
        if (is_array($defaults)) {
            return array_values(array_filter(array_map('trim', $defaults)));
        }

        // Global fallback: Forms settings default recipient
        $global = (string) Setting::get('forms_default_to', '');
        if (trim($global) !== '') {
            return $this->splitEmails($global);
        }

        return [];
    }

    private function splitEmails(string $csv): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\s*,\s*/', $csv) ?: [])));
    }
}
