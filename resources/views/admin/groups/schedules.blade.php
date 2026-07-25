@extends('layouts.app')

@section('title', 'Horarios | PaseLista')
@section('section-label', 'Dirección')
@section('page-title', 'Horarios de grupo')

@section('topbar-actions')
    <div class="btn-list">
        <a
            href="{{ route('admin.groups.index') }}"
            class="btn btn-outline-secondary btn-sm"
        >
            <i class="ti ti-arrow-left me-1"></i>
            Grupos
        </a>

        <a
            href="{{ route(
                'admin.students.index',
                ['search' => $groupRow->name]
            ) }}"
            class="btn btn-outline-primary btn-sm"
        >
            <i class="ti ti-users me-1"></i>
            Ver alumnos
        </a>
    </div>
@endsection

@section('content')
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

    @php
        $activeFromOld = old('active_weekdays');

        $timeValue = function (
            string $field,
            int $weekday,
            string $default
        ) use ($schedules): string {
            $oldValue = old($field.'.'.$weekday);

            if ($oldValue !== null) {
                return (string) $oldValue;
            }

            $schedule = $schedules->get($weekday);

            if (
                $schedule
                && ! empty($schedule->{$field})
            ) {
                return substr(
                    (string) $schedule->{$field},
                    0,
                    5
                );
            }

            return $default;
        };

        $isActive = function (
            int $weekday
        ) use (
            $schedules,
            $activeFromOld
        ): bool {
            if (is_array($activeFromOld)) {
                return in_array(
                    (string) $weekday,
                    $activeFromOld,
                    true
                ) || in_array(
                    $weekday,
                    $activeFromOld,
                    true
                );
            }

            $schedule = $schedules->get($weekday);

            return $schedule
                && $schedule->status === 'active';
        };

        $activeDaysCount = collect($weekdays)
            ->keys()
            ->filter(
                fn ($weekday): bool =>
                    $isActive((int) $weekday)
            )
            ->count();
    @endphp

    <div class="alert alert-success">
        <i class="ti ti-calendar-check me-2"></i>
        Ciclo:
        <strong>{{ $activeCycle->name }}</strong>
        · Grupo:
        <strong>
            {{ $groupRow->level_name ?? 'Sin nivel' }}
            · {{ $groupRow->name }}
        </strong>
        · Plantel:
        <strong>{{ $groupRow->campus_name ?? 'Sin plantel' }}</strong>
    </div>

    @if($diagnostics['duplicate_weekdays'])
        <div class="alert alert-warning">
            <i class="ti ti-database-exclamation me-2"></i>
            Se detectaron horarios duplicados en algunos días.
            Al guardar, PaseLista conservará la fila más reciente y eliminará las copias.
        </div>
    @endif

    @if($diagnostics['invalid_weekdays'])
        <div class="alert alert-warning">
            <i class="ti ti-alert-triangle me-2"></i>
            Hay días activos con secuencia o transición automática inválida.
            Corrige los horarios antes de guardar.
        </div>
    @endif

    <form
        method="POST"
        action="{{ route(
            'admin.groups.schedules.update',
            $groupRow->id
        ) }}"
        id="schedule-form"
    >
        @csrf
        @method('PUT')

        <div class="row row-cards">
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-body text-center">
                        <span class="avatar avatar-xl bg-blue-lt mb-3">
                            <i class="ti ti-users-group fs-1"></i>
                        </span>

                        <h2 class="mb-1">
                            {{ $groupRow->name }}
                        </h2>

                        <div class="text-secondary">
                            {{ $groupRow->level_name ?? 'Sin nivel' }}
                            · {{ $groupRow->campus_name ?? 'Sin plantel' }}
                        </div>

                        <div class="mt-3">
                            <span class="badge bg-blue-lt">
                                {{ $studentsCount }} alumnos
                            </span>

                            <span
                                id="active-days-badge"
                                class="badge bg-success-lt"
                            >
                                {{ $activeDaysCount }}
                                día(s) activo(s)
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">
                                Reglas del grupo
                            </h3>

                            <div class="text-secondary">
                                Aplican al escaneo de todos sus alumnos.
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <label class="form-check form-switch mb-3">
                            <input
                                type="checkbox"
                                name="requires_guardian_scan"
                                value="1"
                                class="form-check-input"
                                @checked(
                                    old(
                                        'requires_guardian_scan',
                                        $groupRow->requires_guardian_scan
                                    )
                                )
                            >

                            <span class="form-check-label">
                                <strong>Requerir tutor</strong>
                            </span>
                        </label>

                        <div class="form-hint mb-4">
                            Al activarlo, la entrega o recogida debe realizarse con un tutor autorizado para el alumno.
                        </div>

                        <label
                            for="auto_transition_minutes"
                            class="form-label required"
                        >
                            Anticipación del modo salida
                        </label>

                        <div class="input-group">
                            <input
                                type="number"
                                id="auto_transition_minutes"
                                name="auto_transition_minutes"
                                class="form-control"
                                min="0"
                                max="120"
                                step="1"
                                value="{{ old(
                                    'auto_transition_minutes',
                                    $groupRow->auto_transition_minutes ?? 30
                                ) }}"
                                required
                            >

                            <span class="input-group-text">
                                minutos
                            </span>
                        </div>

                        <div class="form-hint">
                            El dispositivo en modo automático cambia a salida esta cantidad de minutos antes de la hora de salida.
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mt-3">
                    <i class="ti ti-info-circle me-2"></i>
                    Los días desactivados no generan asistencia ni ausencia automática para este grupo.
                </div>

                <div class="alert alert-warning">
                    <i class="ti ti-arrows-exchange me-2"></i>
                    La transición automática nunca debe comenzar antes de terminar el límite de retardo.
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">
                                Horario semanal
                            </h3>

                            <div class="text-secondary">
                                Entrada, tolerancia, retardo y salida por día.
                            </div>
                        </div>

                        <div class="card-actions">
                            <div class="btn-list">
                                <button
                                    type="button"
                                    id="activate-weekdays"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    Lun–Vie
                                </button>

                                <button
                                    type="button"
                                    id="copy-monday"
                                    class="btn btn-sm btn-outline-secondary"
                                >
                                    Copiar lunes
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Día</th>
                                    <th>Activo</th>
                                    <th>Entrada</th>
                                    <th>Tolerancia</th>
                                    <th>Límite retardo</th>
                                    <th>Salida</th>
                                    <th>Modo salida</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach(
                                    $weekdays
                                    as $weekday => $label
                                )
                                    @php
                                        $dayIsActive = $isActive(
                                            (int) $weekday
                                        );
                                    @endphp

                                    <tr
                                        data-schedule-row="{{ $weekday }}"
                                        class="{{
                                            $dayIsActive
                                                ? ''
                                                : 'text-secondary opacity-75'
                                        }}"
                                    >
                                        <td class="fw-bold">
                                            {{ $label }}
                                        </td>

                                        <td>
                                            <label class="form-check form-switch m-0">
                                                <input
                                                    class="form-check-input day-toggle"
                                                    type="checkbox"
                                                    name="active_weekdays[]"
                                                    value="{{ $weekday }}"
                                                    data-weekday="{{ $weekday }}"
                                                    @checked($dayIsActive)
                                                >

                                                <span class="form-check-label day-label">
                                                    {{ $dayIsActive ? 'Sí' : 'No' }}
                                                </span>
                                            </label>
                                        </td>

                                        @foreach([
                                            'entry_time' => '07:00',
                                            'grace_until' => '07:10',
                                            'late_until' => '07:30',
                                            'exit_time' => '13:00',
                                        ] as $field => $default)
                                            <td>
                                                <input
                                                    type="time"
                                                    name="{{ $field }}[{{ $weekday }}]"
                                                    value="{{ $timeValue(
                                                        $field,
                                                        (int) $weekday,
                                                        $default
                                                    ) }}"
                                                    class="form-control form-control-sm schedule-time"
                                                    data-field="{{ $field }}"
                                                    data-weekday="{{ $weekday }}"
                                                >
                                            </td>
                                        @endforeach

                                        <td style="min-width: 125px;">
                                            <div
                                                class="transition-preview fw-semibold"
                                                data-weekday="{{ $weekday }}"
                                            >
                                                —
                                            </div>

                                            <div
                                                class="schedule-validation small mt-1"
                                                data-weekday="{{ $weekday }}"
                                            ></div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="card-body border-top">
                        <div class="alert alert-light mb-0">
                            <strong>Orden obligatorio:</strong>
                            entrada ≤ tolerancia ≤ límite de retardo &lt; salida.
                            Además, el inicio automático de salida debe ser igual o posterior al límite de retardo.
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-between">
                        <a
                            href="{{ route('admin.groups.index') }}"
                            class="btn btn-outline-secondary"
                        >
                            Cancelar
                        </a>

                        <button
                            id="save-schedules"
                            class="btn btn-primary"
                        >
                            <i class="ti ti-device-floppy me-1"></i>
                            Guardar configuración
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('schedule-form');
    const autoMinutesInput = document.getElementById(
        'auto_transition_minutes'
    );
    const toggles = Array.from(
        document.querySelectorAll('.day-toggle')
    );
    const saveButton = document.getElementById(
        'save-schedules'
    );

    const minutesFromTime = (value) => {
        if (!value || !value.includes(':')) {
            return null;
        }

        const [hours, minutes] = value
            .split(':')
            .map(Number);

        return (hours * 60) + minutes;
    };

    const timeFromMinutes = (value) => {
        const normalized = (
            (value % 1440) + 1440
        ) % 1440;

        const hours = Math.floor(
            normalized / 60
        );

        const minutes = normalized % 60;

        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
    };

    const field = (weekday, name) => {
        return document.querySelector(
            `[data-weekday="${weekday}"][data-field="${name}"]`
        );
    };

    const validateRow = (weekday) => {
        const toggle = document.querySelector(
            `.day-toggle[data-weekday="${weekday}"]`
        );

        const row = document.querySelector(
            `[data-schedule-row="${weekday}"]`
        );

        const message = document.querySelector(
            `.schedule-validation[data-weekday="${weekday}"]`
        );

        const preview = document.querySelector(
            `.transition-preview[data-weekday="${weekday}"]`
        );

        row.classList.toggle(
            'text-secondary',
            !toggle.checked
        );

        row.classList.toggle(
            'opacity-75',
            !toggle.checked
        );

        row.querySelector('.day-label').textContent =
            toggle.checked ? 'Sí' : 'No';

        const entry = minutesFromTime(
            field(weekday, 'entry_time').value
        );

        const grace = minutesFromTime(
            field(weekday, 'grace_until').value
        );

        const late = minutesFromTime(
            field(weekday, 'late_until').value
        );

        const exit = minutesFromTime(
            field(weekday, 'exit_time').value
        );

        const autoMinutes = Number(
            autoMinutesInput.value || 0
        );

        const transitionStart = exit === null
            ? null
            : exit - autoMinutes;

        preview.textContent = transitionStart === null
            ? '—'
            : timeFromMinutes(transitionStart);

        if (!toggle.checked) {
            message.textContent = 'Día inactivo';
            message.className =
                'schedule-validation small mt-1 text-secondary';

            return true;
        }

        if (
            [entry, grace, late, exit]
                .some((value) => value === null)
        ) {
            message.textContent = 'Completa el horario';
            message.className =
                'schedule-validation small mt-1 text-danger';

            return false;
        }

        if (!(
            entry <= grace
            && grace <= late
            && late < exit
        )) {
            message.textContent = 'Secuencia inválida';
            message.className =
                'schedule-validation small mt-1 text-danger';

            return false;
        }

        if (transitionStart < late) {
            message.textContent =
                'La salida automática inicia demasiado pronto';

            message.className =
                'schedule-validation small mt-1 text-warning';

            return false;
        }

        message.textContent = 'Configuración válida';
        message.className =
            'schedule-validation small mt-1 text-success';

        return true;
    };

    const validateAll = () => {
        const valid = toggles
            .map((toggle) =>
                validateRow(toggle.dataset.weekday)
            )
            .every(Boolean);

        saveButton.disabled = !valid;

        const activeCount = toggles.filter(
            (toggle) => toggle.checked
        ).length;

        document.getElementById(
            'active-days-badge'
        ).textContent = `${activeCount} día(s) activo(s)`;

        return valid;
    };

    toggles.forEach((toggle) => {
        toggle.addEventListener(
            'change',
            validateAll
        );
    });

    document
        .querySelectorAll('.schedule-time')
        .forEach((input) => {
            input.addEventListener(
                'input',
                validateAll
            );
        });

    autoMinutesInput.addEventListener(
        'input',
        validateAll
    );

    document.getElementById(
        'activate-weekdays'
    ).addEventListener('click', () => {
        toggles.forEach((toggle) => {
            const weekday = Number(
                toggle.dataset.weekday
            );

            toggle.checked =
                weekday >= 1 && weekday <= 5;
        });

        validateAll();
    });

    document.getElementById(
        'copy-monday'
    ).addEventListener('click', () => {
        const fields = [
            'entry_time',
            'grace_until',
            'late_until',
            'exit_time',
        ];

        fields.forEach((name) => {
            const mondayValue = field(
                1,
                name
            ).value;

            for (
                let weekday = 2;
                weekday <= 7;
                weekday++
            ) {
                field(
                    weekday,
                    name
                ).value = mondayValue;
            }
        });

        validateAll();
    });

    form.addEventListener('submit', (event) => {
        if (!validateAll()) {
            event.preventDefault();

            alert(
                'Corrige los horarios marcados antes de guardar.'
            );
        }
    });

    validateAll();
});
</script>
@endpush
