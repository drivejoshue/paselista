<?php
declare(strict_types=1);

use App\Http\Controllers\Academic\AcademicManagementController;
use App\Http\Controllers\SchoolCore\AcademicDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    'role:school_admin,director',
    'school.license',
    'school.module:academic',
])
    ->prefix('admin/academic')
    ->name('admin.academic.')
    ->group(function (): void {
        // Lectura: administración y dirección.
        Route::get(
            '/',
            [AcademicDashboardController::class, 'index']
        )->name('index');

        Route::get(
            '/data',
            [AcademicManagementController::class, 'data']
        )->name('data');



Route::get(
    '/workflow',
    [
        \App\Http\Controllers\Academic\AcademicWorkflowController::class,
        'catalog',
    ]
)->name('workflow.index');

Route::get(
    '/gradebooks/{gradebook}/roster',
    [
        \App\Http\Controllers\Academic\AcademicWorkflowController::class,
        'roster',
    ]
)
    ->whereNumber('gradebook')
    ->name('gradebooks.roster');


        // Escritura inicial: administrador escolar.
        Route::middleware('role:school_admin')
            ->group(function (): void {
                Route::post(
                    '/teachers',
                    [AcademicManagementController::class, 'storeTeacher']
                )->name('teachers.store');

                Route::patch(
                    '/teachers/{teacher}',
                    [AcademicManagementController::class, 'updateTeacher']
                )
                    ->whereNumber('teacher')
                    ->name('teachers.update');

                Route::post(
                    '/subjects',
                    [AcademicManagementController::class, 'storeSubject']
                )->name('subjects.store');

                Route::patch(
                    '/subjects/{subject}',
                    [AcademicManagementController::class, 'updateSubject']
                )
                    ->whereNumber('subject')
                    ->name('subjects.update');

                Route::post(
                    '/periods',
                    [AcademicManagementController::class, 'storePeriod']
                )->name('periods.store');

                Route::patch(
                    '/periods/{period}',
                    [AcademicManagementController::class, 'updatePeriod']
                )
                    ->whereNumber('period')
                    ->name('periods.update');



Route::post(
    '/plans',
    [
        \App\Http\Controllers\Academic\AcademicWorkflowController::class,
        'storePlan',
    ]
)->name('plans.store');

Route::post(
    '/plan-subjects',
    [
        \App\Http\Controllers\Academic\AcademicWorkflowController::class,
        'storePlanSubject',
    ]
)->name('plan-subjects.store');

Route::patch(
    '/plans/{plan}/activate',
    [
        \App\Http\Controllers\Academic\AcademicWorkflowController::class,
        'activatePlan',
    ]
)
    ->whereNumber('plan')
    ->name('plans.activate');

Route::post(
    '/assignments',
    [
        \App\Http\Controllers\Academic\AcademicWorkflowController::class,
        'storeAssignment',
    ]
)->name('assignments.store');

Route::patch(
    '/periods/{period}/activate',
    [
        \App\Http\Controllers\Academic\AcademicWorkflowController::class,
        'activatePeriod',
    ]
)
    ->whereNumber('period')
    ->name('periods.activate');

Route::post(
    '/gradebooks',
    [
        \App\Http\Controllers\Academic\AcademicWorkflowController::class,
        'storeGradebook',
    ]
)->name('gradebooks.store');

Route::put(
    '/gradebooks/{gradebook}/grades/{enrollment}',
    [
        \App\Http\Controllers\Academic\AcademicWorkflowController::class,
        'saveGrade',
    ]
)
    ->whereNumber('gradebook')
    ->whereNumber('enrollment')
    ->name('grades.update');

            });
    });
