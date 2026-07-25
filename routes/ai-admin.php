<?php

use App\Http\Controllers\Admin\Ai\AiController;
use App\Http\Controllers\Admin\Ai\AiConversationController;
use App\Http\Controllers\Admin\Ai\AiChartController;
use App\Http\Controllers\Admin\Ai\AiExportController;
use App\Http\Middleware\EnsureAiAccess;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    'school.license',
    EnsureAiAccess::class,
])
    ->prefix('admin/ai')
    ->name('admin.ai.')
    ->group(function (): void {
        Route::get('/', [AiController::class, 'index'])
            ->name('index');

        Route::post('/analyze', [AiController::class, 'analyze'])
            ->middleware('throttle:20,1')
            ->name('analyze');

        Route::get('/runs/{run}', [AiController::class, 'show'])
            ->whereNumber('run')
            ->name('runs.show');

        Route::get('/runs/{run}/status', [AiController::class, 'status'])
            ->whereNumber('run')
            ->middleware('throttle:120,1')
            ->name('runs.status');

        Route::post('/runs/{run}/charts', [AiChartController::class, 'store'])
            ->whereNumber('run')
            ->middleware('throttle:30,1')
            ->name('runs.charts.store');

        Route::get('/runs/{run}/print', [AiExportController::class, 'print'])
            ->whereNumber('run')
            ->name('runs.print');

        Route::get('/runs/{run}/pdf', [AiExportController::class, 'pdf'])
            ->whereNumber('run')
            ->name('runs.pdf');

        Route::get('/conversations', [AiConversationController::class, 'index'])
            ->name('conversations.index');

        Route::post('/conversations', [AiConversationController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('conversations.store');

        Route::get('/conversations/{conversation}', [AiConversationController::class, 'show'])
            ->whereNumber('conversation')
            ->name('conversations.show');

        Route::patch('/conversations/{conversation}', [AiConversationController::class, 'rename'])
            ->whereNumber('conversation')
            ->name('conversations.rename');

        Route::post('/conversations/{conversation}/archive', [AiConversationController::class, 'archive'])
            ->whereNumber('conversation')
            ->name('conversations.archive');

        Route::delete('/conversations/{conversation}', [AiConversationController::class, 'destroy'])
            ->whereNumber('conversation')
            ->name('conversations.destroy');
    });
