@extends('layouts.app')

@section('title', 'Nuevo núcleo familiar | PaseLista')
@section('section-label', 'Dirección')
@section('page-title', 'Nuevo núcleo familiar')

@section('topbar-actions')
    <a
        href="{{ route('admin.guardians.index') }}"
        class="btn btn-outline-secondary btn-sm"
    >
        <i class="ti ti-arrow-left me-1"></i>
        Familias
    </a>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.guardians.store') }}">
        @csrf

        <div class="row row-cards">
            <div class="col-xl-8">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <i class="ti ti-alert-circle me-2"></i>
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">1. Responsable principal</h3>
                            <div class="text-secondary">
                                Será el titular del núcleo y de la membresía PaseLista Family.
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label required">
                                Origen del responsable
                            </label>
                            <select
                                name="principal_mode"
                                id="principal-mode"
                                class="form-select"
                                required
                            >
                                <option
                                    value="new"
                                    @selected(old('principal_mode', 'new') === 'new')
                                >
                                    Registrar nueva persona
                                </option>
                                <option
                                    value="existing"
                                    @selected(old('principal_mode') === 'existing')
                                >
                                    Usar familiar o autorizado existente
                                </option>
                            </select>
                            <div class="form-hint">
                                Reutiliza una persona existente para evitar duplicar padres,
                                madres o tutores que ya estén vinculados con otro alumno.
                            </div>
                        </div>

                        <div id="existing-principal-fields" class="d-none">
                            <div class="mb-3">
                                <label class="form-label">
                                    Buscar persona existente
                                </label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-search"></i>
                                    </span>
                                    <input
                                        type="search"
                                        id="existing-principal-search"
                                        class="form-control"
                                        placeholder="Nombre, teléfono, correo o usuario"
                                    >
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label required">
                                    Persona que será responsable principal
                                </label>
                                <select
                                    name="existing_guardian_id"
                                    id="existing-principal-select"
                                    class="form-select"
                                >
                                    <option value="">Seleccionar persona</option>
                                    @foreach($availableGuardianCandidates as $candidate)
                                        <option
                                            value="{{ $candidate->id }}"
                                            @selected(
                                                (int) old('existing_guardian_id')
                                                === (int) $candidate->id
                                            )
                                        >
                                            {{ $candidate->last_name }},
                                            {{ $candidate->first_name }}
                                            · {{ $candidate->phone
                                                ?? $candidate->email
                                                ?? $candidate->access_username
                                                ?? 'Sin contacto' }}
                                            {{ $candidate->user_id ? '· Con cuenta' : '· Sin cuenta' }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-hint">
                                    Solo aparecen personas activas que todavía no son responsables
                                    principales de otro núcleo.
                                </div>
                            </div>
                        </div>

                        <div id="new-principal-fields">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label required">Nombre</label>
                                    <input
                                        type="text"
                                        name="first_name"
                                        value="{{ old('first_name') }}"
                                        class="form-control"
                                        maxlength="100"
                                        autocomplete="given-name"
                                    >
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label required">Apellidos</label>
                                    <input
                                        type="text"
                                        name="last_name"
                                        value="{{ old('last_name') }}"
                                        class="form-control"
                                        maxlength="150"
                                        autocomplete="family-name"
                                    >
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Teléfono</label>
                                    <input
                                        type="tel"
                                        name="phone"
                                        value="{{ old('phone') }}"
                                        class="form-control"
                                        maxlength="30"
                                        autocomplete="tel"
                                    >
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Correo electrónico</label>
                                    <input
                                        type="email"
                                        name="email"
                                        value="{{ old('email') }}"
                                        class="form-control"
                                        maxlength="150"
                                        autocomplete="email"
                                    >
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label required">
                                        Estado administrativo
                                    </label>
                                    <select name="status" class="form-select">
                                        <option
                                            value="active"
                                            @selected(old('status', 'active') === 'active')
                                        >
                                            Activo
                                        </option>
                                        <option
                                            value="inactive"
                                            @selected(old('status') === 'inactive')
                                        >
                                            Inactivo
                                        </option>
                                        <option
                                            value="blocked"
                                            @selected(old('status') === 'blocked')
                                        >
                                            Bloqueado
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mt-0">
                            <div class="col-md-6">
                                <label class="form-label required">
                                    Relación con los alumnos
                                </label>
                                <select name="relationship" class="form-select" required>
                                    <option
                                        value="madre"
                                        @selected(old('relationship') === 'madre')
                                    >
                                        Madre
                                    </option>
                                    <option
                                        value="padre"
                                        @selected(old('relationship') === 'padre')
                                    >
                                        Padre
                                    </option>
                                    <option
                                        value="tutor"
                                        @selected(old('relationship', 'tutor') === 'tutor')
                                    >
                                        Tutor legal
                                    </option>
                                </select>
                                <div class="form-hint">
                                    Se aplicará a todos los alumnos seleccionados.
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Acceso a la app</label>
                                <label class="form-check form-switch border rounded p-3 ps-5">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="create_user"
                                        value="1"
                                        @checked(old('create_user', '1'))
                                    >
                                    <span class="form-check-label">
                                        <span class="fw-semibold d-block">
                                            Generar cuenta PaseLista Family
                                        </span>
                                        <span class="text-secondary small">
                                            Si la persona existente ya tiene cuenta, se conservará.
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">
                                2. Alumnos sin responsable principal
                            </h3>
                            <div class="text-secondary">
                                Busca y selecciona únicamente a los hermanos que
                                formarán parte de este núcleo.
                            </div>
                        </div>

                        <div class="ms-auto">
                            <span class="badge bg-blue-lt" id="selected-students-count">
                                0 seleccionados
                            </span>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="ti ti-info-circle me-2"></i>
                            Solo aparecen alumnos activos que todavía no tienen
                            responsable principal. Una membresía cubrirá a todos
                            los alumnos seleccionados.
                        </div>

                        @if($availableStudents->isNotEmpty())
                            <div class="mb-3">
                                <label class="form-label">
                                    Buscar alumno
                                </label>

                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-search"></i>
                                    </span>

                                    <input
                                        type="search"
                                        id="student-search"
                                        class="form-control"
                                        placeholder="Nombre, matrícula o grupo"
                                        autocomplete="off"
                                    >
                                </div>
                            </div>

                            <div
                                class="list-group list-group-flush border rounded"
                                id="available-students-list"
                            >
                                @foreach($availableStudents as $student)
                                    <label
                                        class="list-group-item"
                                        data-student-option
                                        data-search-text="{{ mb_strtolower(
                                            trim(
                                                $student->first_name.' '
                                                .$student->last_name.' '
                                                .$student->student_code.' '
                                                .($student->group_name ?? '')
                                            )
                                        ) }}"
                                    >
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    name="student_ids[]"
                                                    value="{{ $student->id }}"
                                                    data-student-checkbox
                                                    @checked(in_array(
                                                        (string) $student->id,
                                                        old('student_ids', []),
                                                        true
                                                    ))
                                                >
                                            </div>

                                            <div class="col">
                                                <div class="fw-semibold">
                                                    {{ $student->first_name }}
                                                    {{ $student->last_name }}
                                                </div>

                                                <div class="text-secondary small">
                                                    {{ $student->student_code }}
                                                    · {{ $student->group_name ?? 'Sin grupo' }}
                                                </div>
                                            </div>

                                            <div class="col-auto">
                                                <span class="badge bg-green-lt">
                                                    Sin principal
                                                </span>
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>

                            <div
                                class="p-4 text-center text-secondary border rounded d-none"
                                id="student-search-empty"
                            >
                                No se encontraron alumnos sin responsable principal
                                con ese criterio.
                            </div>

                            <div class="form-hint mt-3">
                                Para mover un alumno que ya pertenece a otro núcleo,
                                utiliza la acción explícita de transferencia desde el
                                expediente de su responsable actual.
                            </div>
                        @else
                            <div class="empty py-4">
                                <div class="empty-icon">
                                    <i class="ti ti-users-group"></i>
                                </div>

                                <p class="empty-title">
                                    No hay alumnos disponibles
                                </p>

                                <p class="empty-subtitle text-secondary">
                                    Todos los alumnos activos ya tienen responsable
                                    principal. Los cambios deben realizarse mediante
                                    transferencia entre núcleos.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Así se formará el núcleo</h3>
                    </div>
                    <div class="card-body">
                        <div class="steps steps-vertical">
                            <div class="step-item">
                                <div class="h4 m-0">1. Responsable principal</div>
                                <div class="text-secondary">
                                    Registra una persona nueva o reutiliza una ya existente.
                                </div>
                            </div>
                            <div class="step-item">
                                <div class="h4 m-0">2. Alumnos principales</div>
                                <div class="text-secondary">
                                    Selecciona todos los hermanos que pertenezcan al mismo núcleo.
                                </div>
                            </div>
                            <div class="step-item">
                                <div class="h4 m-0">3. Familiares y autorizados</div>
                                <div class="text-secondary">
                                    Después agrega padre o madre secundaria, abuelos, tíos
                                    y otras personas autorizadas desde el expediente.
                                </div>
                            </div>
                            <div class="step-item">
                                <div class="h4 m-0">4. Una membresía</div>
                                <div class="text-secondary">
                                    La membresía del principal cubre a todos sus alumnos principales.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-body">
                        <div class="alert alert-warning mb-0">
                            <i class="ti ti-alert-triangle me-2"></i>
                            No se reemplaza silenciosamente al responsable principal de un alumno.
                            Todo cambio posterior se realiza mediante transferencia explícita y auditada.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer bg-transparent px-0 mt-3 d-flex justify-content-between">
            <a
                href="{{ route('admin.guardians.index') }}"
                class="btn btn-outline-secondary"
            >
                Cancelar
            </a>

            <button class="btn btn-primary">
                <i class="ti ti-home-plus me-1"></i>
                Crear núcleo familiar
            </button>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const mode = document.getElementById('principal-mode');
            const existingFields = document.getElementById(
                'existing-principal-fields'
            );
            const newFields = document.getElementById('new-principal-fields');
            const search = document.getElementById('existing-principal-search');
            const select = document.getElementById('existing-principal-select');
            const studentSearch = document.getElementById('student-search');
            const studentOptions = Array.from(
                document.querySelectorAll('[data-student-option]')
            );
            const studentCheckboxes = Array.from(
                document.querySelectorAll('[data-student-checkbox]')
            );
            const selectedStudentsCount = document.getElementById(
                'selected-students-count'
            );
            const studentSearchEmpty = document.getElementById(
                'student-search-empty'
            );
            const availableStudentsList = document.getElementById(
                'available-students-list'
            );

            const syncMode = () => {
                if (!mode || !existingFields || !newFields) {
                    return;
                }

                const existing = mode.value === 'existing';

                existingFields.classList.toggle('d-none', !existing);
                newFields.classList.toggle('d-none', existing);

                existingFields
                    .querySelectorAll('select, input, textarea')
                    .forEach((field) => {
                        field.disabled = !existing;
                    });

                newFields
                    .querySelectorAll('select, input, textarea')
                    .forEach((field) => {
                        field.disabled = existing;
                    });
            };

            const filterCandidates = () => {
                if (!search || !select) {
                    return;
                }

                const term = search.value.trim().toLocaleLowerCase('es-MX');

                Array.from(select.options).forEach((option, index) => {
                    if (index === 0) {
                        option.hidden = false;
                        return;
                    }

                    option.hidden = term !== ''
                        && !option.text
                            .toLocaleLowerCase('es-MX')
                            .includes(term);
                });
            };

            const normalizeText = (value) => value
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLocaleLowerCase('es-MX')
                .trim();

            const updateSelectedStudentsCount = () => {
                if (!selectedStudentsCount) {
                    return;
                }

                const count = studentCheckboxes.filter(
                    (checkbox) => checkbox.checked
                ).length;

                selectedStudentsCount.textContent = count === 1
                    ? '1 seleccionado'
                    : `${count} seleccionados`;
            };

            const filterStudents = () => {
                if (!studentSearch || studentOptions.length === 0) {
                    return;
                }

                const term = normalizeText(studentSearch.value);
                let visibleCount = 0;

                studentOptions.forEach((option) => {
                    const searchableText = normalizeText(
                        option.dataset.searchText || ''
                    );
                    const visible = term === ''
                        || searchableText.includes(term);

                    option.classList.toggle('d-none', !visible);

                    if (visible) {
                        visibleCount += 1;
                    }
                });

                if (studentSearchEmpty) {
                    studentSearchEmpty.classList.toggle(
                        'd-none',
                        visibleCount > 0
                    );
                }

                if (availableStudentsList) {
                    availableStudentsList.classList.toggle(
                        'd-none',
                        visibleCount === 0
                    );
                }
            };

            mode?.addEventListener('change', syncMode);
            search?.addEventListener('input', filterCandidates);
            studentSearch?.addEventListener('input', filterStudents);

            studentCheckboxes.forEach((checkbox) => {
                checkbox.addEventListener(
                    'change',
                    updateSelectedStudentsCount
                );
            });

            syncMode();
            filterCandidates();
            filterStudents();
            updateSelectedStudentsCount();
        });
    </script>
@endsection