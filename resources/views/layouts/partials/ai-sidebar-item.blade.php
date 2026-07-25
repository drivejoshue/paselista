@php
    $canUseSchoolPassAi = in_array(
        auth()->user()?->role,
        ['superadmin', 'school_admin', 'director'],
        true
    );
@endphp

@if(
    $canUseSchoolPassAi
    && \Illuminate\Support\Facades\Route::has('admin.ai.index')
)
    <li class="nav-item">
        <a
            class="nav-link {{ request()->routeIs('admin.ai.*') ? 'active' : '' }}"
            href="{{ route('admin.ai.index') }}"
        >
            <span class="nav-link-icon d-md-none d-lg-inline-block">
                <i class="ti ti-brain"></i>
            </span>

            <span class="nav-link-title">
                PaseLista IA
            </span>
        </a>
    </li>
@endif
