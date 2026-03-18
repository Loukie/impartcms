<?php

use App\Http\Controllers\Admin\MediaAdminController;
use App\Http\Controllers\Admin\FormAdminController;
use App\Http\Controllers\Admin\FormSettingsAdminController;
use App\Http\Controllers\Admin\FormSubmissionAdminController;
use App\Http\Controllers\Admin\CustomSnippetAdminController;
use App\Http\Controllers\Admin\LayoutBlockAdminController;
use App\Http\Controllers\Admin\AiPageAdminController;
use App\Http\Controllers\Admin\AiPageAssistAdminController;
use App\Http\Controllers\Admin\AiSiteBuilderAdminController;
use App\Http\Controllers\Admin\AiSiteCloneAdminController;
use App\Http\Controllers\Admin\AiAgentSettingsController;
use App\Http\Controllers\Admin\AiVisualAuditAdminController;
use App\Http\Controllers\Admin\VisualEditorController;
use App\Http\Controllers\Admin\PageAdminController;
use App\Http\Controllers\Admin\ResetController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\FormSubmissionController;
use App\Http\Controllers\FaviconController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Models\Page;
use App\Models\Form;
use App\Models\CustomSnippet;
use App\Models\LayoutBlock;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * ✅ Bind preview param INCLUDING trashed pages
 */
Route::bind('pagePreview', function ($value) {
    return Page::withTrashed()->whereKey($value)->firstOrFail();
});

/**
 * ✅ Bind trash param INCLUDING trashed pages (prevents 404 on restore/force delete)
 */
Route::bind('pageTrash', function ($value) {
    return Page::withTrashed()->whereKey($value)->firstOrFail();
});

/**
 * ✅ Bind trash params INCLUDING trashed forms/users (prevents 404 on restore/force delete)
 */
Route::bind('formTrash', function ($value) {
    return Form::withTrashed()->whereKey($value)->firstOrFail();
});

Route::bind('userTrash', function ($value) {
    return User::withTrashed()->whereKey($value)->firstOrFail();
});

Route::bind('snippetTrash', function ($value) {
    return CustomSnippet::withTrashed()->whereKey($value)->firstOrFail();
});

Route::bind('layoutBlockTrash', function ($value) {
    return LayoutBlock::withTrashed()->whereKey($value)->firstOrFail();
});

/**
 * Public homepage
 * PageController@show resolves homepage via is_homepage flag (published only).
 */
Route::get('/', [PageController::class, 'show'])->name('page.home');

/**
 * Dynamic SVG favicon when an icon is selected in Settings.
 * (If a Media favicon is chosen, layouts will link directly to that media URL.)
 */
Route::get('/favicon.svg', [FaviconController::class, 'svg'])->name('favicon.svg');

/**
 * Breeze expects this after login/register
 */
Route::middleware(['auth', 'verified'])
    ->get('/dashboard', function () {
        return view('dashboard');
    })
    ->name('dashboard');

/**
 * Breeze profile routes
 */
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/**
 * ✅ Admin-only preview route (drafts + trashed can be previewed here)
 * Must be BEFORE catch-all.
 */
Route::middleware(['auth', 'can:access-admin'])
    ->get('/_preview/pages/{pagePreview}', [PageController::class, 'preview'])
    ->name('pages.preview');

/**
 * ✅ Signed preview for automation (screenshots/AI visual audit)
 * No auth required, but URL is time-limited and signed.
 */
Route::get('/_ai_preview/pages/{pagePreview}', [PageController::class, 'aiPreview'])
    ->middleware(['signed'])
    ->name('pages.aiPreview');

/**
 * CMS admin routes
 */
Route::middleware(['auth', 'can:access-admin'])
    ->prefix(config('cms.admin_path', 'admin'))
    ->name('admin.')
    ->group(function () {

        /**
         * Admin landing
         * Keep this stable: /admin should never 500 even if a view gets renamed.
         */
        Route::get('/', fn () => redirect()->route('dashboard'))->name('home');

        // Settings
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

        // Data Reset
        Route::get('/reset', [ResetController::class, 'index'])->name('reset');
        Route::post('/reset/clear', [ResetController::class, 'clear'])->name('reset.clear');

        // Visual Editor — Typography
        Route::get('/visual-editor/typography/{page}', [VisualEditorController::class, 'getTypography'])->name('visual-editor.typography');
        Route::post('/visual-editor/typography/{page}', [VisualEditorController::class, 'saveTypography'])->name('visual-editor.typography.save');

        // AI Agent settings
        Route::get('/ai-agent', [AiAgentSettingsController::class, 'edit'])->name('ai-agent.edit');
        Route::put('/ai-agent', [AiAgentSettingsController::class, 'update'])->name('ai-agent.update');

        // Visual Editor (GrapesJS)
        Route::get('/visual-editor/assets', [VisualEditorController::class, 'assets'])->name('visual-editor.assets');
        Route::post('/visual-editor/fonts/upload', [VisualEditorController::class, 'uploadFont'])->name('visual-editor.fonts.upload');
        Route::get('/visual-editor/page/{page}', [VisualEditorController::class, 'editPage'])->name('visual-editor.page.edit');
        Route::put('/visual-editor/page/{page}', [VisualEditorController::class, 'savePage'])->name('visual-editor.page.save');
        Route::get('/visual-editor/block/{layoutBlock}', [VisualEditorController::class, 'editBlock'])->name('visual-editor.block.edit');
        Route::put('/visual-editor/block/{layoutBlock}', [VisualEditorController::class, 'saveBlock'])->name('visual-editor.block.save');

        // AI Visual Audit
        Route::get('/ai-visual-audit', [AiVisualAuditAdminController::class, 'index'])->name('ai.visual-audit');
        Route::post('/ai-visual-audit/redesign', [AiVisualAuditAdminController::class, 'redesign'])
            ->middleware(['throttle:2,1'])
            ->name('ai.visual-audit.redesign');

        // Trash routes
        Route::get('/pages-trash', [PageAdminController::class, 'trash'])->name('pages.trash');
        Route::post('/pages-trash/{pageTrash}/restore', [PageAdminController::class, 'restore'])->name('pages.restore');
        Route::delete('/pages-trash/{pageTrash}/force', [PageAdminController::class, 'forceDestroy'])->name('pages.forceDestroy');

        // Bulk permanent delete from trash
        Route::post('/pages-trash/bulk', [PageAdminController::class, 'bulkForceDestroy'])->name('pages.trash.bulk');

        Route::get('/forms-trash', [FormAdminController::class, 'trash'])->name('forms.trash');
        Route::post('/forms-trash/{formTrash}/restore', [FormAdminController::class, 'restore'])->name('forms.restore');
        Route::delete('/forms-trash/{formTrash}/force', [FormAdminController::class, 'forceDestroy'])->name('forms.forceDestroy');
        Route::post('/forms-trash/bulk', [FormAdminController::class, 'bulkForceDestroy'])->name('forms.trash.bulk');

        Route::get('/users-trash', [UserAdminController::class, 'trash'])->name('users.trash');
        Route::post('/users-trash/{userTrash}/restore', [UserAdminController::class, 'restore'])->name('users.restore');
        Route::delete('/users-trash/{userTrash}/force', [UserAdminController::class, 'forceDestroy'])->name('users.forceDestroy');
        Route::post('/users-trash/bulk', [UserAdminController::class, 'bulkForceDestroy'])->name('users.trash.bulk');

        // Header & Footer
        Route::post('/layout-blocks/options', [LayoutBlockAdminController::class, 'updateOptions'])->name('layout-blocks.options');

        Route::get('/layout-blocks-trash', [LayoutBlockAdminController::class, 'trash'])->name('layout-blocks.trash');
        Route::post('/layout-blocks-trash/{layoutBlockTrash}/restore', [LayoutBlockAdminController::class, 'restore'])->name('layout-blocks.restore');
        Route::delete('/layout-blocks-trash/{layoutBlockTrash}/force', [LayoutBlockAdminController::class, 'forceDestroy'])->name('layout-blocks.force-destroy');
        Route::post('/layout-blocks-trash/bulk', [LayoutBlockAdminController::class, 'bulkForceDestroy'])->name('layout-blocks.trash.bulk');

        Route::post('/layout-blocks/bulk', [LayoutBlockAdminController::class, 'bulk'])->name('layout-blocks.bulk');
        Route::resource('layout-blocks', LayoutBlockAdminController::class)->parameters([
            'layout-blocks' => 'layoutBlock',
        ]);

        // Custom code (CSS + Scripts)
        Route::get('/snippets-trash', [CustomSnippetAdminController::class, 'trash'])->name('snippets.trash');
        Route::post('/snippets-trash/{snippetTrash}/restore', [CustomSnippetAdminController::class, 'restore'])->name('snippets.restore');
        Route::delete('/snippets-trash/{snippetTrash}/force', [CustomSnippetAdminController::class, 'forceDestroy'])->name('snippets.forceDestroy');
        Route::post('/snippets-trash/bulk', [CustomSnippetAdminController::class, 'bulkForceDestroy'])->name('snippets.trash.bulk');

        Route::post('/snippets/bulk', [CustomSnippetAdminController::class, 'bulk'])->name('snippets.bulk');
        Route::resource('snippets', CustomSnippetAdminController::class);

        // Homepage selection (WordPress-style)
        Route::post('/pages/{page}/homepage', [PageAdminController::class, 'setHomepage'])->name('pages.setHomepage');
        Route::post('/pages/{page}/homepage/unset', [PageAdminController::class, 'unsetHomepage'])->name('pages.unsetHomepage');
        Route::post('/pages/homepage/clear', [PageAdminController::class, 'clearHomepage'])->name('pages.clearHomepage');

        // AI page generator (creates a new Page and injects generated HTML into body)
        Route::get('/pages-ai', [AiPageAdminController::class, 'create'])->name('pages.ai.create');
        Route::post('/pages-ai', [AiPageAdminController::class, 'store'])
            ->middleware(['throttle:6,1'])
            ->name('pages.ai.store');

        // AI site builder (blueprint -> bulk pages)
        Route::get('/site-builder', [AiSiteBuilderAdminController::class, 'create'])->name('site-builder.create');
        Route::post('/site-builder/blueprint', [AiSiteBuilderAdminController::class, 'blueprint'])
            ->middleware(['throttle:3,1'])
            ->name('site-builder.blueprint');
        Route::post('/site-builder/build', [AiSiteBuilderAdminController::class, 'build'])
            ->middleware(['throttle:2,1'])
            ->name('site-builder.build');

        // AI site cloning (fetch existing site -> improve & clone)
        Route::get('/site-clone', [AiSiteCloneAdminController::class, 'create'])->name('site-clone.create');
        Route::get('/site-clone/health', [AiSiteCloneAdminController::class, 'health'])->name('site-clone.health');
        Route::get('/site-clone/debug-llm', [AiSiteCloneAdminController::class, 'debugLlm'])->name('site-clone.debug-llm');
        Route::post('/site-clone/analyze', [AiSiteCloneAdminController::class, 'analyze'])
            ->middleware(['throttle:3,1'])
            ->name('site-clone.analyze');
        Route::post('/site-clone/build', [AiSiteCloneAdminController::class, 'build'])
            ->middleware(['throttle:2,1'])
            ->name('site-clone.build');

        // AI popup helpers
        Route::get('/ai/pages/search', [AiPageAssistAdminController::class, 'search'])
            ->middleware(['throttle:30,1'])
            ->name('ai.pages.search');

        Route::post('/ai/page-assist', [AiPageAssistAdminController::class, 'assist'])
            ->middleware(['throttle:6,1'])
            ->name('ai.page-assist');

        // Bulk actions for pages - accept both POST and DELETE
        Route::match(['post', 'delete'], '/pages-bulk', [PageAdminController::class, 'bulk'])->name('pages.bulk');
        Route::get('/pages-bulk', fn() => redirect()->route('admin.pages.index'));

        // Standard pages CRUD
        Route::resource('pages', PageAdminController::class);

        // Users
        Route::get('/users', [UserAdminController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserAdminController::class, 'create'])->name('users.create');
        Route::post('/users', [UserAdminController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserAdminController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserAdminController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/send-reset-link', [UserAdminController::class, 'sendResetLink'])->name('users.sendResetLink');
        Route::post('/users/{user}/toggle-admin', [UserAdminController::class, 'toggleAdmin'])->name('users.toggleAdmin');
        Route::delete('/users/{user}', [UserAdminController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/bulk', [UserAdminController::class, 'bulk'])->name('users.bulk');

        // Media
        Route::get('/media', [MediaAdminController::class, 'index'])->name('media.index');
        Route::post('/media', [MediaAdminController::class, 'store'])->name('media.store');
        Route::post('/media/bulk', [MediaAdminController::class, 'bulk'])->name('media.bulk');
        
        // Media trash (soft delete system)
        Route::get('/media/trash', [MediaAdminController::class, 'trash'])->name('media.trash');
        Route::post('/media/trash/{id}/restore', [MediaAdminController::class, 'restore'])->whereNumber('id')->name('media.restore');
        Route::delete('/media/trash/{id}/force', [MediaAdminController::class, 'forceDelete'])->whereNumber('id')->name('media.forceDelete');
        Route::post('/media/trash/bulk', [MediaAdminController::class, 'bulkForceDelete'])->name('media.trash.bulk');

        /**
         * ✅ Media picker (WordPress-style modal)
         * MUST be above /media/{media}
         */
        Route::get('/media/picker', [MediaAdminController::class, 'picker'])->name('media.picker');

        Route::get('/media/{media}', [MediaAdminController::class, 'show'])->whereNumber('media')->name('media.show');
        Route::put('/media/{media}', [MediaAdminController::class, 'update'])->whereNumber('media')->name('media.update');
        Route::delete('/media/{media}', [MediaAdminController::class, 'destroy'])->whereNumber('media')->name('media.destroy');

        // Forms
        Route::get('/forms/settings', [FormSettingsAdminController::class, 'edit'])->name('forms.settings.edit');
        Route::put('/forms/settings', [FormSettingsAdminController::class, 'update'])->name('forms.settings.update');

        Route::get('/forms', [FormAdminController::class, 'index'])->name('forms.index');
        Route::get('/forms/create', [FormAdminController::class, 'create'])->name('forms.create');
        Route::post('/forms', [FormAdminController::class, 'store'])->name('forms.store');
        Route::get('/forms/{form}/edit', [FormAdminController::class, 'edit'])->name('forms.edit');
        Route::put('/forms/{form}', [FormAdminController::class, 'update'])->name('forms.update');
        Route::delete('/forms/{form}', [FormAdminController::class, 'destroy'])->name('forms.destroy');
        // bulk trash
        Route::post('/forms/bulk', [FormAdminController::class, 'bulk'])->name('forms.bulk');

        Route::get('/forms/{form}/submissions', [FormSubmissionAdminController::class, 'index'])->name('forms.submissions.index');
        Route::get('/forms/{form}/submissions/{submission}', [FormSubmissionAdminController::class, 'show'])->name('forms.submissions.show');
    });

/**
 * Forms
 */
Route::post('/forms/{form:slug}/submit', [FormSubmissionController::class, 'submit'])
    ->middleware(['throttle:20,1'])
    ->name('forms.submit');

/**
 * Auth routes (login/register/etc)
 */
require __DIR__ . '/auth.php';

/**
 * Public debug endpoints (for testing without auth)
 */
Route::get('/test/llm-config', function () {
    $llm = app(\App\Support\Ai\LlmClientInterface::class);
    $config = [
        'llm_class' => get_class($llm),
        'is_null' => $llm instanceof \App\Support\Ai\NullLlmClient,
        'timestamp' => now()->toIso8601String(),
    ];
    
    if (!($llm instanceof \App\Support\Ai\NullLlmClient)) {
        try {
            $result = $llm->generateText('reply: ok');
            $config['test_result'] = 'success';
            $config['response_length'] = strlen($result['output_text'] ?? '');
            $config['response_sample'] = substr($result['output_text'], 0, 50);
        } catch (\Throwable $e) {
            $config['test_result'] = 'error';
            $config['error'] = $e->getMessage();
        }
    }
    
    return response()->json($config);
});

Route::get('/test/internet-access', function () {
    $tests = [];
    
    // Test 1: example.com
    try {
        $response = \Illuminate\Support\Facades\Http::timeout(5)->withoutVerifying()->get('https://www.example.com');
        $tests['example.com'] = [
            'success' => true,
            'status' => $response->status(),
            'content_length' => strlen($response->body()),
        ];
    } catch (\Throwable $e) {
        $tests['example.com'] = [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
    
    // Test 2: google.com
    try {
        $response = \Illuminate\Support\Facades\Http::timeout(5)->withoutVerifying()->get('https://www.google.com');
        $tests['google.com'] = [
            'success' => true,
            'status' => $response->status(),
            'content_length' => strlen($response->body()),
        ];
    } catch (\Throwable $e) {
        $tests['google.com'] = [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
    
    return response()->json([
        'internet_access' => collect($tests)->every(fn($t) => $t['success'] ?? false),
        'tests' => $tests,
    ]);
});

Route::get('/test/site-clone-fetch/{url?}', function ($url = 'https://www.example.com') {
    $url = urldecode($url);
    $results = [];
    
    // Exactly mimic what SiteCloneAnalyzer does
    try {
        \Log::info('Debug: Testing site clone fetch', ['url' => $url]);
        
        $response = \Illuminate\Support\Facades\Http::withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
            ->withHeaders([
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Accept-Encoding' => 'gzip, deflate',
                'DNT' => '1',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
            ])
            ->timeout(30)
            ->retry(2, 500)
            ->withoutVerifying()
            ->get($url);

        $response->throw();
        
        $body = (string) $response->body();
        
        $results['fetch_success'] = true;
        $results['status'] = $response->status();
        $results['content_type'] = $response->header('Content-Type');
        $results['content_length'] = strlen($body);
        $results['first_100_chars'] = substr($body, 0, 100);
        
        // Check if HTML
        $isHtml = stripos($response->header('Content-Type'), 'text/html') !== false || 
                  preg_match('/<html|<!doctype/i', substr($body, 0, 500));
        $results['is_valid_html'] = $isHtml;
        
        if (!$isHtml) {
            $results['warning'] = 'Response does not appear to be HTML';
        }
        
    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        $results['fetch_success'] = false;
        $results['error_type'] = 'ConnectionException';
        $results['error'] = $e->getMessage();
    } catch (\Illuminate\Http\Client\RequestException $e) {
        $results['fetch_success'] = false;
        $results['error_type'] = 'RequestException';
        $results['status'] = $e->response?->status() ?? 'unknown';
        $results['error'] = $e->getMessage();
    } catch (\Throwable $e) {
        $results['fetch_success'] = false;
        $results['error_type'] = get_class($e);
        $results['error'] = $e->getMessage();
    }
    
    return response()->json([
        'url' => $url,
        'results' => $results,
    ]);
});

/**
 * ✅ Public CMS pages (published only) - MUST be last
 * Soft-deleted pages are excluded automatically.
 */
Route::get('/{slug}', [PageController::class, 'show'])
    ->where('slug', '.*')
    ->name('page.show');
