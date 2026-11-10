<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\SchoolCore\AcademicPlanManagerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('admin/schoolcore/academic/plan-manager')
    ->group(function (): void {
        Route::get(
            '/',
            [AcademicPlanManagerController::class, 'index']
        )->name('academic.plan-manager.index');

        Route::post(
            '/manage',
            [AcademicPlanManagerController::class, 'manage']
        )->name('academic.plan-manager.manage');
    });
