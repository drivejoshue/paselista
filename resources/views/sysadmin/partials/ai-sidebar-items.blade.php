<li class="nav-item">
    <a
        class="nav-link {{
            request()->routeIs('sysadmin.ai.index')
            || request()->routeIs('sysadmin.ai.schools.*')
                ? 'active'
                : ''
        }}"
        href="{{ route('sysadmin.ai.index') }}"
    >
        <span class="nav-link-icon d-md-none d-lg-inline-block">
            <i class="ti ti-brain"></i>
        </span>
        <span class="nav-link-title">Control de IA</span>
    </a>
</li>

<li class="nav-item">
    <a
        class="nav-link {{
            request()->routeIs('sysadmin.ai.audit.*')
                ? 'active'
                : ''
        }}"
        href="{{ route('sysadmin.ai.audit.index') }}"
    >
        <span class="nav-link-icon d-md-none d-lg-inline-block">
            <i class="ti ti-shield-search"></i>
        </span>
        <span class="nav-link-title">Auditoría de IA</span>
    </a>
</li>
