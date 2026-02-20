<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\Setting;
use App\Support\FormMailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FormSubmissionController extends Controller
{
    public function __construct(
        private readonly FormMailer $mailer
    ) {}

    /**
     * Stores a submission and emails the recipient(s).
     * Spam protection:
     * - honeypot: `website`
     * - route throttling middleware (see routes/web.php)
     */
    public function submit(Request $request, Form $form): RedirectResponse
    {
        abort_unless($form->is_active, 404);

        // Honeypot
        if (trim((string) $request->input('website', '')) !== '') {
            // Pretend success
            return back()->with('success', true);
        }

        $payload = $request->except(['_token', 'website', '_impart_to', '_impart_cc', '_impart_bcc']);

        // Optional computed price (from embed JS)
        $priceZar = $request->input('_impart_price_zar');
        if (is_numeric($priceZar)) {
            $payload['_price_zar'] = (int) $priceZar;
        }

        // Build a nicer display payload (label => value)
        $displayPayload = $this->buildDisplayPayload($form, $payload);

        // Resolve recipients:
        // 1) shortcode to=... (stored in session by shortcode renderer)  — if present
        // 2) form setting: settings.recipients
        // 3) global setting: forms_default_to
        $recipients = $this->resolveRecipients($form, $request);

        // Optional CC/BCC:
        // 1) shortcode cc/bcc via hidden fields
        // 2) global defaults in settings
        $cc = $this->resolveCc($request);
        $bcc = $this->resolveBcc($request);

        // Save submission
        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'payload' => $payload,
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        // Send email (best-effort)
        if (count($recipients) > 0) {
            $subject = 'New form submission: ' . ($form->name ?: $form->slug);

            $fromName = trim((string) Setting::get('forms_from_name', ''));
            $fromEmail = trim((string) Setting::get('forms_from_email', ''));
            $replyTo = trim((string) Setting::get('forms_reply_to', ''));

            $this->mailer->sendRaw(
                to: $recipients,
                subject: $subject,
                textBody: $this->plainTextBody($form, $displayPayload, $submission),
                fromEmail: $fromEmail !== '' ? $fromEmail : null,
                fromName: $fromName !== '' ? $fromName : null,
                replyTo: $replyTo !== '' ? $replyTo : null,
                cc: $cc,
                bcc: $bcc,
            );
        }

        return back()->with('success', true);
    }

    private function plainTextBody(Form $form, array $displayPayload, FormSubmission $submission): string
    {
        $lines = [];
        $lines[] = "🌻 I hope you're well.";
        $lines[] = '';
        $lines[] = 'New submission received for: ' . ($form->name ?: $form->slug);
        $lines[] = 'Submission ID: ' . $submission->id;
        $lines[] = 'Date: ' . now()->format('Y-m-d H:i:s');
        $lines[] = '';
        $lines[] = 'Fields:';

        foreach ($displayPayload as $label => $value) {
            $v = is_scalar($value) ? (string) $value : json_encode($value);
            $lines[] = "- {$label}: {$v}";
        }

        $lines[] = '';
        $lines[] = '—';
        $lines[] = 'Sent via ImpartCMS Forms';

        return implode("\n", $lines);
    }

    private function resolveRecipients(Form $form, Request $request): array
    {
        // Shortcode can override recipients via hidden field.
        $override = trim((string) $request->input('_impart_to', ''));

        $formTo = $form->settings['recipients'] ?? null;
        $defaultTo = Setting::get('forms_default_to', '');

        $raw = (string)($override ?: $formTo ?: $defaultTo);
        $list = collect(explode(',', $raw))
            ->map(fn($e) => trim($e))
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Only keep valid emails
        $out = [];
        foreach ($list as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $out[] = $email;
            }
        }

        return $out;
    }

    private function resolveCc(Request $request): array
    {
        $override = trim((string) $request->input('_impart_cc', ''));
        $defaultCc = (string) Setting::get('forms_default_cc', '');
        $raw = (string) ($override !== '' ? $override : $defaultCc);
        return $this->csvEmails($raw);
    }

    private function resolveBcc(Request $request): array
    {
        $override = trim((string) $request->input('_impart_bcc', ''));
        $defaultBcc = (string) Setting::get('forms_default_bcc', '');
        $raw = (string) ($override !== '' ? $override : $defaultBcc);
        return $this->csvEmails($raw);
    }

    private function csvEmails(string $raw): array
    {
        $list = collect(explode(',', $raw))
            ->map(fn ($e) => trim((string) $e))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $out = [];
        foreach ($list as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $out[] = $email;
            }
        }

        return $out;
    }

    private function buildDisplayPayload(Form $form, array $payload): array
    {
        // Build name => label + options mapping from form schema
        $schema = is_array($form->fields ?? null) ? $form->fields : [];

        // Normalise to map
        $fields = [];
        $isAssoc = array_keys($schema) !== range(0, count($schema) - 1);
        if ($isAssoc) {
            $fields = $schema;
        } else {
            foreach ($schema as $idx => $f) {
                if (!is_array($f)) continue;
                $id = $f['id'] ?? ('f_' . ($idx + 1));
                $fields[$id] = array_merge(['id' => $id], $f);
            }
        }

        // Build lookup by name
        $byName = [];
        foreach ($fields as $f) {
            if (!is_array($f)) continue;
            $name = (string)($f['name'] ?? '');
            if ($name === '') continue;
            $byName[$name] = $f;
        }

        $out = [];
        foreach ($payload as $k => $v) {
            // phone: combine country if present
            if (Str::endsWith($k, '_country')) continue;

            if ($k === '_price_zar') {
                $out['Price (ZAR)'] = 'R' . number_format((int) $v, 0, '.', ' ');
                continue;
            }

            $f = $byName[$k] ?? null;
            $label = $f && !empty($f['label']) ? (string)$f['label'] : (string)$k;
            $type = $f ? strtolower((string)($f['type'] ?? 'text')) : 'text';

            if ($type === 'phone') {
                $country = (string)($payload[$k . '_country'] ?? '');
                $dial = $this->isoToDial($country);
                $prefix = $dial ?: $country;
                $out[$label] = trim($prefix . ' ' . (is_scalar($v) ? (string)$v : ''));
                continue;
            }

            if (in_array($type, ['select','cards'], true)) {
                $out[$label] = $this->mapValueToLabel($f, is_scalar($v) ? (string)$v : '');
                continue;
            }

            if ($type === 'cards_multi') {
                $vals = is_array($v) ? $v : (array)$v;
                $labels = [];
                foreach ($vals as $vv) {
                    $labels[] = $this->mapValueToLabel($f, is_scalar($vv) ? (string)$vv : '');
                }
                $out[$label] = implode(', ', array_filter($labels));
                continue;
            }

            $out[$label] = $v;
        }

        return $out;
    }

    private function mapValueToLabel(?array $field, string $value): string
    {
        if (!$field) return $value;
        $options = is_array($field['options'] ?? null) ? $field['options'] : [];
        foreach ($options as $opt) {
            if (!is_array($opt)) continue;
            if ((string)($opt['value'] ?? '') === $value) {
                $label = (string)($opt['label'] ?? '');
                if ($label === '') return $value;
                // If label and value differ, include both so admin doesn't have to make them identical.
                if ($label !== $value && $value !== '') {
                    return $label . ' (' . $value . ')';
                }
                return $label;
            }
        }
        return $value;
    }

    private function isoToDial(string $iso): string
    {
        $iso = strtoupper(trim($iso));
        $map = [
            'ZA' => '+27',
            'US' => '+1',
            'CA' => '+1',
            'GB' => '+44',
            'AU' => '+61',
            'NZ' => '+64',
            'DE' => '+49',
            'FR' => '+33',
            'NL' => '+31',
            'IE' => '+353',
            'ES' => '+34',
            'IT' => '+39',
            'PT' => '+351',
            'BE' => '+32',
            'CH' => '+41',
            'AT' => '+43',
            'SE' => '+46',
            'NO' => '+47',
            'DK' => '+45',
            'FI' => '+358',
            'PL' => '+48',
            'CZ' => '+420',
            'HU' => '+36',
            'GR' => '+30',
            'TR' => '+90',
            'AE' => '+971',
            'SA' => '+966',
            'IN' => '+91',
            'SG' => '+65',
            'MY' => '+60',
            'TH' => '+66',
            'VN' => '+84',
            'PH' => '+63',
            'ID' => '+62',
            'JP' => '+81',
            'KR' => '+82',
            'CN' => '+86',
            'HK' => '+852',
            'BR' => '+55',
            'MX' => '+52',
            'AR' => '+54',
            'CO' => '+57',
            'CL' => '+56',
            'PE' => '+51',
            'NG' => '+234',
            'KE' => '+254',
            'GH' => '+233',
            'EG' => '+20',
        ];
        return $map[$iso] ?? '';
    }
}
