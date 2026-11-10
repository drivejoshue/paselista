<?php

declare(strict_types=1);

use App\Http\Controllers\Academic\AcademicGradePortalController;
use App\Http\Controllers\Admin\SchoolCore\AcademicGradePortalAdminController;
use App\Http\Controllers\PublicPortal\SchoolPublicPortalController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('admin/schoolcore/academic/grade-portal')
    ->name('academic.grade-portal.')
    ->group(function (): void {
        Route::get('/', [AcademicGradePortalAdminController::class, 'index'])->name('index');
        Route::post('/settings', [AcademicGradePortalAdminController::class, 'settings'])->name('settings');

    });

Route::prefix('consulta/{school}')
    ->name('academic.public-grades.')
    ->group(function (): void {
        Route::get('/', [AcademicGradePortalController::class, 'show'])->name('show');
        Route::post('/acceder', [AcademicGradePortalController::class, 'unlock'])->name('unlock');
        Route::get('/resultados', [AcademicGradePortalController::class, 'results'])->name('results');
        Route::get('/pdf', [AcademicGradePortalController::class, 'pdf'])->name('pdf');
        Route::post('/salir', [AcademicGradePortalController::class, 'lock'])->name('lock');
    });

    Route::get(
    '/portal/{school}',
    [SchoolPublicPortalController::class, 'show']
)->name('school.portal.show');

Route::get(
    '/portal/{school}/calificaciones',
    [SchoolPublicPortalController::class, 'show']
)->name('school.portal.grades');
