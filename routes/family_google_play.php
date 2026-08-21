<?php

use App\Http\Controllers\Api\V1\FamilyGooglePlayController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/family/google-play')
    ->middleware([
        'auth:sanctum',
        'role:guardian',
        'ability:family:read',
        'school.license',
    ])
    ->name('api.v1.family.google-play.')
    ->group(function (): void {
        Route::get(
            '/config',
            [
                FamilyGooglePlayController::class,
                'configuration',
            ]
        )->name('config');

        Route::post(
            '/verify',
            [
                FamilyGooglePlayController::class,
                'verify',
            ]
        )
            ->middleware('throttle:10,1')
            ->name('verify');

        Route::post(
            '/restore',
            [
                FamilyGooglePlayController::class,
                'restore',
            ]
        )
            ->middleware('throttle:10,1')
            ->name('restore');
    });