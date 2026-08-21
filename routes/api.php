<?php

use App\Http\Controllers\Api\AppVersionController;
use App\Http\Controllers\Api\V1\AccessScanController;
use App\Http\Controllers\Api\V1\AdminSchoolNoticeController;
use App\Http\Controllers\Api\V1\AppConfigController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\FamilyAccessController;
use App\Http\Controllers\Api\V1\FamilyController;
use App\Http\Controllers\Api\V1\FamilyDeviceController;
use App\Http\Controllers\Api\V1\FamilyNoticeController;
use App\Http\Controllers\Api\V1\GuardianController;
use App\Http\Controllers\Api\V1\StaffLiveController;
use Illuminate\Support\Facades\Route;

Route::get(
    '/v1/app/version',
    AppVersionController::class,
);

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function (): void {

        /*
        |--------------------------------------------------------------------------
        | Autenticación pública
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/auth/login',
            [
                AuthController::class,
                'login',
            ],
        )->name('auth.login');

        /*
        |--------------------------------------------------------------------------
        | API autenticada
        |--------------------------------------------------------------------------
        */

        Route::middleware('auth:sanctum')
            ->group(function (): void {

                /*
                |--------------------------------------------------------------------------
                | Sesión
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/me',
                    [
                        AuthController::class,
                        'me',
                    ],
                )->name('me');

                Route::post(
                    '/auth/logout',
                    [
                        AuthController::class,
                        'logout',
                    ],
                )->name('auth.logout');

                Route::post(
                    '/auth/change-password',
                    [
                        AuthController::class,
                        'changePassword',
                    ],
                )->name('auth.change-password');

                Route::get(
                    '/app/config',
                    [
                        AppConfigController::class,
                        'show',
                    ],
                )->name('app.config');

                /*
                |--------------------------------------------------------------------------
                | Staff / control de acceso
                |--------------------------------------------------------------------------
                */

                Route::prefix('access')
                    ->middleware([
                        'role:superadmin,school_admin,director,prefect,kiosk',
                        'school.license',
                    ])
                    ->name('access.')
                    ->group(function (): void {
                        Route::get(
                            '/bootstrap',
                            [
                                AccessScanController::class,
                                'bootstrap',
                            ],
                        )->name('bootstrap');

                        Route::get(
                            '/live/today',
                            [
                                StaffLiveController::class,
                                'today',
                            ],
                        )->name('live.today');

                        Route::post(
                            '/scan',
                            [
                                AccessScanController::class,
                                'scan',
                            ],
                        )->name('scan');

                        Route::post(
                            '/guardian/confirm',
                            [
                                AccessScanController::class,
                                'guardianConfirm',
                            ],
                        )->name('guardian.confirm');

                        Route::get(
                            '/recent',
                            [
                                AccessScanController::class,
                                'recent',
                            ],
                        )->name('recent');

                        Route::get(
                            '/students/search',
                            [
                                AccessScanController::class,
                                'searchStudents',
                            ],
                        )->name('students.search');

                        Route::get(
                            '/students/{student}/guardians',
                            [
                                AccessScanController::class,
                                'studentGuardians',
                            ],
                        )
                            ->whereNumber('student')
                            ->name('students.guardians');

                        Route::post(
                            '/manual',
                            [
                                AccessScanController::class,
                                'manual',
                            ],
                        )->name('manual');
                    });

                /*
                |--------------------------------------------------------------------------
                | Family
                |--------------------------------------------------------------------------
                */

                Route::prefix('family')
                    ->middleware([
                        'role:guardian',
                        'ability:family:read',
                        'school.license',
                    ])
                    ->name('family.')
                    ->group(function (): void {

                        /*
                        |--------------------------------------------------------------------------
                        | Disponible sin membresía
                        |--------------------------------------------------------------------------
                        |
                        | El tutor debe poder completar su identidad antes de comprar.
                        |
                        */

                        Route::get(
                            '/access',
                            FamilyAccessController::class,
                        )->name('access');

                        Route::get(
                            '/me',
                            [
                                FamilyController::class,
                                'me',
                            ],
                        )->name('me');

                        Route::post(
                            '/profile/photo',
                            [
                                FamilyController::class,
                                'uploadPhoto',
                            ],
                        )
                            ->middleware('throttle:6,1')
                            ->name('profile.photo');

                        /*
                        |--------------------------------------------------------------------------
                        | Dispositivos
                        |--------------------------------------------------------------------------
                        */

                        Route::prefix('devices')
                            ->middleware(
                                'ability:family:devices',
                            )
                            ->name('devices.')
                            ->group(function (): void {
                                Route::post(
                                    '/',
                                    [
                                        FamilyDeviceController::class,
                                        'store',
                                    ],
                                )->name('store');

                                Route::patch(
                                    '/current/preferences',
                                    [
                                        FamilyDeviceController::class,
                                        'preferences',
                                    ],
                                )->name('preferences');

                                Route::delete(
                                    '/current',
                                    [
                                        FamilyDeviceController::class,
                                        'destroyCurrent',
                                    ],
                                )->name(
                                    'destroy-current',
                                );
                            });

                        /*
                        |--------------------------------------------------------------------------
                        | Requiere acceso Family
                        |--------------------------------------------------------------------------
                        */

                        Route::middleware('family.access')
                            ->group(function (): void {
                                Route::get(
                                    '/credential',
                                    [
                                        FamilyController::class,
                                        'guardianCredential',
                                    ],
                                )->name('credential');

                                Route::get(
                                    '/students',
                                    [
                                        FamilyController::class,
                                        'students',
                                    ],
                                )->name('students');

                                Route::get(
                                    '/students/{student}/attendance',
                                    [
                                        FamilyController::class,
                                        'attendance',
                                    ],
                                )
                                    ->whereNumber('student')
                                    ->name(
                                        'students.attendance',
                                    );

                                Route::get(
                                    '/students/{student}/credential',
                                    [
                                        FamilyController::class,
                                        'credential',
                                    ],
                                )
                                    ->whereNumber('student')
                                    ->name(
                                        'students.credential',
                                    );

                                Route::get(
                                    '/notifications',
                                    [
                                        FamilyController::class,
                                        'notifications',
                                    ],
                                )->name('notifications');

                                Route::patch(
                                    '/notifications/{notification}/read',
                                    [
                                        FamilyController::class,
                                        'markNotificationAsRead',
                                    ],
                                )
                                    ->whereNumber(
                                        'notification',
                                    )
                                    ->name(
                                        'notifications.read',
                                    );

                                Route::prefix('notices')
                                    ->name('notices.')
                                    ->group(function (): void {
                                        Route::get(
                                            '/',
                                            [
                                                FamilyNoticeController::class,
                                                'index',
                                            ],
                                        )->name('index');

                                        Route::get(
                                            '/modal',
                                            [
                                                FamilyNoticeController::class,
                                                'modal',
                                            ],
                                        )->name('modal');

                                        Route::get(
                                            '/{notice}',
                                            [
                                                FamilyNoticeController::class,
                                                'show',
                                            ],
                                        )
                                            ->whereNumber(
                                                'notice',
                                            )
                                            ->name('show');

                                        Route::patch(
                                            '/{notice}/read',
                                            [
                                                FamilyNoticeController::class,
                                                'markAsRead',
                                            ],
                                        )
                                            ->whereNumber(
                                                'notice',
                                            )
                                            ->name('read');

                                        Route::patch(
                                            '/{notice}/acknowledge',
                                            [
                                                FamilyNoticeController::class,
                                                'acknowledge',
                                            ],
                                        )
                                            ->whereNumber(
                                                'notice',
                                            )
                                            ->name(
                                                'acknowledge',
                                            );
                                    });
                            });
                    });

                /*
                |--------------------------------------------------------------------------
                | Avisos para administración y dirección
                |--------------------------------------------------------------------------
                */

                Route::prefix('admin/notices')
                    ->middleware([
                        'role:superadmin,school_admin,director',
                        'school.license',
                    ])
                    ->name('admin.notices.')
                    ->group(function (): void {
                        Route::get(
                            '/',
                            [
                                AdminSchoolNoticeController::class,
                                'index',
                            ],
                        )->name('index');

                        Route::post(
                            '/',
                            [
                                AdminSchoolNoticeController::class,
                                'store',
                            ],
                        )->name('store');

                        Route::get(
                            '/{notice}',
                            [
                                AdminSchoolNoticeController::class,
                                'show',
                            ],
                        )
                            ->whereNumber('notice')
                            ->name('show');

                        Route::post(
                            '/{notice}',
                            [
                                AdminSchoolNoticeController::class,
                                'update',
                            ],
                        )
                            ->whereNumber('notice')
                            ->name('update');

                        Route::post(
                            '/{notice}/publish',
                            [
                                AdminSchoolNoticeController::class,
                                'publish',
                            ],
                        )
                            ->whereNumber('notice')
                            ->name('publish');

                        Route::post(
                            '/{notice}/archive',
                            [
                                AdminSchoolNoticeController::class,
                                'archive',
                            ],
                        )
                            ->whereNumber('notice')
                            ->name('archive');
                    });

                /*
                |--------------------------------------------------------------------------
                | API legacy de tutores
                |--------------------------------------------------------------------------
                */

                Route::prefix('guardian')
                    ->middleware([
                        'role:guardian',
                        'school.license',
                    ])
                    ->name('guardian.')
                    ->group(function (): void {
                        Route::get(
                            '/students',
                            [
                                GuardianController::class,
                                'students',
                            ],
                        )->name('students');

                        Route::get(
                            '/students/{student}/attendance',
                            [
                                GuardianController::class,
                                'attendance',
                            ],
                        )
                            ->whereNumber('student')
                            ->name('students.attendance');

                        Route::get(
                            '/notifications',
                            [
                                GuardianController::class,
                                'notifications',
                            ],
                        )->name('notifications');

                        Route::patch(
                            '/notifications/{notification}/read',
                            [
                                GuardianController::class,
                                'markNotificationAsRead',
                            ],
                        )
                            ->whereNumber(
                                'notification',
                            )
                            ->name(
                                'notifications.read',
                            );
                    });
            });
    });

/*
|--------------------------------------------------------------------------
| Google Play Family
|--------------------------------------------------------------------------
*/

require __DIR__.'/family_google_play.php';