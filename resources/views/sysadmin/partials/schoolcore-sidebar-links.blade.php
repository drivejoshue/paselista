{{-- SchoolCore ERP: links only to registered route names. Access remains protected by sysadmin middleware. --}}
<li class="nav-item"><div class="sp-sidebar-section">SchoolCore ERP</div></li>
@if(\Illuminate\Support\Facades\Route::has('sysadmin.modules.index'))
<li class="nav-item"><a href="{{ route('sysadmin.modules.index') }}" class="nav-link {{ request()->routeIs('sysadmin.modules.*') ? 'active' : '' }}"><span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-apps"></i></span><span class="nav-link-title">Catálogo y precios</span></a></li>
@endif
@if(\Illuminate\Support\Facades\Route::has('sysadmin.plans.index'))
<li class="nav-item"><a href="{{ route('sysadmin.plans.index') }}" class="nav-link {{ request()->routeIs('sysadmin.plans.*') ? 'active' : '' }}"><span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-package"></i></span><span class="nav-link-title">Planes comerciales</span></a></li>
@endif
<li class="nav-item"><a href="{{ route('sysadmin.schools.index') }}" class="nav-link {{ request()->routeIs('sysadmin.schools.index') ? 'active' : '' }}"><span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-school"></i></span><span class="nav-link-title">Asignar a escuelas</span></a></li>
@php $selectedSchool = request()->route('school'); @endphp
@if($selectedSchool && is_numeric($selectedSchool) && \Illuminate\Support\Facades\Route::has('sysadmin.schools.modules.index'))
<li class="nav-item"><a href="{{ route('sysadmin.schools.modules.index', ['school' => $selectedSchool]) }}" class="nav-link {{ request()->routeIs('sysadmin.schools.modules.*') || request()->routeIs('sysadmin.schools.contracts.*') ? 'active' : '' }}"><span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-adjustments"></i></span><span class="nav-link-title">Módulos de escuela #{{ $selectedSchool }}</span></a></li>
@endif
