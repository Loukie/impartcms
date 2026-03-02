<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageRevision;
use App\Support\Ai\AiVisualRedesigner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class AiVisualAuditAdminController extends Controller
{
    public function __construct(
        private readonly AiVisualRedesigner $redesigner,
    ) {}

    public function index(): View
    {
        return view('admin.ai.visual-audit', [
            'pages' => Page::query()->orderByDesc('updated_at')->get(['id', 'title', 'slug', 'status']),
        ]);
    }

    /**
     * Capture screenshot of a page + reference URL, then redesign and save as draft.
     */
    public function redesign(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'page_id' => ['required', 'integer', 'exists:pages,id'],
            'reference_url' => ['required', 'url', 'max:2000'],
            'instruction' => ['nullable', 'string', 'max:2000'],
        ]);

        $page = Page::query()->whereKey((int) $data['page_id'])->firstOrFail();
        $referenceUrl = trim((string) $data['reference_url']);
        $instruction = trim((string) ($data['instruction'] ?? 'Match the reference site visual hierarchy and spacing, and make the page feel premium and clean.'));

        // Signed, public-ish preview URL (works for drafts without requiring auth)
        $pageUrl = URL::temporarySignedRoute(
            name: 'pages.aiPreview',
            expiration: now()->addMinutes(10),
            parameters: ['pagePreview' => $page->id],
        );

        $dir = storage_path('app/ai/screenshots');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $stamp = now()->format('Ymd_His');
        $slugSafe = Str::slug($page->slug ?: $page->title ?: 'page');
        $pageShot = $dir . DIRECTORY_SEPARATOR . "page_{$page->id}_{$slugSafe}_{$stamp}.png";
        $refShot = $dir . DIRECTORY_SEPARATOR . "ref_{$page->id}_{$stamp}.png";

        try {
            $this->captureScreenshot($pageUrl, $pageShot);
            $this->captureScreenshot($referenceUrl, $refShot);
        } catch (Throwable $e) {
            return back()->with('status', 'Screenshot failed: ' . $e->getMessage());
        }

        // Save revision (best-effort)
        try {
            if (class_exists(PageRevision::class)) {
                PageRevision::create([
                    'page_id' => $page->id,
                    'body' => (string) ($page->body ?? ''),
                    'created_by' => optional($request->user())->id,
                    'reason' => 'ai_visual_redesign',
                    'meta' => [
                        'reference_url' => $referenceUrl,
                        'page_screenshot' => $pageShot,
                        'ref_screenshot' => $refShot,
                    ],
                ]);
            }
        } catch (Throwable $e) {
            // ignore
        }

        try {
            $out = $this->redesigner->redesign(
                pageTitle: (string) $page->title,
                instruction: $instruction,
                currentScreenshot: $pageShot,
                referenceScreenshot: $refShot,
            );
        } catch (Throwable $e) {
            return back()->with('status', 'AI redesign failed: ' . $e->getMessage());
        }

        $clean = trim((string) ($out['clean_html'] ?? ''));
        if ($clean === '') {
            return back()->with('status', 'AI redesign failed: empty output.');
        }

        // Safe default: save as draft.
        $page->body = $clean;
        $page->status = 'draft';
        $page->published_at = null;
        if ((bool) $page->is_homepage) {
            Page::query()->where('is_homepage', true)->update(['is_homepage' => false]);
            $page->is_homepage = false;
        }
        $page->save();

        return redirect()->route('admin.pages.edit', $page)
            ->with('status', 'Visual redesign saved as draft ✅ (review and publish when ready)');
    }

    private function captureScreenshot(string $url, string $outPath): void
    {
        // PHP-only screenshot approach:
        // Use a locally installed Chrome/Chromium/Edge headless binary (no Node required).
        $bin = $this->findHeadlessBrowserBinary();
        if ($bin === null) {
            throw new RuntimeException(
                'No headless browser found. Install Google Chrome or Microsoft Edge, or set AI_SCREENSHOT_BIN in .env to the full path of chrome.exe/msedge.exe.'
            );
        }

        $profileDir = storage_path('app/ai/browser-profile/' . Str::random(10));
        if (!is_dir($profileDir)) {
            @mkdir($profileDir, 0775, true);
        }

        // Above-the-fold + a bit more. Full-page screenshots in pure CLI headless mode can be unreliable.
        $windowSize = '1440,2400';

        // Prefer the modern headless implementation if supported; fall back to legacy headless if not.
        $baseArgs = [
            $bin,
            '--disable-gpu',
            '--hide-scrollbars',
            '--no-first-run',
            '--no-default-browser-check',
            '--disable-dev-shm-usage',
            '--user-data-dir=' . $profileDir,
            '--window-size=' . $windowSize,
            '--virtual-time-budget=6000',
            '--screenshot=' . $outPath,
            $url,
        ];

        $attempts = [
            array_merge([$bin, '--headless=new'], array_slice($baseArgs, 1)),
            array_merge([$bin, '--headless'], array_slice($baseArgs, 1)),
        ];

        $lastErr = null;
        foreach ($attempts as $args) {
            $process = new Process($args, base_path());
            $process->setTimeout(120);
            $process->run();

            if ($process->isSuccessful() && is_file($outPath)) {
                return;
            }

            $lastErr = trim($process->getErrorOutput()) ?: trim($process->getOutput()) ?: 'Unknown screenshot error.';
        }

        throw new RuntimeException($lastErr ?: 'Screenshot did not produce a file.');
    }

    private function findHeadlessBrowserBinary(): ?string
    {
        // Explicit override
        $env = (string) env('AI_SCREENSHOT_BIN', '');
        if ($env !== '' && is_file($env)) {
            return $env;
        }

        // If the binary is available on PATH (rare on Windows, common on Linux)
        foreach (['chrome', 'google-chrome', 'chromium', 'chromium-browser', 'msedge'] as $cmd) {
            try {
                $p = new Process([$cmd, '--version']);
                $p->setTimeout(5);
                $p->run();
                if ($p->isSuccessful()) {
                    return $cmd;
                }
            } catch (Throwable $e) {
                // ignore
            }
        }

        // Common Windows install paths
        $candidates = [
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
