<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Support\Ai\AiPageGenerator;

class AiPageAdminController extends Controller
{
    public function create(): View
    {
        return view('admin.pages.ai-create');
    }

    public function store(Request $request, AiPageGenerator $generator): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9\-\/]+$/i'],
            'brief' => ['required', 'string', 'max:8000'],
            'template' => ['nullable', 'string', 'max:100'],
            'style_mode' => ['nullable', Rule::in(['inline', 'classes'])],
            'full_document' => ['nullable', 'boolean'],
            'action' => ['nullable', Rule::in(['draft', 'publish'])],
        ]);

        $title = trim($data['title']);
        $template = trim((string) ($data['template'] ?? ''));
        if ($template === '') {
            $template = 'blank';
        }

        $slug = trim((string) ($data['slug'] ?? ''));
        if ($slug === '') {
            $slug = Str::slug($title);
        }
        $slug = $this->normaliseSlug($slug);
        $slug = $this->ensureUniqueSlug($slug);

        $page = new Page();
        $page->title = $title;
        $page->slug = $slug;
        $page->template = $template;

        $action = (string) ($data['action'] ?? 'draft');
        if ($action === 'publish') {
            $page->status = 'published';
            $page->published_at = now();
        } else {
            $page->status = 'draft';
            $page->published_at = null;
        }

        // Generate HTML (sanitised) and attach.
        try {
            $gen = $generator->generateHtml(
                brief: (string) $data['brief'],
                options: [
                    'title' => $title,
                    'style_mode' => (string) ($data['style_mode'] ?? 'inline'),
                    'full_document' => (bool) ($data['full_document'] ?? false),
                ],
            );
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors([
                    'brief' => 'AI generation failed: ' . $e->getMessage(),
                ]);
        }

        $page->body = (string) ($gen['clean_html'] ?? '');
        $page->save();

        // Create SEO row (keep it simple; user can refine later)
        $page->seo()->create([
            'meta_title' => $title,
            'meta_description' => $this->suggestMetaDescription((string) $data['brief']),
            'robots' => 'index,follow',
        ]);

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('status', 'AI page created ✅');
    }

    private function normaliseSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));

        // Convert spaces to dashes and remove invalid chars (keep / for nested slugs).
        $slug = preg_replace('/\s+/', '-', $slug) ?? $slug;
        $slug = preg_replace('/[^a-z0-9\-\/]/', '', $slug) ?? $slug;
        $slug = preg_replace('#/+#', '/', $slug) ?? $slug;
        $slug = trim($slug, '/');

        return $slug !== '' ? $slug : 'page-' . Str::lower(Str::random(6));
    }

    private function ensureUniqueSlug(string $baseSlug): string
    {
        $slug = $baseSlug;
        $i = 2;
        while (Page::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $i;
            $i++;
        }
        return $slug;
    }

    private function suggestMetaDescription(string $brief): string
    {
        $txt = trim(preg_replace('/\s+/', ' ', $brief) ?? $brief);
        if ($txt === '') {
            return '';
        }

        // Basic clamp ~155 chars.
        if (mb_strlen($txt) > 160) {
            $txt = mb_substr($txt, 0, 157) . '…';
        }

        return $txt;
    }
}
