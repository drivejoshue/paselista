<?php
declare(strict_types=1);

use App\Http\Controllers\Admin\SchoolCore\AcademicCatalogController;
use Illuminate\Support\Facades\Route;

// Cargar este archivo UNA SOLA VEZ desde routes/web.php.
Route::middleware(['auth','role:school_admin,director','school.license'])
    ->prefix('admin/schoolcore/academic/catalog')
    ->name('admin.schoolcore.academic.catalog.')
    ->group(function (): void {
        Route::get('/', [AcademicCatalogController::class,'index'])->name('index');
        Route::post('/teachers', [AcademicCatalogController::class,'storeTeacher'])->name('teachers.store');
        Route::post('/subjects', [AcademicCatalogController::class,'storeSubject'])->name('subjects.store');
        Route::post('/plans', [AcademicCatalogController::class,'storePlan'])->name('plans.store');
        Route::post('/plans/{plan}/subjects', [AcademicCatalogController::class,'attachSubject'])->name('plans.subjects.store');
    });
