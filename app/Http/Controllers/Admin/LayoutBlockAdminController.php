<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LayoutBlock;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LayoutBlockAdminController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', ''); // '', 'enabled', 'disabled'
        $type = (string) $request->query('type', ''); // '', 'header', 'footer'
        $sort = (string) $request->query('sort', 'priority_asc');

        $base = LayoutBlock::query();

        if ($q !== '') {
            $base->where('name', 'like', '%' . $q . '%');
        }

        if (in_array($type, ['header', 'footer'], true)) {
            $base->where('type', $type);
        } else {
            $type = '';
        }

        // Counts (tabs) - respect search + type filter
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
            case 'priority_desc':
                $base->orderByDesc('priority')->orderByDesc('updated_at');
                break;
            case 'updated_desc':
                $base->orderByDesc('updated_at');
                break;
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
            case 'priority_asc':
            default:
                $sort = 'priority_asc';
                $base->orderBy('priority')->orderByDesc('updated_at');
                break;
        }

        $base->orderByDesc('id');

        return view('admin.layout-blocks.index', [
            'blocks' => $base->paginate(20)->withQueryString(),
            'counts' => $counts,
            'currentQuery' => $q,
            'currentStatus' => $status,
            'currentType' => $type,
            'currentSort' => $sort,

            // Options (site-wide enable toggles)
            'headerEnabled' => ((string) (Setting::get('layout_header_enabled', '1') ?? '1')) === '1',
            'footerEnabled' => ((string) (Setting::get('layout_footer_enabled', '1') ?? '1')) === '1',
        ]);
    }

    public function trash(): View
    {
        return view('admin.layout-blocks.trash', [
            'blocks' => LayoutBlock::onlyTrashed()->orderBy('deleted_at', 'desc')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $block = new LayoutBlock();
        $block->type = 'header';
        $block->name = '';
        $block->is_enabled = true;
        $block->target_mode = 'global';
        $block->priority = 100;
        $block->content = '';

        return view('admin.layout-blocks.edit', [
            'block' => $block,
            'pages' => Page::query()->orderBy('title')->get(['id', 'title', 'slug']),
            'selectedPageIds' => [],
            'isCreate' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $block = LayoutBlock::create([
            'type' => $data['type'],
            'name' => $data['name'],
            'is_enabled' => (bool) ($data['is_enabled'] ?? false),
            'target_mode' => $data['target_mode'] ?? 'global',
            'priority' => (int) ($data['priority'] ?? 100),
            'content' => (string) ($data['content'] ?? ''),
        ]);

        $pageIds = array_map('intval', (array) ($data['page_ids'] ?? []));
        $block->pages()->sync($pageIds);

        LayoutBlock::flushCache();

        return redirect()->route('admin.layout-blocks.edit', $block)->with('status', 'Saved ✅');
    }

    public function edit(LayoutBlock $layoutBlock): View
    {
        $layoutBlock->load('pages:id');

        return view('admin.layout-blocks.edit', [
            'block' => $layoutBlock,
            'pages' => Page::query()->orderBy('title')->get(['id', 'title', 'slug']),
            'selectedPageIds' => $layoutBlock->pages->pluck('id')->map(fn ($v) => (int) $v)->values()->all(),
            'isCreate' => false,
        ]);
    }

    public function update(Request $request, LayoutBlock $layoutBlock): RedirectResponse
    {
        $data = $this->validated($request);

        $layoutBlock->update([
            'type' => $data['type'],
            'name' => $data['name'],
            'is_enabled' => (bool) ($data['is_enabled'] ?? false),
            'target_mode' => $data['target_mode'] ?? 'global',
            'priority' => (int) ($data['priority'] ?? 100),
            'content' => (string) ($data['content'] ?? ''),
        ]);

        $pageIds = array_map('intval', (array) ($data['page_ids'] ?? []));
        $layoutBlock->pages()->sync($pageIds);

        LayoutBlock::flushCache();

        return back()->with('status', 'Updated ✅');
    }

    public function destroy(LayoutBlock $layoutBlock): RedirectResponse
    {
        $layoutBlock->delete();

        return redirect()->route('admin.layout-blocks.index')->with('status', 'Moved to trash ✅');
    }

    public function restore(LayoutBlock $layoutBlockTrash): RedirectResponse
    {
        $layoutBlockTrash->restore();

        return redirect()->route('admin.layout-blocks.index')->with('status', 'Restored ✅');
    }

    public function forceDestroy(LayoutBlock $layoutBlockTrash): RedirectResponse
    {
        $layoutBlockTrash->pages()->detach();
        $layoutBlockTrash->forceDelete();

        LayoutBlock::flushCache();

        return redirect()->route('admin.layout-blocks.trash')->with('status', 'Deleted permanently ✅');
    }

    /**
     * Save site-wide toggles.
     */
    public function updateOptions(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'layout_header_enabled' => ['nullable', 'boolean'],
            'layout_footer_enabled' => ['nullable', 'boolean'],
        ]);

        Setting::set('layout_header_enabled', ((bool) ($data['layout_header_enabled'] ?? false)) ? '1' : '0');
        Setting::set('layout_footer_enabled', ((bool) ($data['layout_footer_enabled'] ?? false)) ? '1' : '0');

        return back()->with('status', 'Options updated ✅');
    }

    private function validated(Request $request): array
    {
        // If migrations haven't run yet, keep this controller safe.
        if (!Schema::hasTable('layout_blocks')) {
            abort(500, 'Layout blocks table missing. Run migrations.');
        }

        return $request->validate([
            'type' => ['required', 'string', Rule::in(['header', 'footer'])],
            'name' => ['required', 'string', 'max:120'],
            'is_enabled' => ['nullable', 'boolean'],
            'target_mode' => ['required', 'string', Rule::in(['global', 'only', 'except'])],
            'priority' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'content' => ['nullable', 'string'],
            'page_ids' => ['nullable', 'array'],
            'page_ids.*' => ['integer', 'exists:pages,id'],
        ]);
    }
}
