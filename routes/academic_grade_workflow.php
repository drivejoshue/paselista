<?php

declare(strict_types=1);

use App\Http\Controllers\Academic\GradeWorkflowController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {

    /*
    |--------------------------------------------------------------------------
    | PORTAL DOCENTE
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/teacher/grades',
        [GradeWorkflowController::class, 'teacherIndex']
    )->name('academic.grades.teacher.index');

    Route::get(
        '/teacher/grades/{assignment}/{period}',
        [GradeWorkflowController::class, 'teacher']
    )->name('academic.grades.teacher');

    Route::post(
        '/teacher/grades/{assignment}/{period}',
        [GradeWorkflowController::class, 'teacherSave']
    )->name('academic.grades.teacher.save');

    Route::post(
        '/teacher/grades/{assignment}/{period}/submit',
        [GradeWorkflowController::class, 'submit']
    )->name('academic.grades.teacher.submit');


    Route::get(
    '/teacher/grades/{assignment}/{period}/pdf',
    [GradeWorkflowController::class, 'teacherPdf']
)->whereNumber('assignment')
 ->whereNumber('period')
 ->name('academic.grades.teacher.pdf');


    /*
    |--------------------------------------------------------------------------
    | DIRECCIÓN - CALIFICACIONES
    |--------------------------------------------------------------------------
    */

    // Vista administrativa existente
    Route::get(
        '/admin/schoolcore/academic/grades',
        [GradeWorkflowController::class, 'director']
    )->name('academic.grades.director');


    /*
    |--------------------------------------------------------------------------
    | ENDPOINTS JSON PARA REACT
    | Deben declararse antes de /{book}.
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/admin/schoolcore/academic/grades/data',
        [GradeWorkflowController::class, 'directorData']
    )->name('academic.grades.data');

    Route::get(
        '/admin/schoolcore/academic/grades/data/{book}',
        [GradeWorkflowController::class, 'reviewData']
    )->whereNumber('book')
     ->name('academic.grades.data.review');


    /*
    |--------------------------------------------------------------------------
    | OPERACIONES ADMINISTRATIVAS
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/admin/schoolcore/academic/grades/publish-all',
        [GradeWorkflowController::class, 'publishAll']
    )->name('academic.grades.publish-all');

    Route::post(
        '/admin/schoolcore/academic/grades/periods',
        [GradeWorkflowController::class, 'createPeriod']
    )->name('academic.grades.periods.store');

    // Revisión tradicional: únicamente IDs numéricos.
    Route::get(
        '/admin/schoolcore/academic/grades/{book}',
        [GradeWorkflowController::class, 'review']
    )->whereNumber('book')
     ->name('academic.grades.review');

    Route::post(
        '/admin/schoolcore/academic/grades/{book}/publish',
        [GradeWorkflowController::class, 'publish']
    )->whereNumber('book')
     ->name('academic.grades.publish');

    Route::post(
        '/admin/schoolcore/academic/grades/{book}/{grade}/correct',
        [GradeWorkflowController::class, 'correct']
    )->whereNumber('book')
     ->whereNumber('grade')
     ->name('academic.grades.correct');



});
