<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Forms mail delivery with selectable provider.
 *
 * Providers:
 *  - env   : Use Laravel's configured mailer from .env / config/mail.php
 *  - smtp  : Use SMTP settings stored in Settings (host/port/user/pass/encryption)
 *  - brevo : Use Brevo Transactional Email API (api-key)
 */
class FormMailer
{
    public function sendRaw(
        array $to,
        string $subject,
        string $textBody,
        ?string $fromEmail,
        ?string $fromName,
        ?string $replyTo,
        array $cc = [],
        array $bcc = [],
    ): void
    {
        $provider = strtolower(trim((string) Setting::get('forms_mail_provider', 'env')));

        // Normalise recipients (unique + valid)
        $to = collect($to)
            ->map(fn ($e) => trim((string) $e))
            ->filter(fn ($e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();

        if (count($to) === 0) {
            return;
        }

        // Normalise cc/bcc (unique + valid)
        $cc = collect($cc)
            ->map(fn ($e) => trim((string) $e))
            ->filter(fn ($e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();

        $bcc = collect($bcc)
            ->map(fn ($e) => trim((string) $e))
            ->filter(fn ($e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();

        try {
            if ($provider === 'brevo') {
                $this->sendViaBrevo($to, $cc, $bcc, $subject, $textBody, $fromEmail, $fromName, $replyTo);
                return;
            }

            if ($provider === 'smtp') {
                $didSend = $this->sendViaCustomSmtp($to, $cc, $bcc, $subject, $textBody, $fromEmail, $fromName, $replyTo);
                if ($didSend) return;
                // Fallback to env mailer
            }

            $this->sendViaLaravelMailer($to, $cc, $bcc, $subject, $textBody, $fromEmail, $fromName, $replyTo);
        } catch (\Throwable $e) {
            // Best-effort: never break the user flow if mail fails
            Log::warning('Form mail send failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendViaLaravelMailer(array $to, array $cc, array $bcc, string $subject, string $textBody, ?string $fromEmail, ?string $fromName, ?string $replyTo): void
    {
        Mail::raw($textBody, function ($message) use ($to, $cc, $bcc, $subject, $fromEmail, $fromName, $replyTo) {
            $message->to($to)->subject($subject);

            if (!empty($cc)) {
                $message->cc($cc);
            }

            if (!empty($bcc)) {
                $message->bcc($bcc);
            }

            if (!empty($fromEmail)) {
                $message->from($fromEmail, !empty($fromName) ? $fromName : null);
            }

            if (!empty($replyTo)) {
                $message->replyTo($replyTo);
            }
        });
    }

    /**
     * Returns true if custom SMTP was configured and send attempted.
     * Returns false if SMTP settings are missing and caller should fall back.
     */
    private function sendViaCustomSmtp(array $to, array $cc, array $bcc, string $subject, string $textBody, ?string $fromEmail, ?string $fromName, ?string $replyTo): bool
    {
        $host = trim((string) Setting::get('forms_smtp_host', ''));
        $port = (int) Setting::get('forms_smtp_port', '587');
        $username = trim((string) Setting::get('forms_smtp_username', ''));
        $password = Setting::getSecret('forms_smtp_password', '');
        $encryption = strtolower(trim((string) Setting::get('forms_smtp_encryption', 'tls')));

        if ($host === '' || $port <= 0) {
            return false;
        }

        // Override Laravel mail config for this request.
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => $port,
            'mail.mailers.smtp.username' => $username !== '' ? $username : null,
            'mail.mailers.smtp.password' => $password !== '' ? $password : null,
            'mail.mailers.smtp.encryption' => in_array($encryption, ['tls', 'ssl', 'starttls'], true) ? $encryption : null,
        ]);

        $this->sendViaLaravelMailer($to, $cc, $bcc, $subject, $textBody, $fromEmail, $fromName, $replyTo);
        return true;
    }

    private function sendViaBrevo(array $to, array $cc, array $bcc, string $subject, string $textBody, ?string $fromEmail, ?string $fromName, ?string $replyTo): void
    {
        $apiKey = Setting::getSecret('forms_brevo_api_key', '');

        if ($apiKey === '') {
            // No key configured, fall back to Laravel mailer
            $this->sendViaLaravelMailer($to, $cc, $bcc, $subject, $textBody, $fromEmail, $fromName, $replyTo);
            return;
        }

        $senderEmail = $fromEmail ?: (string) config('mail.from.address', '');
        $senderName = $fromName ?: (string) config('mail.from.name', config('app.name'));

        // Brevo requires sender email.
        if (trim($senderEmail) === '') {
            // If missing, fall back to Laravel mailer.
            $this->sendViaLaravelMailer($to, $cc, $bcc, $subject, $textBody, $fromEmail, $fromName, $replyTo);
            return;
        }

        $payload = [
            'sender' => [
                'email' => $senderEmail,
                'name' => $senderName,
            ],
            'to' => array_map(fn ($email) => ['email' => $email], $to),
            'subject' => $subject,
            'textContent' => $textBody,
        ];

        if (!empty($cc)) {
            $payload['cc'] = array_map(fn ($email) => ['email' => $email], $cc);
        }

        if (!empty($bcc)) {
            $payload['bcc'] = array_map(fn ($email) => ['email' => $email], $bcc);
        }

        if (!empty($replyTo)) {
            $payload['replyTo'] = ['email' => $replyTo];
        }

        $resp = Http::withHeaders([
            'api-key' => $apiKey,
            'accept' => 'application/json',
            'content-type' => 'application/json',
        ])->timeout(10)->post('https://api.brevo.com/v3/smtp/email', $payload);

        if (!$resp->successful()) {
            Log::warning('Brevo send failed', [
                'status' => $resp->status(),
                'body' => $resp->body(),
            ]);
        }
    }
}
