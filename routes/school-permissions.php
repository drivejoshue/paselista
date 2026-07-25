<?php

use App\Http\Controllers\Admin\RolePermissionController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    'role:school_admin,director',
    'school.license',
])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get(
            '/role-permissions',
            [
                RolePermissionController::class,
                'index',
            ]
        )->name('role-permissions.index');
    });
