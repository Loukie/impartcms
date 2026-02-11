<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PageAdminController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.index', [
            'pages' => Page::query()->orderBy('id', 'desc')->paginate(20),
        ]);
    }

    public function trash(): View
    {
        return view('admin.pages.trash', [
            'pages' => Page::onlyTrashed()->orderBy('deleted_at', 'desc')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.pages.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $action = (string) $request->input('action', 'draft'); // draft|publish

        if ($action === 'publish') {
            $data['status'] = 'published';
            $data['published_at'] = $data['published_at'] ?? now();
        } else {
            $data['status'] = 'draft';
            unset($data['published_at']);
        }

        // Only set homepage if the form explicitly includes it.
        // (Prevents accidentally clearing homepage when the UI doesn't include this field.)
        if ($request->has('is_homepage')) {
            $data['is_homepage'] = (bool) $request->boolean('is_homepage');
        }
        $data = array_filter($data, fn ($v) => $v !== null);

        $page = Page::create($data);

        // Enforce single homepage if flagged during create.
        if (!empty($data['is_homepage'])) {
            $this->makeHomepage($page);
        }

        $seo = $this->validatedSeo($request);
        $page->seo()->create($seo);

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('status', $page->status === 'published' ? 'Page published ✅' : 'Draft saved ✅');
    }

    public function edit(Page $page): View
    {
        $page->load('seo');

        return view('admin.pages.edit', [
            'page' => $page,
        ]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $data = $this->validated($request, $page->id);

        $action = (string) $request->input('action', '');

        if ($action === 'publish') {
            $data['status'] = 'published';
            $data['published_at'] = $data['published_at'] ?? ($page->published_at ?? now());
        } elseif ($action === 'draft') {
            $data['status'] = 'draft';
            unset($data['published_at']);
        } else {
            $data['status'] = $page->status;
            unset($data['published_at']);
        }

        // Only update homepage flag if the UI explicitly sends it.
        if ($request->has('is_homepage')) {
            $data['is_homepage'] = (bool) $request->boolean('is_homepage');
        }
        $data = array_filter($data, fn ($v) => $v !== null);

        $page->update($data);

        // Enforce single homepage if flagged during update.
        if (!empty($data['is_homepage'])) {
            $this->makeHomepage($page);
        }

        $seo = $this->validatedSeo($request);
        if ($page->seo) {
            $page->seo->update($seo);
        } else {
            $page->seo()->create($seo);
        }

        return back()->with('status', $page->status === 'published' ? 'Page updated ✅' : 'Draft updated ✅');
    }

    /**
     * WordPress-style: Set a specific page as the homepage.
     * - Only published pages can be set as homepage.
     * - Keeps a single homepage (clears the flag on all others, including trashed).
     * - Also stores the selection in settings for future flexibility.
     */
    public function setHomepage(Page $page): RedirectResponse
    {
        if ($page->status !== 'published') {
            return back()->withErrors([
                'homepage_page_id' => 'Only published pages can be set as the homepage.',
            ]);
        }

        $this->makeHomepage($page);

        return back()->with('status', 'Homepage updated ✅');
    }

    private function makeHomepage(Page $page): void
    {
        // Clear existing homepage flags everywhere (including trashed) to keep it single.
        Page::withTrashed()->where('id', '!=', $page->id)->update(['is_homepage' => false]);

        // Ensure selected page is marked as homepage.
        $page->is_homepage = true;
        $page->save();

        // Persist selection in settings (used by '/' resolver).
        Setting::set('homepage_page_id', (string) $page->id);
    }

    /**
     * Move to Trash (soft delete)
     */
    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return redirect()
            ->route('admin.pages.index')
            ->with('status', 'Page moved to trash ✅');
    }

    /**
     * Restore from trash
     */
    public function restore(Page $pageTrash): RedirectResponse
    {
        $pageTrash->restore();

        return redirect()
            ->route('admin.pages.index')
            ->with('status', 'Page restored ✅');
    }

    /**
     * Delete permanently (force delete)
     */
    public function forceDestroy(Page $pageTrash): RedirectResponse
    {
        $pageTrash->forceDelete();

        return redirect()
            ->route('admin.pages.trash')
            ->with('status', 'Page deleted permanently ✅');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9\-\/]+$/i',
                Rule::unique('pages', 'slug')->ignore($ignoreId),
            ],
            'body' => ['nullable', 'string'],
            'status' => ['nullable', 'in:draft,published'],
            'template' => ['nullable', 'string', 'max:100'],
            'is_homepage' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ]);
    }

    private function validatedSeo(Request $request): array
    {
        return $request->validate([
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'canonical_url' => ['nullable', 'string', 'max:500'],
            'robots' => ['nullable', 'string', 'max:255'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:500'],
            'og_image_url' => ['nullable', 'string', 'max:500'],
            'twitter_title' => ['nullable', 'string', 'max:255'],
            'twitter_description' => ['nullable', 'string', 'max:500'],
            'twitter_image_url' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
