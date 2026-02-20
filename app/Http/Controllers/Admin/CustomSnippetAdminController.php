<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomSnippet;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomSnippetAdminController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $type = (string) $request->query('type', ''); // '', 'css', 'script'
        $status = (string) $request->query('status', ''); // '', 'enabled', 'disabled'
        $sort = (string) $request->query('sort', 'updated_desc');

        $base = CustomSnippet::query();

        if ($q !== '') {
            $base->where('name', 'like', '%' . $q . '%');
        }

        if (in_array($type, ['css', 'script'], true)) {
            $base->where('type', $type);
        } else {
            $type = '';
        }

        $countsBase = clone $base;
        $counts = [
            'all' => (clone $countsBase)->count(),
            'enabled' => (clone $countsBase)->where('is_enabled', true)->count(),
            'disabled' => (clone $countsBase)->where('is_enabled', false)->count(),
        ];

        if ($status === 'enabled') {
            $base->where('is_enabled', true);
        } elseif ($status === 'disabled') {
            $base->where('is_enabled', false);
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
            case 'name_asc':
                $base->orderBy('name');
                break;
            case 'name_desc':
                $base->orderByDesc('name');
                break;
            case 'updated_desc':
            default:
                $sort = 'updated_desc';
                $base->orderByDesc('updated_at');
                break;
        }
        $base->orderByDesc('id');

        return view('admin.snippets.index', [
            'snippets' => $base->paginate(20)->withQueryString(),
            'counts' => $counts,
            'currentQuery' => $q,
            'currentType' => $type,
            'currentStatus' => $status,
            'currentSort' => $sort,
        ]);
    }

    public function trash(): View
    {
        return view('admin.snippets.trash', [
            'snippets' => CustomSnippet::onlyTrashed()->orderBy('deleted_at', 'desc')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $snippet = new CustomSnippet([
            'type' => 'script',
            'name' => '',
            'position' => 'head',
            'is_enabled' => true,
            'target_mode' => 'global',
            'content' => '',
        ]);

        return view('admin.snippets.edit', [
            'snippet' => $snippet,
            'isNew' => true,
            'pages' => Page::query()->orderBy('title')->get(['id', 'title']),
            'selectedPageIds' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $snippet = CustomSnippet::create($data);

        $pageIds = $this->validatedPageIds($request);
        $snippet->pages()->sync($pageIds);
        CustomSnippet::flushCache();

        return redirect()->route('admin.snippets.edit', $snippet)->with('status', 'Saved ✅');
    }

    public function edit(CustomSnippet $snippet): View
    {
        $snippet->load('pages:id');

        return view('admin.snippets.edit', [
            'snippet' => $snippet,
            'isNew' => false,
            'pages' => Page::query()->orderBy('title')->get(['id', 'title']),
            'selectedPageIds' => $snippet->pages->pluck('id')->map(fn ($v) => (int) $v)->values()->all(),
        ]);
    }

    public function update(Request $request, CustomSnippet $snippet): RedirectResponse
    {
        $data = $this->validated($request);

        $snippet->update($data);

        $pageIds = $this->validatedPageIds($request);
        $snippet->pages()->sync($pageIds);
        CustomSnippet::flushCache();

        return back()->with('status', 'Saved ✅');
    }

    public function destroy(CustomSnippet $snippet): RedirectResponse
    {
        $snippet->delete();
        CustomSnippet::flushCache();

        return redirect()->route('admin.snippets.index')->with('status', 'Moved to trash ✅');
    }

    public function restore(CustomSnippet $snippetTrash): RedirectResponse
    {
        $snippetTrash->restore();
        CustomSnippet::flushCache();

        return redirect()->route('admin.snippets.index')->with('status', 'Restored ✅');
    }

    public function forceDestroy(CustomSnippet $snippetTrash): RedirectResponse
    {
        $snippetTrash->forceDelete();
        CustomSnippet::flushCache();

        return redirect()->route('admin.snippets.trash')->with('status', 'Deleted permanently ✅');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['css', 'script'])],
            'name' => ['required', 'string', 'max:255'],
            'is_enabled' => ['nullable'],
            'position' => ['nullable', Rule::in(['head', 'body', 'footer'])],
            'target_mode' => ['nullable', Rule::in(['global', 'only', 'except'])],
            'content' => ['nullable', 'string'],
        ]);

        $data['is_enabled'] = (bool) ($data['is_enabled'] ?? false);

        $data['position'] = strtolower((string) ($data['position'] ?? 'head'));
        $data['target_mode'] = strtolower((string) ($data['target_mode'] ?? 'global'));

        if (($data['type'] ?? '') === 'css') {
            $data['position'] = 'head';
        }

        return $data;
    }

    /**
     * @return array<int>
     */
    private function validatedPageIds(Request $request): array
    {
        $mode = strtolower((string) $request->input('target_mode', 'global'));
        if ($mode === 'global') {
            return [];
        }

        $raw = $request->input('page_ids', []);
        if (!is_array($raw)) {
            return [];
        }

        $ids = array_values(array_filter(array_map('intval', $raw), fn ($v) => $v > 0));
        if (empty($ids)) {
            return [];
        }

        return Page::query()->whereIn('id', $ids)->pluck('id')->map(fn ($v) => (int) $v)->values()->all();
    }
}
