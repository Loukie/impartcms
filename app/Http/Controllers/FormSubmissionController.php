<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormRecipientRule;
use App\Models\FormSubmission;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class FormSubmissionController extends Controller
{
    public function submit(Request $request, Form $form): RedirectResponse
    {
        if (!$form->is_active) {
            abort(404);
        }

        $data = $request->validate($this->rulesFromForm($form));

        $pageId = $request->input('_page_id');
        $page = $pageId ? Page::query()->find($pageId) : null;

        $recipients = $this->resolveRecipients(
            form: $form,
            page: $page,
            userId: auth()->id(),
            overrideTo: $request->input('_override_to')
        );

        FormSubmission::create([
            'form_id' => $form->id,
            'page_id' => $page?->id,
            'user_id' => auth()->id(),
            'payload' => $data,
            'ip' => $request->ip(),
            'user_agent' => substr((string)$request->userAgent(), 0, 1000),
            'created_at' => now(),
        ]);

        if (!empty($recipients)) {
            Mail::raw(
                "New form submission: {$form->name}\n\n" . json_encode($data, JSON_PRETTY_PRINT),
                function ($message) use ($recipients) {
                    $message->to($recipients)->subject('New form submission');
                }
            );
        }

        return back()->with('status', 'Thanks — we received your message ✅');
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

        return [];
    }

    private function splitEmails(string $csv): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\s*,\s*/', $csv) ?: [])));
    }
}
