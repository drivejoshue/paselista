<?php

use App\Http\Controllers\PrivacyRequestController;
use App\Http\Controllers\PublicSiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicSiteController::class, 'home'])
    ->name('public.home');

Route::get('/privacidad', [PublicSiteController::class, 'privacy'])
    ->name('public.privacy');

Route::get(
    '/eliminacion-de-datos',
    [PublicSiteController::class, 'dataDeletion']
)->name('public.data-deletion');

Route::get('/soporte', [PublicSiteController::class, 'support'])
    ->name('public.support');

Route::post(
    '/solicitudes-de-privacidad',
    [PrivacyRequestController::class, 'store']
)
    ->middleware('throttle:5,1')
    ->name('public.privacy-requests.store');
