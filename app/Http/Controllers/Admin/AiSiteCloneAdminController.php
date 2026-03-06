<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Ai\LlmClientInterface;
use App\Support\Ai\SiteCloneAnalyzer;
use App\Support\Ai\DesignSystemGenerator;
use App\Support\Ai\AiSiteBlueprintGenerator;
use App\Support\Ai\AiSiteBuilder;
use App\Support\Ai\AiPageGenerator;
use App\Support\Ai\LinkRewriter;
use App\Support\Ai\HtmlSanitiser;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class AiSiteCloneAdminController extends Controller
{
    public function __construct(
        private readonly LlmClientInterface $llm,
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
                'max_pages' => 'nullable|integer|min:3|max:15',
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

            Log::info('Site analysis complete', [
                'pages_found' => count($analysis['pages'] ?? []),
                'url' => $url,
            ]);

            // Generate design system
            $designGenerator = new DesignSystemGenerator($this->llm);
            $design = $designGenerator->generate($analysis, $modification);

            Log::info('Design system generated', [
                'primary_color' => $design['primary_color'] ?? 'N/A',
            ]);

            // Generate blueprint from the analysis
            $blueprintGen = new AiSiteBlueprintGenerator($this->llm);
            $blueprintResult = $blueprintGen->generateForClone(
                $analysis,
                $design,
                $modification
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
        $request->validate([
            'source_url' => 'required|url',
            'blueprint_json' => 'required|json',
            'design_system' => 'required|json',
            'publish' => 'boolean',
            'set_homepage' => 'boolean',
        ]);

        try {
            $sourceUrl = trim((string) $request->input('source_url'));
            $blueprintJson = (string) $request->input('blueprint_json');
            $designSystem = json_decode((string) $request->input('design_system'), true);
            $publish = (bool) $request->input('publish', false);
            $setHomepage = (bool) $request->input('set_homepage', false);

            if (!is_array($designSystem)) {
                throw new \InvalidArgumentException('Invalid design system.');
            }

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
            ]);

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
}
