<?php

use App\Http\Controllers\Sysadmin\AiAuditController;
use App\Http\Controllers\Sysadmin\AiManagementController;
use App\Http\Middleware\EnsureSuperadmin;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    EnsureSuperadmin::class,
])
    ->prefix('sysadmin/ai')
    ->name('sysadmin.ai.')
    ->group(function (): void {
        Route::get(
            '/',
            [
                AiManagementController::class,
                'index',
            ]
        )->name('index');

        Route::get(
            '/audit',
            [
                AiAuditController::class,
                'index',
            ]
        )->name('audit.index');

        Route::get(
            '/audit/{run}',
            [
                AiAuditController::class,
                'show',
            ]
        )
            ->whereNumber('run')
            ->name('audit.show');

        Route::get(
            '/schools/{school}',
            [
                AiManagementController::class,
                'show',
            ]
        )->name('schools.show');

        Route::put(
            '/schools/{school}',
            [
                AiManagementController::class,
                'update',
            ]
        )->name('schools.update');

        Route::post(
            '/schools/{school}/reset-usage',
            [
                AiManagementController::class,
                'resetUsage',
            ]
        )->name('schools.reset-usage');
    });