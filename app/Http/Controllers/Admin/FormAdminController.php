<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Form;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FormAdminController extends Controller
{
    public function index()
    {
        $forms = Form::query()
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('admin.forms.index', [
            'forms' => $forms,
        ]);
    }

    public function create()
    {
        return view('admin.forms.edit', [
            'form' => new Form(['is_active' => true]),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request, creating: true);

        $form = new Form();
        $form->name = $validated['name'];
        $form->slug = $validated['slug'];
        $form->is_active = (bool) ($validated['is_active'] ?? false);
        $form->fields = $validated['fields'];
        $form->settings = $validated['settings'];
        $form->save();

        return redirect()->route('admin.forms.edit', $form)->with('status', 'Form created ✅');
    }

    public function edit(Form $form)
    {
        return view('admin.forms.edit', [
            'form' => $form,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Form $form)
    {
        $validated = $this->validatePayload($request, creating: false, form: $form);

        $form->name = $validated['name'];
        $form->slug = $validated['slug'];
        $form->is_active = (bool) ($validated['is_active'] ?? false);
        $form->fields = $validated['fields'];
        $form->settings = $validated['settings'];
        $form->save();

        return back()->with('status', 'Form updated ✅');
    }

    public function destroy(Form $form)
    {
        $form->delete();
        return redirect()->route('admin.forms.index')->with('status', 'Form deleted.');
    }

    private function validatePayload(Request $request, bool $creating, ?Form $form = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                $creating
                    ? Rule::unique('forms', 'slug')
                    : Rule::unique('forms', 'slug')->ignore($form?->id),
            ],
            'is_active' => ['nullable', 'boolean'],

            // JSON payloads from the builder UI
            'fields_json' => ['nullable', 'string'],
            'settings_json' => ['nullable', 'string'],
        ]);

        $fields = [];
        if (!empty($validated['fields_json'])) {
            $decoded = json_decode($validated['fields_json'], true);
            if (!is_array($decoded)) {
                throw ValidationException::withMessages([
                    'fields_json' => 'Fields JSON is invalid.',
                ]);
            }
            $fields = $this->normaliseFields($decoded);
        }

        $settings = [];
        if (!empty($validated['settings_json'])) {
            $decoded = json_decode($validated['settings_json'], true);
            if (!is_array($decoded)) {
                throw ValidationException::withMessages([
                    'settings_json' => 'Settings JSON is invalid.',
                ]);
            }
            $settings = $this->normaliseSettings($decoded);
        }

        return [
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'fields' => $fields,
            'settings' => $settings,
        ];
    }

    private function normaliseFields(array $fields): array
    {
        $out = [];
        foreach ($fields as $f) {
            if (!is_array($f)) continue;

            $type = (string) ($f['type'] ?? 'text');
            $name = isset($f['name']) ? (string) $f['name'] : null;
            $label = isset($f['label']) ? (string) $f['label'] : ($name ?: '');

            $row = [
                'type' => $type,
                'label' => $label,
            ];

            if ($name) {
                $row['name'] = $name;
            }

            if (!empty($f['required'])) {
                $row['required'] = true;
            }

            foreach (['placeholder', 'help', 'html'] as $k) {
                if (isset($f[$k]) && $f[$k] !== '') {
                    $row[$k] = (string) $f[$k];
                }
            }

            // Options (select/cards)
            if (in_array($type, ['select', 'cards', 'cards_multi'], true)) {
                $opts = [];
                $raw = $f['options'] ?? [];
                if (is_array($raw)) {
                    foreach ($raw as $opt) {
                        if (is_string($opt)) {
                            $opts[] = ['label' => $opt, 'value' => $opt];
                        } elseif (is_array($opt)) {
                            $label = (string) ($opt['label'] ?? $opt['value'] ?? '');
                            $value = (string) ($opt['value'] ?? $label);
                            if ($label === '' && $value === '') continue;
                            $o = ['label' => $label, 'value' => $value];
                            if (!empty($opt['description'])) $o['description'] = (string) $opt['description'];
                            if (!empty($opt['media_id'])) $o['media_id'] = (int) $opt['media_id'];
                            $opts[] = $o;
                        }
                    }
                }
                $row['options'] = $opts;
            }

            $out[] = $row;
        }

        return $out;
    }

    private function normaliseSettings(array $settings): array
    {
        $out = [];

        if (!empty($settings['description'])) {
            $out['description'] = (string) $settings['description'];
        }

        // Default recipients stored as array
        $def = $settings['default_recipients'] ?? [];
        if (is_string($def)) {
            $def = array_filter(array_map('trim', preg_split('/\s*,\s*/', $def) ?: []));
        }
        if (is_array($def)) {
            $out['default_recipients'] = array_values(array_filter(array_map('trim', $def)));
        }

        // UI preferences (template + columns)
        $out['ui'] = [
            'template' => (string) ($settings['ui']['template'] ?? $settings['ui_template'] ?? 'normal'),
            'columns' => (int) ($settings['ui']['columns'] ?? $settings['ui_columns'] ?? 1),
        ];
        if ($out['ui']['columns'] < 1 || $out['ui']['columns'] > 4) $out['ui']['columns'] = 1;

        return $out;
    }
}
