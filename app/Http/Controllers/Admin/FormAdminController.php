<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Form;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormAdminController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', ''); // '', 'active', 'inactive'
        $sort = (string) $request->query('sort', 'updated_desc');

        $base = Form::query()->withCount('submissions'); // SoftDeletes excluded by default.

        if ($q !== '') {
            $base->where(function ($query) use ($q) {
                $query->where('name', 'like', '%' . $q . '%')
                    ->orWhere('slug', 'like', '%' . $q . '%');
            });
        }

        // Counts for tabs (reflect current search).
        $countsBase = clone $base;
        $counts = [
            'all' => (clone $countsBase)->count(),
            'active' => (clone $countsBase)->where('is_active', true)->count(),
            'inactive' => (clone $countsBase)->where('is_active', false)->count(),
        ];

        if ($status === 'active') {
            $base->where('is_active', true);
        } elseif ($status === 'inactive') {
            $base->where('is_active', false);
        } else {
            $status = '';
        }

        switch ($sort) {
            case 'updated_asc':
                $base->orderBy('updated_at');
                break;
            case 'created_desc':
                $base->orderByDesc('created_at');
                break;
            case 'created_asc':
                $base->orderBy('created_at');
                break;
            case 'name_desc':
                $base->orderByDesc('name');
                break;
            case 'name_asc':
                $base->orderBy('name');
                break;
            case 'updated_desc':
            default:
                $sort = 'updated_desc';
                $base->orderByDesc('updated_at');
                break;
        }
        $base->orderByDesc('id');

        return view('admin.forms.index', [
            'forms' => $base->paginate(20)->withQueryString(),
            'counts' => $counts,
            'currentQuery' => $q,
            'currentStatus' => $status,
            'currentSort' => $sort,
        ]);
    }

    public function trash(): View
    {
        return view('admin.forms.trash', [
            'forms' => Form::onlyTrashed()->withCount('submissions')->orderBy('deleted_at', 'desc')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $form = new Form([
            'name' => '',
            'slug' => '',
            'is_active' => true,
            'fields' => [],
            'settings' => [
                'layout' => [
                    [
                        'type' => 'row',
                        'id' => 'r_default',
                        'columns' => 1,
                        'cols' => [[]],
                    ]
                ],
                'pricing_enabled' => false,
                'logic_enabled' => false,
            ],
        ]);

        return view('admin.forms.edit', [
            'form' => $form,
            'isNew' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:forms,slug'],
            'is_active' => ['nullable'],
            'fields_json' => ['nullable', 'string'],
            'settings_json' => ['nullable', 'string'],
        ]);

        $fields = $this->decodeJson($data['fields_json'] ?? '{}');
        $settings = $this->decodeJson($data['settings_json'] ?? '{}');

        $form = Form::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'is_active' => (bool) ($data['is_active'] ?? false),
            'fields' => is_array($fields) ? $fields : [],
            'settings' => is_array($settings) ? $settings : [],
        ]);

        return redirect()->route('admin.forms.edit', $form)->with('status', 'Form created ✅');
    }

    public function edit(Form $form): View
    {
        return view('admin.forms.edit', [
            'form' => $form,
            'isNew' => false,
        ]);
    }

    public function update(Request $request, Form $form): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:forms,slug,' . $form->id],
            'is_active' => ['nullable'],
            'fields_json' => ['nullable', 'string'],
            'settings_json' => ['nullable', 'string'],
        ]);

        $fields = $this->decodeJson($data['fields_json'] ?? json_encode($form->fields ?? []));
        $settings = $this->decodeJson($data['settings_json'] ?? json_encode($form->settings ?? []));

        $form->update([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'is_active' => (bool) ($data['is_active'] ?? false),
            'fields' => is_array($fields) ? $fields : [],
            'settings' => is_array($settings) ? $settings : [],
        ]);

        return back()->with('status', 'Saved ✅');
    }

    public function destroy(Form $form): RedirectResponse
    {
        $form->delete();

        return redirect()
            ->route('admin.forms.index')
            ->with('status', 'Form moved to trash ✅');
    }

    /**
     * Restore from trash
     */
    public function restore(Form $formTrash): RedirectResponse
    {
        $formTrash->restore();

        return redirect()
            ->route('admin.forms.index')
            ->with('status', 'Form restored ✅');
    }

    /**
     * Delete permanently (force delete)
     */
    public function forceDestroy(Form $formTrash): RedirectResponse
    {
        $formTrash->forceDelete();

        return redirect()
            ->route('admin.forms.trash')
            ->with('status', 'Form deleted permanently ✅');
    }

    private function decodeJson(string $raw): mixed
    {
        $raw = trim($raw);
        if ($raw === '') return [];

        try {
            return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return [];
        }
    }
}
