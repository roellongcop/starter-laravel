<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataTagController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\FormResponseController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\IpController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\LoginHistoryController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationRoleController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\ReferenceFileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SessionHeartbeatController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TeamCategoryController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnableSsr;
use App\Support\Seo;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public/SEO pages: server-side rendered. New marketing/SEO pages go in this
// group. See docs/features/seo-and-ssr.md.
Route::middleware(EnableSsr::class)->group(function () {
    Route::get('/', function () {
        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'seo' => Seo::make(
                title: 'Roel R. Longcop — Full Stack Software Developer',
                description: 'Full Stack Software Developer with 8+ years building '
                    .'web and mobile apps in Laravel, React, Vue and Node.js. '
                    .'See selected work, skills and experience.',
                type: 'profile',
                jsonLd: Seo::personSchema(
                    name: 'Roel R. Longcop',
                    jobTitle: 'Full Stack Software Developer',
                ),
            )->toArray(),
        ]);
    })->name('home');

    Route::get('/contact', [ContactController::class, 'create'])->name('contact');
});

Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::get('/robots.txt', function () {
    $body = implode("\n", [
        'User-agent: *',
        'Disallow:',
        '',
        'Sitemap: '.route('sitemap'),
    ])."\n";

    return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
})->name('robots');

// Public brand images (favicon + logos) — render before login / on guest pages.
Route::get('brand/{slot}', [BrandController::class, 'show'])
    ->where('slot', 'favicon|square-logo|landscape-logo')
    ->name('brand.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/search', [DashboardController::class, 'search'])->name('dashboard.search');
});

Route::middleware('auth')->group(function () {
    // Pinged by the client `useIdleLogout` hook on real activity so the
    // server-side EnforceIdleTimeout treats an actively-used (but not
    // navigating) session as alive.
    Route::post('session/heartbeat', SessionHeartbeatController::class)->name('session.heartbeat');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Generic image pipeline: upload a (cropped) image, then render resized,
    // cached copies on demand. Reusable by any page via <ImagePicker>.
    Route::post('/media', [MediaController::class, 'store'])->name('media.store');
    Route::get('/media/{file}/img', [MediaController::class, 'img'])->name('media.img');

    // User documents (pdf/doc/docx): self-service, owner-scoped. Uploaded via
    // the reusable <FileDropzone>; streamed as gated attachments.
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{file}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('/documents/{file}/view', [DocumentController::class, 'view'])->name('documents.view');
    Route::delete('/documents/{file}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    // Profile photo: pick existing / upload / camera. Stored via /media; the
    // avatar references a file id and is streamed (resized) per user.
    Route::get('/profile/photos', [AvatarController::class, 'photos'])->name('profile.photos');
    Route::post('/profile/avatar', [AvatarController::class, 'store'])->name('profile.avatar.store');
    Route::delete('/profile/avatar', [AvatarController::class, 'destroy'])->name('profile.avatar.destroy');
    Route::get('/avatar/{user}', [AvatarController::class, 'show'])->name('profile.avatar');

    Route::post('users/bulk', [UserController::class, 'bulk'])->name('users.bulk');
    Route::resource('users', UserController::class);

    Route::resource('roles', RoleController::class);

    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings/{group}', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('settings/email/test', [SettingsController::class, 'testEmail'])->name('settings.email.test');

    Route::post('themes/bulk', [ThemeController::class, 'bulk'])->name('themes.bulk');
    Route::resource('themes', ThemeController::class);

    Route::get('files/{file}/download', [FileController::class, 'download'])->name('files.download');
    Route::get('files/{file}/preview', [FileController::class, 'preview'])->name('files.preview');
    Route::resource('files', FileController::class)->only(['index', 'create', 'store', 'show', 'destroy']);

    Route::post('ips/bulk', [IpController::class, 'bulk'])->name('ips.bulk');
    Route::resource('ips', IpController::class)->except(['create', 'edit']);

    Route::resource('organizations', OrganizationController::class)->except(['create', 'edit']);

    Route::resource('projects', ProjectController::class)->except(['create', 'edit']);
    Route::resource('assets', AssetController::class)->except(['create', 'edit']);

    // Teams & People: org-nested teams, their category + organization-role
    // lookups. People are an internal roster managed via TeamController.
    Route::resource('team-categories', TeamCategoryController::class)->except(['create', 'edit']);
    Route::resource('organization-roles', OrganizationRoleController::class)->except(['create', 'edit']);
    Route::resource('teams', TeamController::class)->except(['create', 'edit']);
    // People: read-only roster across teams (members are managed via the team form).
    Route::get('people', [PersonController::class, 'index'])->name('people.index');

    // Reference files: org-nested references with a single uploaded file. The
    // upload/download routes are declared before the resource so they aren't
    // shadowed by the {referenceFile} show route.
    Route::post('reference-files/upload', [ReferenceFileController::class, 'upload'])->name('reference-files.upload');
    Route::get('reference-files/{referenceFile}/download', [ReferenceFileController::class, 'download'])->name('reference-files.download');
    Route::resource('reference-files', ReferenceFileController::class)->except(['create', 'edit']);

    // Data tags: org-nested coloured tags chosen from a fixed palette.
    Route::resource('data-tags', DataTagController::class)->except(['create', 'edit']);

    // Forms have full create/edit pages (the field builder is too rich for a sheet).
    Route::resource('forms', FormController::class);
    // Filling a form + viewing its submissions.
    Route::get('forms/{form}/respond', [FormResponseController::class, 'create'])->name('forms.respond');
    Route::get('forms/{form}/responses', [FormResponseController::class, 'index'])->name('forms.responses.index');
    Route::post('forms/{form}/responses', [FormResponseController::class, 'store'])->name('forms.responses.store');
    Route::get('responses/{response}', [FormResponseController::class, 'show'])->name('responses.show');
    Route::delete('responses/{response}', [FormResponseController::class, 'destroy'])->name('responses.destroy');

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/bulk', [NotificationController::class, 'bulk'])->name('notifications.bulk');
    Route::patch('notifications/{notification}', [NotificationController::class, 'update'])->name('notifications.update');

    // Sessions (read-only + revoke)
    Route::get('sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::delete('sessions/{session}', [SessionController::class, 'destroy'])->name('sessions.destroy');

    // Audit logs (read-only)
    Route::get('logs', [LogController::class, 'index'])->name('logs.index');
    Route::get('logs/{log}', [LogController::class, 'show'])->name('logs.show');

    // Login history (read-only)
    Route::get('login-history', [LoginHistoryController::class, 'index'])->name('login-history.index');

    // Queue monitor
    Route::get('queue', [QueueController::class, 'index'])->name('queue.index');
    Route::post('queue/retry', [QueueController::class, 'retry'])->name('queue.retry');
    Route::post('queue/clear-failed', [QueueController::class, 'clearFailed'])->name('queue.clear-failed');
    Route::post('queue/clear-pending', [QueueController::class, 'clearPending'])->name('queue.clear-pending');

    // Backups
    Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
    Route::post('backups', [BackupController::class, 'store'])->name('backups.store');
    Route::get('backups/{backup}/download', [BackupController::class, 'download'])->name('backups.download');
    Route::post('backups/{backup}/restore', [BackupController::class, 'restore'])->name('backups.restore');
    Route::delete('backups/{backup}', [BackupController::class, 'destroy'])->name('backups.destroy');

    // Exports (MyExport)
    Route::get('exports', [ExportController::class, 'index'])->name('exports.index');
    Route::get('exports/create', [ExportController::class, 'create'])->name('exports.create');
    Route::post('exports', [ExportController::class, 'store'])->name('exports.store');
    Route::get('exports/d/{token}', [ExportController::class, 'download'])->name('exports.download');

    // Imports (MyImport)
    Route::get('imports', [ImportController::class, 'index'])->name('imports.index');
    Route::get('imports/create', [ImportController::class, 'create'])->name('imports.create');
    Route::post('imports', [ImportController::class, 'store'])->name('imports.store');
    Route::get('imports/{import}/download', [ImportController::class, 'download'])->name('imports.download');
    Route::get('imports/{import}/preview', [ImportController::class, 'preview'])->name('imports.preview');
    Route::post('imports/{import}/process', [ImportController::class, 'process'])->name('imports.process');
    Route::get('imports/{import}/errors', [ImportController::class, 'errors'])->name('imports.errors');
    Route::delete('imports/{import}', [ImportController::class, 'destroy'])->name('imports.destroy');
});

require __DIR__.'/auth.php';
