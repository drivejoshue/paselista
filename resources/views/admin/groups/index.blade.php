@extends('layouts.app')

@section('title', 'Grupos y horarios | PaseLista')
@section('section-label', 'Dirección')
@section('page-title', 'Grupos y horarios')

@section('content')
    @php
        $weekdayShort = [
            1 => 'L',
            2 => 'M',
            3 => 'X',
            4 => 'J',
            5 => 'V',
            6 => 'S',
            7 => 'D',
        ];
    @endphp

    @if(session('success'))
        <div class="alert alert-success">
            <i class="ti ti-circle-check me-2"></i>
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <i class="ti ti-alert-circle me-2"></i>
            {{ $errors->first() }}
        </div>
    @endif

    @if(!$activeCycle)
        <div class="alert alert-warning">
            <i class="ti ti-calendar-off me-2"></i>
            No existe un ciclo escolar activo. Activa un ciclo para configurar sus grupos.
        </div>
    @else
        <div class="alert alert-success">
            <i class="ti ti-calendar-check me-2"></i>
            Ciclo operativo:
            <strong>{{ $activeCycle->name }}</strong>
            ·
            {{ \Illuminate\Support\Carbon::parse($activeCycle->starts_on)->format('d/m/Y') }}
            al
            {{ \Illuminate\Support\Carbon::parse($activeCycle->ends_on)->format('d/m/Y') }}
        </div>
    @endif

    <div class="row row-cards mb-3">
        <div class="col-sm-6 col-xl">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-secondary">Grupos mostrados</div>
                    <div class="h1 mb-0">{{ $summary['total'] }}</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-secondary">Configuración correcta</div>
                    <div class="h1 mb-0 text-success">
                        {{ $summary['complete'] }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-secondary">Requieren revisión</div>
                    <div class="h1 mb-0 text-warning">
                        {{ $summary['warning'] }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-secondary">Sin horario activo</div>
                    <div class="h1 mb-0 text-danger">
                        {{ $summary['without_schedule'] }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="text-secondary">Tutor requerido</div>
                    <div class="h1 mb-0">
                        {{ $summary['guardian_required'] }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <form method="GET" action="{{ route('admin.groups.index') }}">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Filtros</h3>
                    <div class="text-secondary">
                        Localiza grupos y revisa su estado operativo.
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Buscar</label>
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <i class="ti ti-search"></i>
                            </span>

                            <input
                                type="text"
                                name="search"
                                value="{{ $filters['search'] }}"
                                class="form-control"
                                placeholder="Grupo, nivel, grado o plantel"
                            >
                        </div>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Plantel</label>
                        <select name="campus_id" class="form-select">
                            <option value="">Todos</option>

                            @foreach($campuses as $campus)
                                <option
                                    value="{{ $campus->id }}"
                                    @selected(
                                        (string) $filters['campus_id']
                                        === (string) $campus->id
                                    )
                                >
                                    {{ $campus->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Nivel</label>
                        <select name="level_id" class="form-select">
                            <option value="">Todos</option>

                            @foreach($levels as $level)
                                <option
                                    value="{{ $level->id }}"
                                    @selected(
                                        (string) $filters['level_id']
                                        === (string) $level->id
                                    )
                                >
                                    {{ $level->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Configuración</label>
                        <select name="configuration" class="form-select">
                            <option value="">Todas</option>
                            <option
                                value="complete"
                                @selected(
                                    $filters['configuration']
                                    === 'complete'
                                )
                            >
                                Correcta
                            </option>
                            <option
                                value="warning"
                                @selected(
                                    $filters['configuration']
                                    === 'warning'
                                )
                            >
                                Requiere revisión
                            </option>
                            <option
                                value="without_schedule"
                                @selected(
                                    $filters['configuration']
                                    === 'without_schedule'
                                )
                            >
                                Sin horario activo
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-between">
                <a
                    href="{{ route('admin.groups.index') }}"
                    class="btn btn-outline-secondary"
                >
                    <i class="ti ti-x me-1"></i>
                    Limpiar
                </a>

                <button class="btn btn-primary">
                    <i class="ti ti-filter me-1"></i>
                    Aplicar filtros
                </button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Grupos escolares</h3>
                <p class="card-subtitle">
                    Horarios, transición automática y requisito de tutor.
                </p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Grupo</th>
                        <th>Plantel y nivel</th>
                        <th>Alumnos</th>
                        <th>Horario semanal</th>
                        <th>Control de acceso</th>
                        <th>Estado</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($groups as $group)
                        <tr>
                            <td>
                                <div class="fw-bold">
                                    {{ $group->name }}
                                </div>

                                @if($group->grade_label)
                                    <div class="text-secondary small">
                                        Grado: {{ $group->grade_label }}
                                    </div>
                                @endif
                            </td>

                            <td>
                                <div>
                                    {{ $group->level_name ?? 'Sin nivel' }}
                                </div>

                                <div class="text-secondary small">
                                    {{ $group->campus_name ?? 'Sin plantel' }}
                                </div>
                            </td>

                            <td>
                                <span class="badge bg-blue-lt">
                                    {{ $group->students_count }} alumnos
                                </span>
                            </td>

                            <td style="min-width: 240px;">
                                <div class="d-flex flex-wrap gap-1 mb-2">
                                    @foreach($weekdayShort as $weekday => $short)
                                        <span
                                            class="badge {{
                                                in_array(
                                                    $weekday,
                                                    $group->active_weekdays,
                                                    true
                                                )
                                                    ? 'bg-blue-lt'
                                                    : 'bg-secondary-lt'
                                            }}"
                                            title="{{ $weekdays[$weekday] }}"
                                        >
                                            {{ $short }}
                                        </span>
                                    @endforeach
                                </div>

                                @if(
                                    $group->configuration_status
                                    === 'complete'
                                )
                                    <span class="badge bg-success-lt">
                                        <i class="ti ti-circle-check me-1"></i>
                                        {{ $group->active_schedules_count }}
                                        día(s) configurado(s)
                                    </span>
                                @elseif(
                                    $group->configuration_status
                                    === 'warning'
                                )
                                    <span class="badge bg-warning-lt">
                                        <i class="ti ti-alert-triangle me-1"></i>
                                        Requiere revisión
                                    </span>

                                    @if($group->invalid_weekdays)
                                        <div class="text-warning small mt-1">
                                            Horario o transición inválida
                                        </div>
                                    @endif

                                    @if($group->duplicate_weekdays)
                                        <div class="text-danger small">
                                            Días duplicados detectados
                                        </div>
                                    @endif
                                @else
                                    <span class="badge bg-danger-lt">
                                        <i class="ti ti-calendar-off me-1"></i>
                                        Sin horario activo
                                    </span>
                                @endif
                            </td>

                            <td>
                                @if($group->requires_guardian_scan)
                                    <span class="badge bg-purple-lt">
                                        <i class="ti ti-user-shield me-1"></i>
                                        Tutor requerido
                                    </span>
                                @else
                                    <span class="badge bg-secondary-lt">
                                        Tutor opcional
                                    </span>
                                @endif

                                <div class="text-secondary small mt-1">
                                    Modo salida:
                                    {{ $group->auto_transition_minutes ?? 30 }}
                                    min antes
                                </div>
                            </td>

                            <td>
                                @if($group->status === 'active')
                                    <span class="badge bg-success-lt">
                                        Activo
                                    </span>
                                @else
                                    <span class="badge bg-secondary-lt">
                                        {{ ucfirst($group->status) }}
                                    </span>
                                @endif
                            </td>

                            <td>
                                <a
                                    href="{{ route(
                                        'admin.groups.schedules.edit',
                                        $group->id
                                    ) }}"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    <i class="ti ti-clock-cog me-1"></i>
                                    Configurar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="7"
                                class="text-center text-secondary py-5"
                            >
                                No hay grupos con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
