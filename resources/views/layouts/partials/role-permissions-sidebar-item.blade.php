@php
    $schoolRole = auth()->user()?->role;

    $canViewRolePermissions = in_array(
        $schoolRole,
        [
            'school_admin',
            'director',
        ],
        true
    );
@endphp

@if(
    $canViewRolePermissions
    && \Illuminate\Support\Facades\Route::has(
        'admin.role-permissions.index'
    )
)
    <li class="nav-item">
        <a
            class="nav-link {{
                request()->routeIs(
                    'admin.role-permissions.*'
                )
                    ? 'active'
                    : ''
            }}"
            href="{{ route(
                'admin.role-permissions.index'
            ) }}"
        >
            <span class="nav-link-icon d-md-none d-lg-inline-block">
                <i class="ti ti-shield-check"></i>
            </span>

            <span class="nav-link-title">
                Permisos por rol
            </span>
        </a>
    </li>
@endif
