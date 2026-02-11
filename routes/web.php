<?php

use App\Http\Controllers\Admin\PageAdminController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\FormSubmissionController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Models\Page;
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

Route::get('/', [PageController::class, 'show'])->name('page.home');

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
        Route::get('/', fn () => redirect()->route('admin.pages.index'))->name('home');

        // Settings
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

        // Trash routes
        Route::get('/pages-trash', [PageAdminController::class, 'trash'])->name('pages.trash');
        Route::post('/pages-trash/{pageTrash}/restore', [PageAdminController::class, 'restore'])->name('pages.restore');
        Route::delete('/pages-trash/{pageTrash}/force', [PageAdminController::class, 'forceDestroy'])->name('pages.forceDestroy');

        // Homepage selection (WordPress-style)
        Route::post('/pages/{page}/homepage', [PageAdminController::class, 'setHomepage'])->name('pages.setHomepage');

        // Standard pages CRUD (destroy = Move to Trash)
        Route::resource('pages', PageAdminController::class);
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
