<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Authorization\SchoolRolePolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class RolePermissionController extends Controller
{
    public function __construct(
        private readonly SchoolRolePolicy $policy
    ) {
    }

    public function index(
        Request $request
    ): View {
        $user = $request->user();

        abort_unless(
            $user
            && in_array(
                $user->role,
                [
                    'school_admin',
                    'director',
                ],
                true
            ),
            403
        );

        return view(
            'admin.roles.permissions',
            [
                'matrix' =>
                    $this->policy->matrix(),

                'roles' => config(
                    'school_permissions.roles',
                    []
                ),

                'currentRole' =>
                    (string) $user->role,
            ]
        );
    }
}
