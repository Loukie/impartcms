<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Page;
use App\Support\Ai\LlmClientInterface;
use App\Support\Ai\SiteCloneAnalyzer;
use App\Support\Ai\DesignSystemGenerator;
use App\Support\Ai\AiSiteBlueprintGenerator;
use App\Support\Ai\AiSiteBuilder;
use App\Support\Ai\AiPageGenerator;
use App\Support\Ai\AiImageClientInterface;
use App\Support\Ai\FallbackImageGenerator;
use App\Support\Ai\LinkRewriter;
use App\Support\Ai\HtmlSanitiser;
use App\Support\MediaImporter;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AiSiteCloneAdminController extends Controller
{
    public function __construct(
        private readonly LlmClientInterface $llm,
        private readonly AiImageClientInterface $imageClient,
    ) {}

    /**
     * Show the site cloning form.
     */
    public function create(): View
    {
        return view('admin.pages.ai-clone-site');
    }

    /**
     * Health check to verify LLM is configured and working.
     * GET /admin/site-clone/health
     */
    public function health()
    {
        try {
            // Check if AI is disabled
            if ($this->llm instanceof \App\Support\Ai\NullLlmClient) {
                return response()->json([
                    'healthy' => false,
                    'message' => 'AI provider not configured. Go to Admin → Settings → AI Agent to configure OpenAI or Claude.',
                ], 503);
            }

            // Try a simple LLM call
            $res = $this->llm->generateText('Say "OK" in one word only.');

            if (empty($res['output_text'])) {
                return response()->json([
                    'healthy' => false,
                    'message' => 'AI provider returned empty response.',
                ], 503);
            }

            return response()->json([
                'healthy' => true,
                'message' => 'AI provider is working ✓',
                'provider' => get_class($this->llm),
                'sample_response' => substr($res['output_text'], 0, 30),
            ]);
        } catch (\Throwable $e) {
            Log::error('LLM health check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'healthy' => false,
                'message' => 'AI provider error: ' . $e->getMessage(),
                'error_type' => get_class($e),
            ], 503);
        }
    }

    /**
     * Debug: Test the LLM with a simple JSON request.
     * GET /admin/site-clone/debug-llm
     */
    public function debugLlm()
    {
        try {
            Log::info('Debug LLM: Testing basic LLM call');
            
            $result = $this->llm->generateText('Return valid JSON: {"test": "ok"}');
            
            Log::info('Debug LLM: Result received', [
                'length' => strlen($result['output_text'] ?? ''),
                'first_100_chars' => substr($result['output_text'] ?? '', 0, 100),
            ]);
            
            return response()->json([
                'success' => true,
                'llm_class' => get_class($this->llm),
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('Debug LLM: Exception', [
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'file' =>  $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
            ], 400);
        }
    }

    /**
     * Analyze a site and generate a cloning blueprint.
     *
     * POST /admin/ai/site-clone/analyze
     */
    public function analyze(Request $request)
    {
        // Ensure JSON response for API calls
        $request->headers->set('Accept', 'application/json');
        
        try {
            $request->validate([
                'url' => 'required|url',
                'modification' => 'nullable|string|max:2000',
                'max_pages' => 'nullable|integer|min:3|max:20',
                'selected_pages' => 'nullable|array',
            ]);
        } catch (\Throwable $e) {
            // Validation error - convert to JSON response
            $errors = [];
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                $errors = $e->errors();
            }
            
            Log::error('Clone analyze validation failed', [
                'error' => $e->getMessage(),
                'errors' => $errors,
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Invalid request: ' . ($errors['url'][0] ?? $e->getMessage()),
                'details' => $errors,
            ], 422);
        }

        try {
            // Pre-flight check: ensure AI is configured
            if ($this->llm instanceof \App\Support\Ai\NullLlmClient) {
                throw new \RuntimeException('AI provider not configured. Go to Admin → Settings → AI Agent to set up OpenAI or Claude.');
            }

            $url = trim((string) $request->input('url'));
            $modification = trim((string) ($request->input('modification') ?? ''));
            $maxPages = (int) ($request->input('max_pages') ?? 8);
            $selectedPages = (array) ($request->input('selected_pages') ?? []);
            $modificationWithContract = $this->buildCloneDesignDirective($modification);

            // Normalize URL
            if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }

            Log::info('Starting site clone analysis', [
                'url' => $url,
                'max_pages' => $maxPages,
                'has_modification' => $modification !== '',
            ]);

            // Analyze the site
            $analyzer = new SiteCloneAnalyzer();
            $analysis = $analyzer->analyze($url, $maxPages);

            // If selected pages provided, filter the analysis to only those pages
            if (count($selectedPages) > 0) {
                $selectedUrls = array_map('strtolower', $selectedPages);
                $filteredPages = array_filter($analysis['pages'], function($page) use ($selectedUrls) {
                    return in_array(strtolower($page['url'] ?? ''), $selectedUrls);
                });
                $analysis['pages'] = array_values($filteredPages);  // Re-index array
            }

            Log::info('Site analysis complete', [
                'pages_found' => count($analysis['pages'] ?? []),
                'url' => $url,
            ]);

            // Generate design system
            $designGenerator = new DesignSystemGenerator($this->llm);
            $design = $designGenerator->generate($analysis, $modificationWithContract);
            $design = $this->applyDesignOverrides($design);

            Log::info('Design system generated', [
                'primary_color' => $design['primary_color'] ?? 'N/A',
            ]);

            // Generate blueprint from the analysis
            $blueprintGen = new AiSiteBlueprintGenerator($this->llm);
            $blueprintResult = $blueprintGen->generateForClone(
                $analysis,
                $design,
                $modificationWithContract
            );

            Log::info('Blueprint generated successfully', [
                'pages_in_blueprint' => count($blueprintResult['blueprint']['pages'] ?? []),
            ]);

            return response()->json([
                'success' => true,
                'analysis' => $analysis,
                'design' => $design,
                'blueprint' => $blueprintResult['blueprint'],
                'blueprint_raw' => $blueprintResult['raw_json'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Site clone analyze error', [
                'url' => $url ?? 'unknown',
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorMsg = $e->getMessage();
            // Make error messages user-friendly
            if (str_contains($errorMsg, 'Invalid URL')) {
                $errorMsg = 'Please provide a valid website URL (e.g., https://example.com)';
            } elseif (str_contains($errorMsg, 'Could not connect') || str_contains($errorMsg, 'Connection failed')) {
                $errorMsg = 'Could not connect to the website. Verify the URL is correct, the site is online, and publicly accessible. (Some sites may block automated access.)';
            } elseif (str_contains($errorMsg, 'blocked the request')) {
                $errorMsg = 'The website blocked our request. Some sites don\'t allow automated scraping. Try a different site or contact support.';
            } elseif (str_contains($errorMsg, 'Failed to fetch URL')) {
                $errorMsg = 'Could not access the website. Check the URL and try again. If the problem persists, the site may restrict automated access.';
            } elseif (str_contains($errorMsg, 'not valid JSON') || str_contains($errorMsg, '<!doctype') || str_contains($errorMsg, 'HTML')) {
                $errorMsg = 'Website returned unexpected content. Ensure the URL is a valid website and try again.';
            } elseif (str_contains($errorMsg, 'API error')) {
                $errorMsg = 'AI service error. Check your API key in Admin → Settings → AI Agent. Error: ' . substr($e->getMessage(), 0, 100);
            } elseif (str_contains($errorMsg, 'not configured')) {
                $errorMsg = $e->getMessage();
            } elseif (str_contains($errorMsg, 'Could not extract')) {
                $errorMsg = 'AI returned an unexpected response format. Please try again.';
            }

            return response()->json([
                'success' => false,
                'error' => $errorMsg,
                'details' => $e->getMessage(), // For debugging
            ], 400);
        }
    }

    /**
     * Build the cloned site from analysis and blueprint.
     *
     * POST /admin/ai/site-clone/build
     */
    public function build(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'source_url' => 'required|url',
                'blueprint_json' => 'required|json',
                'design_system' => 'required|json',
                'analysis' => 'nullable|json',
                'publish' => 'boolean',
                'set_homepage' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid build payload.',
                    'validation_errors' => $validator->errors(),
                ], 422);
            }

            $sourceUrl = trim((string) $request->input('source_url'));
            $blueprintJson = (string) $request->input('blueprint_json');
            $designSystem = json_decode((string) $request->input('design_system'), true);
            $analysis = json_decode((string) ($request->input('analysis') ?? '{}'), true);
            $publish = (bool) $request->input('publish', false);
            $setHomepage = (bool) $request->input('set_homepage', false);
            $aiImageGenerationEnabled = $this->isAiImageGenerationEnabled();

            if (!is_array($designSystem)) {
                throw new \InvalidArgumentException('Invalid design system.');
            }

            // Download images/videos and create URL mapping
            $mediaMapping = [];
            $fallbackImageUrl = null;
            if (!empty($analysis['images']) || !empty($analysis['videos'])) {
                Log::info('Downloading media assets for clone');
                $mediaImporter = new MediaImporter($request->user()->id);
                $allMediaUrls = [];
                
                // Collect all image URLs
                if (!empty($analysis['images'])) {
                    $imgs = $analysis['images'];
                    if (!empty($imgs['logo'])) {
                        $allMediaUrls[] = $imgs['logo'];
                    }
                    if (!empty($imgs['hero']) && is_array($imgs['hero'])) {
                        $allMediaUrls = array_merge($allMediaUrls, $imgs['hero']);
                    }
                    if (!empty($imgs['content']) && is_array($imgs['content'])) {
                        $allMediaUrls = array_merge($allMediaUrls, $imgs['content']);
                    }
                    if (!empty($imgs['icons']) && is_array($imgs['icons'])) {
                        // Skip icons - we'll use FontAwesome shortcodes instead
                    }
                }
                
                // Collect video URLs
                if (!empty($analysis['videos']) && is_array($analysis['videos'])) {
                    $allMediaUrls = array_merge($allMediaUrls, $analysis['videos']);
                }
                
                // Download all media and create mapping
                $allMediaUrls = array_unique($allMediaUrls);
                foreach ($allMediaUrls as $url) {
                    $mediaFile = $mediaImporter->importFromUrl($url);
                    if ($mediaFile) {
                        $mediaMapping[$url] = $mediaFile->url;
                        Log::info('Media imported', ['external' => $url, 'internal' => $mediaFile->url]);
                    }
                }
                
                Log::info('Media import complete', ['total' => count($mediaMapping)]);
            }

            // Create one real fallback image file in Media Library for this clone run.
            $fallbackGenerator = new FallbackImageGenerator(
                imageClient: $this->imageClient,
                userId: $request->user()?->id,
            );
            $fallbackMedia = $fallbackGenerator->create([
                'design_system' => $designSystem,
                'source_url' => $sourceUrl,
                'require_ai' => false,
                'disable_ai' => !$aiImageGenerationEnabled,
                'page_title' => (string) ($analysis['title'] ?? ''),
                'page_body' => (string) json_encode($analysis['navigation'] ?? []),
            ]);
            $fallbackImageUrl = $fallbackMedia->url;

            // Build pages from blueprint
            $sanitiser = app(HtmlSanitiser::class);
            $pageGen = new AiPageGenerator($this->llm, $sanitiser);
            $siteBuilder = new AiSiteBuilder($pageGen);

            $result = $siteBuilder->buildFromBlueprintJson($blueprintJson, [
                'style_mode' => 'inline',
                'template' => 'blank',
                'action' => $publish ? 'publish' : 'draft',
                'publish_homepage' => true,
                'set_homepage' => $setHomepage,
                'design_system' => $designSystem,
                'source_url' => $sourceUrl,
                'media_mapping' => $mediaMapping,
                'fallback_image_url' => $fallbackImageUrl,
            ]);

            // Enforce media normalization for cloned pages:
            // - keep original image if we can import it
            // - otherwise generate AI image, save to Media, and re-link src
            $normalization = $this->materializeCloneImagesToMedia(
                pagesReport: (array) ($result['pages'] ?? []),
                sourceUrl: $sourceUrl,
                designSystem: $designSystem,
                userId: $request->user()?->id,
                analysis: $analysis,
                aiImageGenerationEnabled: $aiImageGenerationEnabled,
            );
            if (!empty($normalization['warnings'])) {
                $result['warnings'] = array_merge((array) ($result['warnings'] ?? []), $normalization['warnings']);
            }

            return response()->json([
                'success' => true,
                'created' => $result['created'],
                'pages' => $result['pages'],
                'homepage_id' => $result['homepage_id'],
                'warnings' => $result['warnings'],
                'message' => 'Successfully cloned and created ' . $result['created'] . ' pages.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Ensure every image in cloned pages points to a Media Library URL.
     *
     * Rules:
     * - If original source is reachable, import and re-link to local media URL.
    * - If source is missing/broken/unreachable, prefer contextual replacement from Media Library.
    * - If no contextual replacement is found, use a non-AI generated fallback media asset.
     *
     * @param array<int,array<string,mixed>> $pagesReport
     * @param array<string,mixed> $designSystem
     * @return array{warnings:array<int,string>}
     */
    private function materializeCloneImagesToMedia(array $pagesReport, string $sourceUrl, array $designSystem, ?int $userId, array $analysis = [], bool $aiImageGenerationEnabled = false): array
    {
        $warnings = [];
        $mediaImporter = new MediaImporter($userId);
        $fallbackGenerator = new FallbackImageGenerator(
            imageClient: $this->imageClient,
            userId: $userId,
        );

        /** @var array<string,string> $resolvedCache */
        $resolvedCache = [];

        foreach ($pagesReport as $row) {
            $pageId = isset($row['id']) ? (int) $row['id'] : 0;
            if ($pageId <= 0) {
                continue;
            }

            $page = Page::query()->find($pageId);
            if (!$page) {
                continue;
            }

            $html = (string) ($page->body ?? '');
            $hasImages = (stripos($html, '<img') !== false) || (stripos($html, 'background-image') !== false) || (stripos($html, 'background:') !== false);
            if (!$hasImages) {
                continue;
            }

            $updated = $html;

            // Process <img> tags
            $updated = preg_replace_callback('/<img\b[^>]*>/i', function (array $matches) use (&$resolvedCache, $mediaImporter, $fallbackGenerator, $sourceUrl, $designSystem, $analysis, $page, &$warnings, $aiImageGenerationEnabled) {
                $tag = $matches[0];
                $src = $this->extractAttributeValue($tag, 'src');
                $src = is_string($src) ? trim($src) : '';

                // Existing local media URL/path can remain unchanged.
                if ($src !== '' && $this->isLocalMediaSource($src)) {
                    return $tag;
                }

                $resolved = $this->resolveImageSource($src, $resolvedCache, $mediaImporter, $fallbackGenerator, $sourceUrl, $designSystem, $analysis, $page, $warnings, $aiImageGenerationEnabled);

                if ($resolved === '') {
                    return $tag;
                }

                $tag = $this->setOrReplaceAttribute($tag, 'src', $resolved);
                if ($this->extractAttributeValue($tag, 'alt') === null) {
                    $tag = $this->setOrReplaceAttribute($tag, 'alt', 'Image');
                }

                // Keep resilience on runtime failures as well.
                $onErrorJs = "this.onerror=null;this.src='" . $resolved . "';";
                $tag = $this->setOrReplaceAttribute($tag, 'onerror', $onErrorJs);

                return $tag;
            }, $updated) ?? $updated;

            // Process inline style background-image and background properties
            $updated = preg_replace_callback('/\bstyle\s*=\s*(["\'])([^\1]*?)\1/i', function (array $matches) use (&$resolvedCache, $mediaImporter, $fallbackGenerator, $sourceUrl, $designSystem, $analysis, $page, &$warnings, $aiImageGenerationEnabled) {
                $quote = $matches[1];
                $styleContent = $matches[2];
                
                // Process background-image: url(...) and background: url(...)
                $updatedStyle = preg_replace_callback('/\b(background-image|background)\s*:\s*([^;]*url\([\'"]?)([^\'"\)]+)([\'"]?\)[^;]*)/i', function (array $urlMatches) use (&$resolvedCache, $mediaImporter, $fallbackGenerator, $sourceUrl, $designSystem, $analysis, $page, &$warnings, $aiImageGenerationEnabled) {
                    $property = $urlMatches[1];
                    $beforeUrl = $urlMatches[2];
                    $url = $urlMatches[3];
                    $afterUrl = $urlMatches[4];

                    $url = trim($url);
                    
                    // Skip data URIs and local media
                    if ($url === '' || str_starts_with(strtolower($url), 'data:') || $this->isLocalMediaSource($url)) {
                        return $urlMatches[0];
                    }

                    $resolved = $this->resolveImageSource($url, $resolvedCache, $mediaImporter, $fallbackGenerator, $sourceUrl, $designSystem, $analysis, $page, $warnings, $aiImageGenerationEnabled);

                    if ($resolved === '') {
                        return $urlMatches[0];
                    }

                    return $property . ': ' . $beforeUrl . $resolved . $afterUrl;
                }, $styleContent) ?? $styleContent;

                return 'style=' . $quote . $updatedStyle . $quote;
            }, $updated) ?? $updated;

            if ($updated !== $html) {
                $page->body = $updated;
                $page->save();
            }
        }

        return [
            'warnings' => $warnings,
        ];
    }

    /**
    * Resolve a single image source - try import, contextual replacement, then non-AI fallback.
     *
     * @param array<string,string> &$resolvedCache
     */
    private function resolveImageSource(
        string $src,
        array &$resolvedCache,
        MediaImporter $mediaImporter,
        FallbackImageGenerator $fallbackGenerator,
        string $sourceUrl,
        array $designSystem,
        array $analysis,
        Page $page,
        array &$warnings,
        bool $aiImageGenerationEnabled
    ): string {
        if ($src === '') {
            return '';
        }

        // Check cache first
        if (isset($resolvedCache[$src])) {
            return $resolvedCache[$src];
        }

        $resolved = '';
        $candidate = $this->normaliseImageSourceUrl($src, $sourceUrl);

        // Try importing original
        if ($candidate !== '') {
            $imported = $mediaImporter->importFromUrl($candidate);
            if ($imported) {
                $resolved = (string) $imported->url;
            }
        }

        // Context-aware replacement fallback from existing Media Library.
        if ($resolved === '') {
            $existing = $fallbackGenerator->findContextualReplacementUrl([
                'design_system' => $designSystem,
                'source_url' => $sourceUrl,
                'page_title' => (string) ($page->title ?? ''),
                'page_body' => (string) ($page->body ?? ''),
                'original_src' => $src,
                'analysis_title' => (string) ($analysis['title'] ?? ''),
            ]);

            if (is_string($existing) && trim($existing) !== '') {
                $resolved = trim($existing);
                $warnings[] = 'Image source unavailable on page "' . (string) ($page->title ?? 'Untitled') . '", replaced with contextual Media Library image.';
            }
        }

        // Final fallback as generated non-AI media asset.
        if ($resolved === '') {
            $fallback = $fallbackGenerator->create([
                'design_system' => $designSystem,
                'source_url' => $sourceUrl,
                'require_ai' => false,
                'disable_ai' => !$aiImageGenerationEnabled,
                'page_title' => (string) ($page->title ?? ''),
                'page_body' => (string) ($page->body ?? ''),
                'original_src' => $src,
            ]);
            $resolved = (string) $fallback->url;

            $warnings[] = $aiImageGenerationEnabled
                ? 'Image source unavailable on page "' . (string) ($page->title ?? 'Untitled') . '", replaced with generated media fallback asset.'
                : 'Image source unavailable on page "' . (string) ($page->title ?? 'Untitled') . '", replaced with generated non-AI fallback media asset.';
        }

        $resolvedCache[$src] = $resolved;
        return $resolved;
    }

    private function normaliseImageSourceUrl(string $src, string $sourceUrl): string
    {
        $src = trim($src);
        if ($src === '' || str_starts_with(strtolower($src), 'data:')) {
            return '';
        }

        if (preg_match('#^https?://#i', $src) === 1) {
            return $src;
        }

        $base = parse_url($sourceUrl);
        $scheme = (string) ($base['scheme'] ?? 'https');
        $host = (string) ($base['host'] ?? '');
        if ($host === '') {
            return '';
        }
        $origin = $scheme . '://' . $host;

        if (str_starts_with($src, '//')) {
            return $scheme . ':' . $src;
        }

        if (str_starts_with($src, '/')) {
            return $origin . $src;
        }

        $basePath = (string) ($base['path'] ?? '/');
        $baseDir = rtrim(str_replace('\\', '/', dirname($basePath)), '/');
        if ($baseDir === '' || $baseDir === '.') {
            return $origin . '/' . ltrim($src, '/');
        }

        return $origin . $baseDir . '/' . ltrim($src, '/');
    }

    private function isLocalMediaSource(string $src): bool
    {
        $src = trim($src);
        if ($src === '') {
            return false;
        }

        if (str_starts_with($src, '/storage/media/') || str_starts_with($src, 'storage/media/')) {
            return true;
        }

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $srcHost = parse_url($src, PHP_URL_HOST);
        $srcPath = (string) (parse_url($src, PHP_URL_PATH) ?? '');

        return is_string($appHost)
            && $appHost !== ''
            && is_string($srcHost)
            && strcasecmp($appHost, $srcHost) === 0
            && str_starts_with($srcPath, '/storage/media/');
    }

    private function extractAttributeValue(string $tag, string $attribute): ?string
    {
        $pattern = "/\\b" . preg_quote($attribute, '/') . "\\s*=\\s*(\"([^\"]*)\"|'([^']*)'|([^\\s>]+))/i";
        if (!preg_match($pattern, $tag, $m)) {
            return null;
        }

        return (string) ($m[2] ?? $m[3] ?? $m[4] ?? '');
    }

    private function setOrReplaceAttribute(string $tag, string $attribute, string $value): string
    {
        $escaped = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $replacement = $attribute . '="' . $escaped . '"';
        $pattern = "/\\b" . preg_quote($attribute, '/') . "\\s*=\\s*(\"[^\"]*\"|'[^']*'|[^\\s>]+)/i";

        if (preg_match($pattern, $tag) === 1) {
            return preg_replace($pattern, $replacement, $tag, 1) ?? $tag;
        }

        return preg_replace('/\/>$/', ' ' . $replacement . ' />', $tag, 1)
            ?? preg_replace('/>$/', ' ' . $replacement . '>', $tag, 1)
            ?? $tag;
    }

    private function isAiImageGenerationEnabled(): bool
    {
        try {
            $raw = (string) (Setting::get('ai.images.enabled', '0') ?? '0');
            $v = strtolower(trim($raw));
            return in_array($v, ['1', 'true', 'yes', 'on'], true);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function buildCloneDesignDirective(string $userModification): string
    {
        $mode = strtolower(trim((string) (Setting::get('ai.clone.design_mode', 'premium') ?? 'premium')));
        if (!in_array($mode, ['safe', 'premium', 'strict_reference'], true)) {
            $mode = 'premium';
        }

        $primary = trim((string) (Setting::get('ai.clone.brand_primary_color', '') ?? ''));
        $secondary = trim((string) (Setting::get('ai.clone.brand_secondary_color', '') ?? ''));
        $accent = trim((string) (Setting::get('ai.clone.brand_accent_color', '') ?? ''));
        $globalFont = trim((string) (Setting::get('ai.clone.global_font', '') ?? ''));
        $enforceBrandTokens = $this->toBoolSetting((string) (Setting::get('ai.clone.enforce_brand_tokens', '0') ?? '0'));

        $lines = [];
        if ($userModification !== '') {
            $lines[] = $userModification;
            $lines[] = '';
        }

        $lines[] = 'System design contract (always apply):';
        $lines[] = '- Create premium, modern, professional pages that feel intentional and brand-specific.';
        $lines[] = '- Do not produce generic templates or boilerplate layouts.';
        $lines[] = '- Preserve reference structure/content intent while modernizing hierarchy, spacing, and readability.';
        $lines[] = '- Header behavior: homepage top transparent overlay; homepage on hover/scroll solid light; inner pages solid light.';
        $lines[] = '- Build responsive output for desktop and mobile (320px+), with no broken sections.';

        if ($mode === 'premium' || $mode === 'strict_reference') {
            $lines[] = '- Avoid default/generic nav bars, hero blocks, and repetitive card grids unless context explicitly requires them.';
            $lines[] = '- Define a distinct visual concept and consistent mood across all pages.';
            $lines[] = '- Match reference cues for spacing rhythm, typography tone, contrast, and button treatment.';
        }

        if ($mode === 'strict_reference') {
            $lines[] = '- Prioritize reference-lock: do not drift into unrelated visual direction.';
            $lines[] = '- Keep information density and section sequencing close to source intent.';
        }

        if ($this->isHexColor($primary) && $this->isHexColor($secondary) && $this->isHexColor($accent)) {
            $lines[] = $enforceBrandTokens
                ? '- Brand colors are locked: primary ' . strtolower($primary) . ', secondary ' . strtolower($secondary) . ', accent ' . strtolower($accent) . '.'
                : '- Preferred color direction: primary ' . strtolower($primary) . ', secondary ' . strtolower($secondary) . ', accent ' . strtolower($accent) . ' (adapt per client/reference when needed).';
        }

        if ($globalFont !== '') {
            $lines[] = $enforceBrandTokens
                ? '- Global font is locked: ' . $globalFont . ' (headings and body unless readability would break).'
                : '- Preferred typography direction: ' . $globalFont . ' (adapt per client/reference when needed).';
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<string,mixed> $design
     * @return array<string,mixed>
     */
    private function applyDesignOverrides(array $design): array
    {
        $primary = trim((string) (Setting::get('ai.clone.brand_primary_color', '') ?? ''));
        $secondary = trim((string) (Setting::get('ai.clone.brand_secondary_color', '') ?? ''));
        $accent = trim((string) (Setting::get('ai.clone.brand_accent_color', '') ?? ''));
        $globalFont = trim((string) (Setting::get('ai.clone.global_font', '') ?? ''));
        $enforceBrandTokens = $this->toBoolSetting((string) (Setting::get('ai.clone.enforce_brand_tokens', '0') ?? '0'));

        if (!$enforceBrandTokens) {
            return $design;
        }

        if ($this->isHexColor($primary)) {
            $design['primary_color'] = strtolower($primary);
        }
        if ($this->isHexColor($secondary)) {
            $design['secondary_color'] = strtolower($secondary);
        }
        if ($this->isHexColor($accent)) {
            $design['accent_color'] = strtolower($accent);
        }
        if ($globalFont !== '') {
            $design['heading_font'] = $globalFont;
            $design['body_font'] = $globalFont;
        }

        return $design;
    }

    private function isHexColor(string $color): bool
    {
        $value = trim($color);
        return (bool) preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value);
    }

    private function toBoolSetting(string $value): bool
    {
        $v = strtolower(trim($value));
        return in_array($v, ['1', 'true', 'yes', 'on'], true);
    }
}
