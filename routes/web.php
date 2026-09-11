<?php

/**
 * ROUTES
 * =============================================================================
 * Grouped by who the route is for. Every authenticated group is guarded by the
 * `role` middleware; per-record checks (is this *your* portfolio?) are left to
 * PortfolioPolicy inside the controllers.
 * =============================================================================
 */

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ChairController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\OjtSupervisorController;
use App\Http\Controllers\PortfolioController;
use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------------------
// Guest
// -----------------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/', fn () => redirect()->route('login'));
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

// The OJT supervisor form: public by design, protected by a single-use token.
Route::get('/ojt-evaluation/{token}', [OjtSupervisorController::class, 'show'])->name('ojt.supervisor');
Route::post('/ojt-evaluation/{token}', [OjtSupervisorController::class, 'store']);

// -----------------------------------------------------------------------------
// Authenticated (any role)
// -----------------------------------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Exports are authorized per portfolio inside the controller.
    Route::get('/portfolios/{portfolio}/export/word', [ExportController::class, 'word'])->name('export.word');
    Route::get('/portfolios/{portfolio}/export/pdf', [ExportController::class, 'pdf'])->name('export.pdf');
    Route::get('/students/{student}/transcript', [ExportController::class, 'transcript'])->name('export.transcript');

    Route::get('/evidence/{evidence}/download', [EvidenceController::class, 'download'])->name('evidence.download');

    // Readable by the owner and by any evaluator; PortfolioPolicy decides, and
    // the page renders read-only for anyone who cannot edit it.
    Route::get('/portfolio/{portfolio}/section/{number}', [PortfolioController::class, 'section'])
        ->whereNumber('number')->name('portfolio.section');
    Route::get('/portfolio/{portfolio}/history', [PortfolioController::class, 'history'])->name('portfolio.history');
});

// -----------------------------------------------------------------------------
// Student
// -----------------------------------------------------------------------------
Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/portfolio', [PortfolioController::class, 'index'])->name('portfolio.index');
    Route::post('/portfolio/{portfolio}/submit', [PortfolioController::class, 'submit'])->name('portfolio.submit');
    Route::post('/portfolio/{portfolio}/evidence', [EvidenceController::class, 'store'])->name('evidence.store');
    Route::delete('/evidence/{evidence}', [EvidenceController::class, 'destroy'])->name('evidence.destroy');
});

// -----------------------------------------------------------------------------
// Faculty evaluator
// -----------------------------------------------------------------------------
Route::middleware(['auth', 'role:faculty,chair,admin'])->prefix('faculty')->name('faculty.')->group(function () {
    Route::get('/queue', [FacultyController::class, 'queue'])->name('queue');
    Route::get('/portfolios/{portfolio}', [FacultyController::class, 'review'])->name('review');
    Route::post('/portfolios/{portfolio}/decide', [FacultyController::class, 'decide'])->name('decide');
    Route::post('/evidence/{evidence}/rate', [EvidenceController::class, 'rate'])->name('evidence.rate');
});

// -----------------------------------------------------------------------------
// Program chair / OBE coordinator
// -----------------------------------------------------------------------------
Route::middleware(['auth', 'role:chair,admin'])->prefix('program')->name('chair.')->group(function () {
    Route::get('/dashboard', [ChairController::class, 'dashboard'])->name('dashboard');
    Route::get('/plo/{plo}', [ChairController::class, 'plo'])->name('plo');
    Route::get('/curriculum', [ChairController::class, 'curriculum'])->name('curriculum');
    Route::get('/cqi', [ChairController::class, 'cqi'])->name('cqi');
    Route::post('/cqi/generate', [ChairController::class, 'generateCqi'])->name('cqi.generate');
    Route::put('/cqi/{action}', [ChairController::class, 'updateCqi'])->name('cqi.update');
    Route::get('/students/{student}/profile', [ChairController::class, 'graduateProfile'])->name('graduate-profile');
});

// -----------------------------------------------------------------------------
// Administrator
// -----------------------------------------------------------------------------
Route::middleware(['auth', 'role:admin,chair'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/deadlines', [AdminController::class, 'deadlines'])->name('deadlines');
    Route::post('/deadlines/generate', [AdminController::class, 'generateDeadlines'])->name('deadlines.generate');
    Route::put('/deadlines/{deadline}', [AdminController::class, 'updateDeadline'])->name('deadlines.update');
    Route::delete('/deadlines/{deadline}', [AdminController::class, 'destroyDeadline'])->name('deadlines.destroy');
    Route::post('/academic-year/open', [AdminController::class, 'openYear'])->name('year.open');
});
