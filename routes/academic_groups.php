<?php
declare(strict_types=1);

use App\Http\Controllers\Admin\SchoolCore\AcademicGroupsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:school_admin,director', 'school.license'])
    ->get('/admin/schoolcore/academic/groups', [AcademicGroupsController::class, 'index'])
    ->name('admin.schoolcore.academic.groups.index');

Route::middleware(['auth', 'role:school_admin,director', 'school.license'])
    ->post('/admin/schoolcore/academic/groups/assign', [AcademicGroupsController::class, 'assign'])
    ->name('admin.schoolcore.academic.groups.assign');
