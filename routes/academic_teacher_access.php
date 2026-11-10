<?php
declare(strict_types=1);

use App\Http\Controllers\Admin\SchoolCore\AcademicCatalogController;
use App\Http\Controllers\Teacher\TeacherAccessController;
use Illuminate\Support\Facades\Route;

// Incluido desde routes/web.php. El controlador comprueba además el colegio, el módulo y el perfil.
Route::middleware(['auth','role:school_admin,director','school.license'])
    ->post('/admin/schoolcore/academic/catalog/teachers/{teacher}/password',
        [AcademicCatalogController::class,'resetTeacherPassword'])
    ->name('admin.schoolcore.academic.catalog.teachers.password');

Route::middleware('auth')->prefix('teacher')->name('teacher.')->group(function (): void {
    Route::get('/change-password',[TeacherAccessController::class,'changeForm'])->name('password.change');
    Route::post('/change-password',[TeacherAccessController::class,'change'])->name('password.update')->middleware('throttle:6,1');
    Route::get('/dashboard',[TeacherAccessController::class,'dashboard'])->name('dashboard');
    Route::get('/assignments/{assignment}/students',[TeacherAccessController::class,'students'])->whereNumber('assignment')->name('assignments.students');
    Route::post(
    '/preferences/grade-input',
    [TeacherAccessController::class, 'updateGradeInputPreference']
)->middleware('throttle:20,1')
 ->name('preferences.grade-input');
});
