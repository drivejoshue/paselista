@extends('layouts.sysadmin')

@section('title', 'Administradores · '.$school->name)
@section('page_title', 'Administradores')

@section('content')

@php
    $editingAdministratorId = old(
        '_editing_administrator_id'
    );

    $resettingAdministratorId = old(
        '_resetting_administrator_id'
    );
@endphp

<div class="page-header d-print-none">
    <div class="row g-2 align-items-center">

        <div class="col">
            <div class="page-pretitle">

                <a
                    href="{{ route(
                        'sysadmin.schools.show',
                        $school
                    ) }}"
                    class="text-secondary text-decoration-none"
                >
                    <i class="ti ti-arrow-left me-1"></i>
                    {{ $school->name }}
                </a>

            </div>

            <h2 class="page-title">
                Administradores de la escuela
            </h2>

            <div class="text-secondary mt-1">
                Alta, edición, suspensión y
                restablecimiento de contraseña.
            </div>
        </div>

        <div class="col-auto ms-auto">

            <a
                href="{{ route(
                    'sysadmin.schools.app-config.edit',
                    $school
                ) }}"
                class="btn btn-outline-primary"
            >
                <i class="ti ti-device-mobile-cog me-2"></i>
                Configurar apps
            </a>

        </div>
    </div>
</div>


@if (session('status'))
    <div class="alert alert-success mt-3">

        <div class="d-flex">
            <div>
                <i class="ti ti-circle-check me-2"></i>
            </div>

            <div>
                {{ session('status') }}
            </div>
        </div>

    </div>
@endif


@if (
    $errors->any()
    && ! $editingAdministratorId
    && ! $resettingAdministratorId
)
    <div class="alert alert-danger mt-3">

        <div class="fw-semibold mb-1">
            No se pudo completar la operación.
        </div>

        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>

    </div>
@endif


<div class="row row-cards">

    {{-- ================================================================
         CREAR ADMINISTRADOR
         ================================================================ --}}

    <div class="col-lg-5">

        <div class="card">

            <form
                method="POST"
                action="{{ route(
                    'sysadmin.schools.administrators.store',
                    $school
                ) }}"
            >
                @csrf

                <div class="card-header">
                    <h3 class="card-title">
                        Nuevo administrador
                    </h3>
                </div>

                <div class="card-body">

                    <div class="mb-3">

                        <label class="form-label required">
                            Nombre
                        </label>

                        <input
                            type="text"
                            name="name"
                            class="form-control {{
                                $errors->has('name')
                                && ! $editingAdministratorId
                                    ? 'is-invalid'
                                    : ''
                            }}"
                            value="{{
                                ! $editingAdministratorId
                                    ? old('name')
                                    : ''
                            }}"
                            maxlength="160"
                            autocomplete="name"
                            required
                        >

                        @if (
                            ! $editingAdministratorId
                            && $errors->has('name')
                        )
                            <div class="invalid-feedback">
                                {{ $errors->first('name') }}
                            </div>
                        @endif

                    </div>


                    <div class="mb-3">

                        <label class="form-label required">
                            Correo
                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-control {{
                                $errors->has('email')
                                && ! $editingAdministratorId
                                    ? 'is-invalid'
                                    : ''
                            }}"
                            value="{{
                                ! $editingAdministratorId
                                    ? old('email')
                                    : ''
                            }}"
                            maxlength="180"
                            autocomplete="email"
                            required
                        >

                        @if (
                            ! $editingAdministratorId
                            && $errors->has('email')
                        )
                            <div class="invalid-feedback">
                                {{ $errors->first('email') }}
                            </div>
                        @endif

                    </div>


                    <div class="row g-3 mb-3">

                        <div class="col-md-6">

                            <label class="form-label">
                                Teléfono
                            </label>

                            <input
                                type="text"
                                name="phone"
                                class="form-control {{
                                    $errors->has('phone')
                                    && ! $editingAdministratorId
                                        ? 'is-invalid'
                                        : ''
                                }}"
                                value="{{
                                    ! $editingAdministratorId
                                        ? old('phone')
                                        : ''
                                }}"
                                maxlength="30"
                                autocomplete="tel"
                            >

                            @if (
                                ! $editingAdministratorId
                                && $errors->has('phone')
                            )
                                <div class="invalid-feedback">
                                    {{ $errors->first('phone') }}
                                </div>
                            @endif

                        </div>


                        <div class="col-md-6">

                            <label class="form-label required">
                                Rol
                            </label>

                            <select
                                name="role"
                                class="form-select {{
                                    $errors->has('role')
                                    && ! $editingAdministratorId
                                        ? 'is-invalid'
                                        : ''
                                }}"
                                required
                            >

                                <option
                                    value="director"
                                    @selected(
                                        ! $editingAdministratorId
                                        && old('role', 'director')
                                            === 'director'
                                    )
                                >
                                    Director
                                </option>

                                <option
                                    value="school_admin"
                                    @selected(
                                        ! $editingAdministratorId
                                        && old('role')
                                            === 'school_admin'
                                    )
                                >
                                    Administrador escolar
                                </option>

                            </select>

                            @if (
                                ! $editingAdministratorId
                                && $errors->has('role')
                            )
                                <div class="invalid-feedback">
                                    {{ $errors->first('role') }}
                                </div>
                            @endif

                        </div>

                    </div>


                    <div class="row g-3">

                        <div class="col-md-6">

                            <label class="form-label required">
                                Contraseña
                            </label>

                            <input
                                type="password"
                                name="password"
                                class="form-control {{
                                    $errors->has('password')
                                    && ! $editingAdministratorId
                                        ? 'is-invalid'
                                        : ''
                                }}"
                                minlength="8"
                                autocomplete="new-password"
                                required
                            >

                            @if (
                                ! $editingAdministratorId
                                && $errors->has('password')
                            )
                                <div class="invalid-feedback">
                                    {{ $errors->first('password') }}
                                </div>
                            @endif

                        </div>


                        <div class="col-md-6">

                            <label class="form-label required">
                                Confirmar
                            </label>

                            <input
                                type="password"
                                name="password_confirmation"
                                class="form-control"
                                minlength="8"
                                autocomplete="new-password"
                                required
                            >

                        </div>

                    </div>

                </div>


                <div class="card-footer text-end">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        <i class="ti ti-user-plus me-2"></i>
                        Crear administrador
                    </button>

                </div>

            </form>

        </div>

    </div>


    {{-- ================================================================
         LISTADO
         ================================================================ --}}

    <div class="col-lg-7">

        <div class="card">

            <div class="card-header">

                <h3 class="card-title">
                    Usuarios administrativos
                </h3>

                <div class="card-actions">

                    <span class="badge bg-blue-lt text-blue">
                        {{ $administrators->count() }}
                    </span>

                </div>

            </div>


            <div class="table-responsive">

                <table class="table table-vcenter card-table">

                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Último acceso</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>

                    <tbody>

                    @forelse (
                        $administrators
                        as $administrator
                    )

                        <tr>

                            <td>

                                <div class="fw-semibold">
                                    {{ $administrator->name }}
                                </div>

                                <div class="small text-secondary">
                                    {{ $administrator->email }}
                                </div>

                                @if ($administrator->phone)

                                    <div class="small text-secondary">
                                        {{ $administrator->phone }}
                                    </div>

                                @endif

                            </td>


                            <td>

                                @if (
                                    $administrator->role
                                    === 'school_admin'
                                )

                                    <span
                                        class="
                                            badge
                                            bg-purple-lt
                                            text-purple
                                        "
                                    >
                                        Administrador
                                    </span>

                                @else

                                    <span
                                        class="
                                            badge
                                            bg-azure-lt
                                            text-azure
                                        "
                                    >
                                        Director
                                    </span>

                                @endif

                            </td>


                            <td>

                                @if (
                                    $administrator->status
                                    === 'active'
                                )

                                    <span
                                        class="
                                            badge
                                            bg-green-lt
                                            text-green
                                        "
                                    >
                                        Activo
                                    </span>

                                @else

                                    <span
                                        class="
                                            badge
                                            bg-red-lt
                                            text-red
                                        "
                                    >
                                        Suspendido
                                    </span>

                                @endif

                            </td>


                            <td>

                                @if ($administrator->last_login_at)

                                    <span
                                        class="small text-secondary"
                                    >
                                        {{
                                            \Illuminate\Support\Carbon::parse(
                                                $administrator->last_login_at
                                            )->format('d/m/Y H:i')
                                        }}
                                    </span>

                                @else

                                    <span class="text-secondary">
                                        —
                                    </span>

                                @endif

                            </td>


                            <td class="text-end">

    <div class="d-flex justify-content-end gap-2">

        <button
            type="button"
            class="btn btn-sm btn-outline-primary"
            data-bs-toggle="modal"
            data-bs-target="#edit-admin-{{ $administrator->id }}"
            title="Editar administrador"
        >
            <i class="ti ti-edit me-1"></i>
            Editar
        </button>

        <button
            type="button"
            class="btn btn-sm btn-outline-warning"
            data-bs-toggle="modal"
            data-bs-target="#reset-admin-{{ $administrator->id }}"
            title="Restablecer contraseña"
        >
            <i class="ti ti-key"></i>
        </button>

    </div>

</td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="5"
                                class="
                                    text-center
                                    text-secondary
                                    py-5
                                "
                            >
                                Sin administradores.
                            </td>

                        </tr>

                    @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>


{{-- =====================================================================
     MODALES
     ===================================================================== --}}

@foreach ($administrators as $administrator)

    @php
        $isCurrentEdit =
            (string) $editingAdministratorId
            === (string) $administrator->id;

        $isCurrentReset =
            (string) $resettingAdministratorId
            === (string) $administrator->id;
    @endphp


    {{-- ================================================================
         EDITAR
         ================================================================ --}}

    <div
        class="modal modal-blur fade"
        id="edit-admin-{{ $administrator->id }}"
        tabindex="-1"
        aria-hidden="true"
    >

        <div
            class="
                modal-dialog
                modal-dialog-centered
            "
        >

            <form
                method="POST"
                action="{{ route(
                    'sysadmin.schools.administrators.update',
                    [
                        $school,
                        $administrator->id,
                    ]
                ) }}"
                class="modal-content"
            >
                @csrf
                @method('PUT')

                <input
                    type="hidden"
                    name="_editing_administrator_id"
                    value="{{ $administrator->id }}"
                >


                <div class="modal-header">

                    <div>

                        <h5 class="modal-title">
                            Editar administrador
                        </h5>

                        <div class="small text-secondary">
                            {{ $administrator->email }}
                        </div>

                    </div>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Cerrar"
                    ></button>

                </div>


                <div class="modal-body">

                    @if (
                        $isCurrentEdit
                        && $errors->any()
                    )

                        <div class="alert alert-danger">

                            <div class="fw-semibold mb-1">
                                Revisa los datos.
                            </div>

                            <ul class="mb-0">

                                @foreach (
                                    $errors->all()
                                    as $error
                                )

                                    <li>
                                        {{ $error }}
                                    </li>

                                @endforeach

                            </ul>

                        </div>

                    @endif


                    <div class="mb-3">

                        <label class="form-label required">
                            Nombre
                        </label>

                        <input
                            type="text"
                            name="name"
                            class="form-control {{
                                $isCurrentEdit
                                && $errors->has('name')
                                    ? 'is-invalid'
                                    : ''
                            }}"
                            value="{{
                                $isCurrentEdit
                                    ? old(
                                        'name',
                                        $administrator->name
                                    )
                                    : $administrator->name
                            }}"
                            maxlength="160"
                            required
                        >

                        @if (
                            $isCurrentEdit
                            && $errors->has('name')
                        )

                            <div class="invalid-feedback">
                                {{ $errors->first('name') }}
                            </div>

                        @endif

                    </div>


                    <div class="mb-3">

                        <label class="form-label required">
                            Correo
                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-control {{
                                $isCurrentEdit
                                && $errors->has('email')
                                    ? 'is-invalid'
                                    : ''
                            }}"
                            value="{{
                                $isCurrentEdit
                                    ? old(
                                        'email',
                                        $administrator->email
                                    )
                                    : $administrator->email
                            }}"
                            maxlength="180"
                            required
                        >

                        @if (
                            $isCurrentEdit
                            && $errors->has('email')
                        )

                            <div class="invalid-feedback">
                                {{ $errors->first('email') }}
                            </div>

                        @endif

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Teléfono
                        </label>

                        <input
                            type="text"
                            name="phone"
                            class="form-control {{
                                $isCurrentEdit
                                && $errors->has('phone')
                                    ? 'is-invalid'
                                    : ''
                            }}"
                            value="{{
                                $isCurrentEdit
                                    ? old(
                                        'phone',
                                        $administrator->phone
                                    )
                                    : $administrator->phone
                            }}"
                            maxlength="30"
                        >

                        @if (
                            $isCurrentEdit
                            && $errors->has('phone')
                        )

                            <div class="invalid-feedback">
                                {{ $errors->first('phone') }}
                            </div>

                        @endif

                    </div>


                    <div class="row g-3">

                        <div class="col-md-6">

                            <label class="form-label required">
                                Rol
                            </label>

                            @php
                                $selectedRole =
                                    $isCurrentEdit
                                        ? old(
                                            'role',
                                            $administrator->role
                                        )
                                        : $administrator->role;
                            @endphp

                            <select
                                name="role"
                                class="form-select {{
                                    $isCurrentEdit
                                    && $errors->has('role')
                                        ? 'is-invalid'
                                        : ''
                                }}"
                                required
                            >

                                <option
                                    value="director"
                                    @selected(
                                        $selectedRole
                                        === 'director'
                                    )
                                >
                                    Director
                                </option>

                                <option
                                    value="school_admin"
                                    @selected(
                                        $selectedRole
                                        === 'school_admin'
                                    )
                                >
                                    Administrador escolar
                                </option>

                            </select>

                            @if (
                                $isCurrentEdit
                                && $errors->has('role')
                            )

                                <div class="invalid-feedback">
                                    {{ $errors->first('role') }}
                                </div>

                            @endif

                        </div>


                        <div class="col-md-6">

                            <label class="form-label required">
                                Estado
                            </label>

                            @php
                                $selectedStatus =
                                    $isCurrentEdit
                                        ? old(
                                            'status',
                                            $administrator->status
                                        )
                                        : $administrator->status;
                            @endphp

                            <select
                                name="status"
                                class="form-select {{
                                    $isCurrentEdit
                                    && $errors->has('status')
                                        ? 'is-invalid'
                                        : ''
                                }}"
                                required
                            >

                                <option
                                    value="active"
                                    @selected(
                                        $selectedStatus
                                        === 'active'
                                    )
                                >
                                    Activo
                                </option>

                                <option
                                    value="blocked"
                                    @selected(
                                        $selectedStatus
                                        === 'blocked'
                                    )
                                >
                                    Suspendido
                                </option>

                            </select>

                            @if (
                                $isCurrentEdit
                                && $errors->has('status')
                            )

                                <div class="invalid-feedback">
                                    {{ $errors->first('status') }}
                                </div>

                            @endif

                        </div>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn me-auto"
                        data-bs-dismiss="modal"
                    >
                        Cancelar
                    </button>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        <i class="ti ti-device-floppy me-2"></i>
                        Guardar cambios
                    </button>

                </div>

            </form>

        </div>

    </div>


    {{-- ================================================================
         CONTRASEÑA
         ================================================================ --}}

    <div
        class="modal modal-blur fade"
        id="reset-admin-{{ $administrator->id }}"
        tabindex="-1"
        aria-hidden="true"
    >

        <div
            class="
                modal-dialog
                modal-dialog-centered
            "
        >

            <form
                method="POST"
                action="{{ route(
                    'sysadmin.schools.administrators.reset-password',
                    [
                        $school,
                        $administrator->id,
                    ]
                ) }}"
                class="modal-content"
            >
                @csrf

                <input
                    type="hidden"
                    name="_resetting_administrator_id"
                    value="{{ $administrator->id }}"
                >


                <div class="modal-header">

                    <div>

                        <h5 class="modal-title">
                            Restablecer contraseña
                        </h5>

                        <div class="small text-secondary">
                            {{ $administrator->name }}
                        </div>

                    </div>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Cerrar"
                    ></button>

                </div>


                <div class="modal-body">

                    @if (
                        $isCurrentReset
                        && $errors->any()
                    )

                        <div class="alert alert-danger">

                            <ul class="mb-0">

                                @foreach (
                                    $errors->all()
                                    as $error
                                )

                                    <li>
                                        {{ $error }}
                                    </li>

                                @endforeach

                            </ul>

                        </div>

                    @endif


                    <div class="alert alert-warning">

                        <div class="d-flex">

                            <div>
                                <i
                                    class="
                                        ti
                                        ti-alert-triangle
                                        me-2
                                    "
                                ></i>
                            </div>

                            <div>
                                Al cambiar la contraseña se
                                revocarán sus sesiones API
                                actuales.
                            </div>

                        </div>

                    </div>


                    <div class="mb-3">

                        <label class="form-label required">
                            Nueva contraseña
                        </label>

                        <input
                            type="password"
                            name="password"
                            class="form-control {{
                                $isCurrentReset
                                && $errors->has('password')
                                    ? 'is-invalid'
                                    : ''
                            }}"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >

                        @if (
                            $isCurrentReset
                            && $errors->has('password')
                        )

                            <div class="invalid-feedback">
                                {{ $errors->first('password') }}
                            </div>

                        @endif

                    </div>


                    <div>

                        <label class="form-label required">
                            Confirmar contraseña
                        </label>

                        <input
                            type="password"
                            name="password_confirmation"
                            class="form-control"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn me-auto"
                        data-bs-dismiss="modal"
                    >
                        Cancelar
                    </button>


                    <button
                        type="submit"
                        class="btn btn-warning"
                    >
                        <i class="ti ti-key me-2"></i>
                        Restablecer
                    </button>

                </div>

            </form>

        </div>

    </div>

@endforeach

@endsection


@push('scripts')

@if ($editingAdministratorId)

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalElement = document.getElementById(
        @json(
            'edit-admin-'.$editingAdministratorId
        )
    );

    if (
        modalElement
        && window.bootstrap
    ) {
        bootstrap.Modal
            .getOrCreateInstance(modalElement)
            .show();
    }
});
</script>

@elseif ($resettingAdministratorId)

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalElement = document.getElementById(
        @json(
            'reset-admin-'.$resettingAdministratorId
        )
    );

    if (
        modalElement
        && window.bootstrap
    ) {
        bootstrap.Modal
            .getOrCreateInstance(modalElement)
            .show();
    }
});
</script>

@endif

@endpush