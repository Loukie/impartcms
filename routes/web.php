<?php

use App\Http\Controllers\Admin\MediaAdminController;
use App\Http\Controllers\Admin\FormAdminController;
use App\Http\Controllers\Admin\FormSettingsAdminController;
use App\Http\Controllers\Admin\FormSubmissionAdminController;
use App\Http\Controllers\Admin\CustomSnippetAdminController;
use App\Http\Controllers\Admin\LayoutBlockAdminController;
use App\Http\Controllers\Admin\PageAdminController;
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

        // Trash routes
        Route::get('/pages-trash', [PageAdminController::class, 'trash'])->name('pages.trash');
        Route::post('/pages-trash/{pageTrash}/restore', [PageAdminController::class, 'restore'])->name('pages.restore');
        Route::delete('/pages-trash/{pageTrash}/force', [PageAdminController::class, 'forceDestroy'])->name('pages.forceDestroy');

        Route::get('/forms-trash', [FormAdminController::class, 'trash'])->name('forms.trash');
        Route::post('/forms-trash/{formTrash}/restore', [FormAdminController::class, 'restore'])->name('forms.restore');
        Route::delete('/forms-trash/{formTrash}/force', [FormAdminController::class, 'forceDestroy'])->name('forms.forceDestroy');

        Route::get('/users-trash', [UserAdminController::class, 'trash'])->name('users.trash');
        Route::post('/users-trash/{userTrash}/restore', [UserAdminController::class, 'restore'])->name('users.restore');
        Route::delete('/users-trash/{userTrash}/force', [UserAdminController::class, 'forceDestroy'])->name('users.forceDestroy');

        // Header & Footer
        Route::post('/layout-blocks/options', [LayoutBlockAdminController::class, 'updateOptions'])->name('layout-blocks.options');

        Route::get('/layout-blocks-trash', [LayoutBlockAdminController::class, 'trash'])->name('layout-blocks.trash');
        Route::post('/layout-blocks-trash/{layoutBlockTrash}/restore', [LayoutBlockAdminController::class, 'restore'])->name('layout-blocks.restore');
        Route::delete('/layout-blocks-trash/{layoutBlockTrash}/force', [LayoutBlockAdminController::class, 'forceDestroy'])->name('layout-blocks.force-destroy');

        Route::resource('layout-blocks', LayoutBlockAdminController::class)->parameters([
            'layout-blocks' => 'layoutBlock',
        ]);

        // Custom code (CSS + Scripts)
        Route::get('/snippets-trash', [CustomSnippetAdminController::class, 'trash'])->name('snippets.trash');
        Route::post('/snippets-trash/{snippetTrash}/restore', [CustomSnippetAdminController::class, 'restore'])->name('snippets.restore');
        Route::delete('/snippets-trash/{snippetTrash}/force', [CustomSnippetAdminController::class, 'forceDestroy'])->name('snippets.forceDestroy');

        Route::resource('snippets', CustomSnippetAdminController::class);

        // Homepage selection (WordPress-style)
        Route::post('/pages/{page}/homepage', [PageAdminController::class, 'setHomepage'])->name('pages.setHomepage');

        // Standard pages CRUD (destroy = Move to Trash)
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

        // Media
        Route::get('/media', [MediaAdminController::class, 'index'])->name('media.index');
        Route::post('/media', [MediaAdminController::class, 'store'])->name('media.store');

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
 * ✅ Public CMS pages (published only) - MUST be last
 * Soft-deleted pages are excluded automatically.
 */
Route::get('/{slug}', [PageController::class, 'show'])
    ->where('slug', '.*')
    ->name('page.show');
