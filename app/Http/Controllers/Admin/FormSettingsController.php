<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormSubmission;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

class FormSettingsController extends Controller
{
    public function edit()
    {
        $recent = FormSubmission::query()
            ->orderByDesc('created_at')
            ->limit(25)
            ->get();

        return view('admin.forms.settings', [
            'formsDefaultTo' => (string) Setting::get('forms_default_to', ''),
            'mailMode' => (string) Setting::get('forms_mail_mode', 'inherit'),
            'fromName' => (string) Setting::get('forms_from_name', ''),
            'fromEmail' => (string) Setting::get('forms_from_email', ''),
            'smtpHost' => (string) Setting::get('forms_smtp_host', ''),
            'smtpPort' => (string) Setting::get('forms_smtp_port', '587'),
            'smtpEncryption' => (string) Setting::get('forms_smtp_encryption', 'tls'),
            'smtpUsername' => (string) Setting::get('forms_smtp_username', ''),
            'smtpPasswordSet' => (string) Setting::get('forms_smtp_password', '') !== '',
            'recent' => $recent,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'forms_default_to' => ['nullable', 'string', 'max:1000'],
            'forms_mail_mode' => ['required', 'in:inherit,smtp,log'],

            'forms_from_name' => ['nullable', 'string', 'max:120'],
            'forms_from_email' => ['nullable', 'email', 'max:255'],

            'forms_smtp_host' => ['nullable', 'string', 'max:255'],
            'forms_smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'forms_smtp_encryption' => ['nullable', 'in:tls,ssl,none'],
            'forms_smtp_username' => ['nullable', 'string', 'max:255'],
            'forms_smtp_password' => ['nullable', 'string', 'max:255'],
            'forms_smtp_password_clear' => ['nullable', 'boolean'],
        ]);

        Setting::set('forms_default_to', (string) ($validated['forms_default_to'] ?? ''));
        Setting::set('forms_mail_mode', (string) $validated['forms_mail_mode']);

        Setting::set('forms_from_name', (string) ($validated['forms_from_name'] ?? ''));
        Setting::set('forms_from_email', (string) ($validated['forms_from_email'] ?? ''));

        Setting::set('forms_smtp_host', (string) ($validated['forms_smtp_host'] ?? ''));
        Setting::set('forms_smtp_port', (string) ((int) ($validated['forms_smtp_port'] ?? 587)));
        $enc = (string) ($validated['forms_smtp_encryption'] ?? 'tls');
        if ($enc === 'none') $enc = '';
        Setting::set('forms_smtp_encryption', $enc);
        Setting::set('forms_smtp_username', (string) ($validated['forms_smtp_username'] ?? ''));

        if (!empty($validated['forms_smtp_password_clear'])) {
            Setting::set('forms_smtp_password', '');
        } elseif (!empty($validated['forms_smtp_password'])) {
            Setting::set('forms_smtp_password', Crypt::encryptString((string) $validated['forms_smtp_password']));
        }

        return back()->with('status', 'Forms settings updated ✅');
    }

    public function sendTestEmail(Request $request)
    {
        $validated = $request->validate([
            'to' => ['required', 'email', 'max:255'],
        ]);

        $mode = strtolower((string) Setting::get('forms_mail_mode', 'inherit'));

        try {
            if ($mode === 'log') {
                return back()->with('status', 'Mail mode is LOG — no email sent (this is expected).');
            }

            if ($mode === 'smtp') {
                // Configure runtime mailer (same structure used in FormSubmissionController)
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

                $mailer = Mail::mailer('forms_smtp');
            } else {
                $mailer = Mail::mailer();
            }

            $fromName = (string) Setting::get('forms_from_name', Setting::get('site_name', config('app.name')));
            $fromEmail = (string) Setting::get('forms_from_email', config('mail.from.address'));

            $mailer->raw('ImpartCMS test email (Forms settings). ✅', function ($message) use ($validated, $fromName, $fromEmail) {
                $message->to($validated['to'])->subject('ImpartCMS Forms — Test email');
                if ($fromEmail) {
                    $message->from($fromEmail, $fromName ?: null);
                }
            });

            return back()->with('status', 'Test email sent ✅');
        } catch (\Throwable $e) {
            return back()->withErrors([
                'to' => 'Test email failed: ' . $e->getMessage(),
            ]);
        }
    }
}
