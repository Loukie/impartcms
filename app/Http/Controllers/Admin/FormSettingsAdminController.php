<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormSettingsAdminController extends Controller
{
    public function edit(): View
    {
        return view('admin.forms.settings', [
            'defaults' => [
                'default_to' => (string) Setting::get('forms_default_to', ''),
                'default_cc' => (string) Setting::get('forms_default_cc', ''),
                'default_bcc' => (string) Setting::get('forms_default_bcc', ''),
                'from_name' => (string) Setting::get('forms_from_name', ''),
                'from_email' => (string) Setting::get('forms_from_email', ''),
                'reply_to' => (string) Setting::get('forms_reply_to', ''),

                // Delivery provider
                'mail_provider' => (string) Setting::get('forms_mail_provider', 'env'), // env|smtp|brevo

                // SMTP override (optional)
                'smtp_host' => (string) Setting::get('forms_smtp_host', ''),
                'smtp_port' => (string) Setting::get('forms_smtp_port', '587'),
                'smtp_username' => (string) Setting::get('forms_smtp_username', ''),
                'smtp_password' => '', // never prefill
                'smtp_encryption' => (string) Setting::get('forms_smtp_encryption', 'tls'),

                // Brevo API (optional)
                'brevo_api_key' => '', // never prefill
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'default_to' => ['nullable', 'string', 'max:255'],
            'default_cc' => ['nullable', 'string', 'max:255'],
            'default_bcc' => ['nullable', 'string', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'from_email' => ['nullable', 'string', 'max:255'],
            'reply_to' => ['nullable', 'string', 'max:255'],

            'mail_provider' => ['nullable', 'string', 'in:env,smtp,brevo'],

            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'string', 'max:10'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_encryption' => ['nullable', 'string', 'in:tls,ssl,starttls,none'],

            'brevo_api_key' => ['nullable', 'string', 'max:255'],
        ]);

        // We store raw strings; validation for CSV emails is handled on send.
        Setting::set('forms_default_to', trim((string)($data['default_to'] ?? '')));
        Setting::set('forms_default_cc', trim((string)($data['default_cc'] ?? '')));
        Setting::set('forms_default_bcc', trim((string)($data['default_bcc'] ?? '')));
        Setting::set('forms_from_name', trim((string)($data['from_name'] ?? '')));
        Setting::set('forms_from_email', trim((string)($data['from_email'] ?? '')));
        Setting::set('forms_reply_to', trim((string)($data['reply_to'] ?? '')));

        Setting::set('forms_mail_provider', trim((string)($data['mail_provider'] ?? 'env')) ?: 'env');

        // SMTP override settings
        Setting::set('forms_smtp_host', trim((string)($data['smtp_host'] ?? '')));
        Setting::set('forms_smtp_port', trim((string)($data['smtp_port'] ?? '587')));
        Setting::set('forms_smtp_username', trim((string)($data['smtp_username'] ?? '')));
        $enc = trim((string)($data['smtp_encryption'] ?? 'tls'));
        Setting::set('forms_smtp_encryption', $enc === 'none' ? '' : $enc);

        // Only update secrets if a value is provided (so you can save other settings without clearing secrets)
        $smtpPass = trim((string)($data['smtp_password'] ?? ''));
        if ($smtpPass !== '') {
            Setting::setSecret('forms_smtp_password', $smtpPass);
        }

        $brevoKey = trim((string)($data['brevo_api_key'] ?? ''));
        if ($brevoKey !== '') {
            Setting::setSecret('forms_brevo_api_key', $brevoKey);
        }

        return back()->with('status', 'Forms settings saved ✅');
    }
}
