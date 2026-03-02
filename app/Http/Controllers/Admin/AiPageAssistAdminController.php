<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageRevision;
use App\Support\Ai\HtmlSanitiser;
use App\Support\Ai\LlmClientInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class AiPageAssistAdminController extends Controller
{
    public function __construct(
        private readonly LlmClientInterface $llm,
        private readonly HtmlSanitiser $sanitiser,
    ) {}

    /**
     * AJAX page search for the AI popup.
     */
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $query = Page::query()
            ->select(['id', 'title', 'slug', 'status'])
            ->orderByDesc('updated_at');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', '%' . $q . '%')
                    ->orWhere('slug', 'like', '%' . $q . '%');
            });
        }

        $pages = $query->limit(20)->get()->map(fn ($p) => [
            'id' => $p->id,
            'title' => $p->title,
            'slug' => $p->slug,
            'status' => $p->status,
        ])->values();

        return response()->json(['pages' => $pages]);
    }

    /**
     * Apply AI changes to a specific page.
     *
     * - Default behaviour is safe: saves as draft.
     * - Always sanitises HTML.
     * - Stores a revision (best-effort).
     */
    public function assist(Request $request): JsonResponse
    {
        $data = $request->validate([
            'page_id' => ['required', 'integer', 'exists:pages,id'],
            'instruction' => ['required', 'string', 'max:4000'],
            'mode' => ['nullable', 'in:tweak,rewrite'],
            'save_as' => ['nullable', 'in:draft,keep'],
        ]);

        $page = Page::query()->whereKey((int) $data['page_id'])->firstOrFail();
        $mode = (string) ($data['mode'] ?? 'tweak');
        $saveAs = (string) ($data['save_as'] ?? 'draft');

        $currentHtml = (string) ($page->body ?? '');
        $instruction = trim((string) $data['instruction']);

        [$input, $instructions] = $this->buildPrompt(
            title: (string) $page->title,
            slug: (string) $page->slug,
            currentHtml: $currentHtml,
            instruction: $instruction,
            mode: $mode,
        );

        $res = $this->llm->generateText($input, $instructions);
        $raw = trim((string) ($res['output_text'] ?? ($res['text'] ?? '')));
        if ($raw === '') {
            return response()->json(['ok' => false, 'error' => 'AI returned no output.'], 422);
        }

        $clean = trim($this->sanitiser->clean($raw));
        if ($clean === '') {
            return response()->json(['ok' => false, 'error' => 'AI output was empty after sanitisation.'], 422);
        }

        // Save revision (best-effort; do not fail the whole request if revisions are not set up).
        try {
            if (class_exists(PageRevision::class)) {
                PageRevision::create([
                    'page_id' => $page->id,
                    'body' => $currentHtml,
                    'created_by' => optional($request->user())->id,
                    'reason' => 'ai_assist',
                    'meta' => [
                        'mode' => $mode,
                        'provider_model' => $res['model'] ?? null,
                    ],
                ]);
            }
        } catch (Throwable $e) {
            // ignore
        }

        // Apply changes
        $page->body = $clean;

        if ($saveAs === 'draft') {
            $page->status = 'draft';
            $page->published_at = null;

            // If this page was homepage, clear it so / doesn't point to a draft.
            if ((bool) $page->is_homepage) {
                Page::query()->where('is_homepage', true)->update(['is_homepage' => false]);
                $page->is_homepage = false;
            }
        }

        $page->save();

        return response()->json([
            'ok' => true,
            'page_id' => $page->id,
            'clean_html' => $clean,
            'model' => $res['model'] ?? null,
            'edit_url' => route('admin.pages.edit', $page),
            'message' => $saveAs === 'draft' ? 'Saved as draft ✅' : 'Saved ✅',
        ]);
    }

    /**
     * @return array{0:string,1:string} [input, instructions]
     */
    private function buildPrompt(string $title, string $slug, string $currentHtml, string $instruction, string $mode): array
    {
        $rules = [
            'Output ONLY HTML. No markdown. No backticks. No commentary.',
            'Do NOT include <script> tags, inline JS, or event handler attributes (onclick, onload, etc.).',
            'No iframes, embeds, or external JS includes.',
            'All links must be http(s) or relative. No javascript: links.',
            'Keep the structure clean and readable.',
            'Use accessible markup (sensible heading hierarchy, labels for inputs).',
            'Return an HTML FRAGMENT only (no <html>, <head>, <body>, or <!doctype html>).',
        ];

        if ($mode === 'rewrite') {
            $rules[] = 'Rewrite from scratch based on the instruction. Do not preserve the existing HTML.';
        } else {
            $rules[] = 'Tweak the existing HTML. Preserve intent and useful structure, but improve it per the instruction.';
        }

        $instructions = implode("\n", $rules);

        $inputLines = [];
        $inputLines[] = "Page title: {$title}";
        $inputLines[] = "Page slug: {$slug}";
        $inputLines[] = '';
        $inputLines[] = 'User instruction:';
        $inputLines[] = $instruction;
        $inputLines[] = '';

        if ($mode !== 'rewrite') {
            $inputLines[] = 'Current HTML:';
            $inputLines[] = '---';
            $inputLines[] = $currentHtml !== '' ? $currentHtml : '(empty)';
            $inputLines[] = '---';
            $inputLines[] = '';
        }

        $inputLines[] = 'Only output HTML.';

        return [implode("\n", $inputLines), $instructions];
    }
}
