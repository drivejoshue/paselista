<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\SchoolCore\AcademicGradeExportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix(
        'admin/schoolcore/academic/grades/export'
    )
    ->name('academic.grades.export.')
    ->group(function (): void {
        Route::post(
            '/summary',
            [
                AcademicGradeExportController::class,
                'summary',
            ]
        )->name('summary');

        Route::get(
            '/books/{book}',
            [
                AcademicGradeExportController::class,
                'book',
            ]
        )
            ->whereNumber('book')
            ->name('book');
    });
