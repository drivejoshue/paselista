@extends('layouts.sysadmin')

@section('title', 'Catálogo de módulos | SchoolCore')

@section('content')
@php
    $activeCount = $modules->where('status', 'active')->count();
    $comingSoon = $modules->where('status', 'coming_soon')->count();
@endphp

<div class="container-xl">

    <div class="page-header mb-4">
        <div class="row align-items-center g-3">
            <div class="col">
                <div class="page-pretitle">
                    SYSADMIN / SCHOOLCORE ERP
                </div>

                <h2 class="page-title">
                    Catálogo comercial de módulos
                </h2>

                <div class="text-secondary mt-2">
                    Configura nombres, disponibilidad y tarifas
                    de los módulos del ERP.
                </div>
            </div>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>No se guardaron los cambios.</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row row-cards mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary small">
                        Módulos registrados
                    </div>
                    <div class="h1 mb-0">{{ $modules->count() }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary small">
                        Disponibles
                    </div>
                    <div class="h1 mb-0">{{ $activeCount }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary small">
                        Próximamente
                    </div>
                    <div class="h1 mb-0">{{ $comingSoon }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info mb-4">
        Las tarifas de este catálogo son precios de referencia.
        Cada escuela puede tener un precio pactado diferente.

        Cambiar una tarifa aquí no modifica los contratos
        existentes ni genera cargos.
    </div>

    <div class="row row-cards">
        @foreach($modules as $module)
            @php
                $isCore = (bool) $module->is_core;

                $isIncluded = in_array(
                    (int) $module->id,
                    $includedIds,
                    true
                );

                $pricingModel = $module->pricing_model
                    ?? ($isIncluded ? 'included' : 'paid');

                $monthlyPrice = $module->monthly_price ?? null;
                $annualPrice = $module->annual_price ?? null;

                $assignedCount = (int) (
                    $subscriptions[$module->id] ?? 0
                );
            @endphp

            <div class="col-12 col-xl-6">
                <div class="card h-100">

                    <div class="card-header">
                        <div class="flex-fill">
                            <h3 class="card-title mb-1">
                                {{ $module->name }}
                            </h3>

                            <code>{{ $module->code }}</code>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            @if($isCore)
                                <span class="badge bg-blue-lt">
                                    Núcleo
                                </span>
                            @endif

                            @if($isIncluded)
                                <span class="badge bg-green-lt">
                                    Incluido en planes
                                </span>
                            @endif

                            <span class="badge {{
                                $module->status === 'active'
                                    ? 'bg-green-lt'
                                    : 'bg-secondary-lt'
                            }}">
                                {{ $module->status }}
                            </span>
                        </div>
                    </div>

                    <div class="card-body">
                        <form
                            method="POST"
                            action="{{ route(
                                'sysadmin.modules.update',
                                $module->id
                            ) }}"
                        >
                            @csrf
                            @method('PUT')

                            <div class="row g-3">

                                <div class="col-12">
                                    <label class="form-label">
                                        Nombre comercial
                                    </label>

                                    <input
                                        type="text"
                                        name="name"
                                        class="form-control"
                                        maxlength="150"
                                        required
                                        value="{{ $module->name }}"
                                    >
                                </div>

                                <div class="col-12">
                                    <label class="form-label">
                                        Descripción
                                    </label>

                                    <textarea
                                        name="description"
                                        rows="2"
                                        class="form-control"
                                    >{{ $module->description }}</textarea>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">
                                        Clasificación comercial
                                    </label>

                                    <select
                                        name="pricing_model"
                                        class="form-select"
                                        @disabled($isCore)
                                    >
                                        @foreach([
                                            'included' => 'Incluido en plan',
                                            'free' => 'Gratuito',
                                            'paid' => 'Complemento de pago',
                                            'quote' => 'Bajo cotización',
                                        ] as $value => $label)
                                            <option
                                                value="{{ $value }}"
                                                @selected($pricingModel === $value)
                                            >
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @if($isCore)
                                        <input
                                            type="hidden"
                                            name="pricing_model"
                                            value="included"
                                        >
                                    @endif
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">
                                        Disponibilidad
                                    </label>

                                    <select
                                        name="status"
                                        class="form-select"
                                        @disabled($isCore)
                                    >
                                        @foreach([
                                            'active' => 'Disponible',
                                            'inactive' => 'Deshabilitado',
                                            'coming_soon' => 'Próximamente',
                                        ] as $value => $label)
                                            <option
                                                value="{{ $value }}"
                                                @selected($module->status === $value)
                                            >
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @if($isCore)
                                        <input
                                            type="hidden"
                                            name="status"
                                            value="active"
                                        >
                                    @endif
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">
                                        Tarifa mensual (MXN)
                                    </label>

                                    <div class="input-group">
                                        <span class="input-group-text">$</span>

                                        <input
                                            name="monthly_price"
                                            type="number"
                                            class="form-control"
                                            min="0"
                                            step="0.01"
                                            max="9999999999.99"
                                            placeholder="Sin tarifa"
                                            value="{{ $monthlyPrice }}"
                                            @readonly($isCore)
                                        >
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">
                                        Tarifa anual (MXN)
                                    </label>

                                    <div class="input-group">
                                        <span class="input-group-text">$</span>

                                        <input
                                            name="annual_price"
                                            type="number"
                                            class="form-control"
                                            min="0"
                                            step="0.01"
                                            max="9999999999.99"
                                            placeholder="Sin tarifa"
                                            value="{{ $annualPrice }}"
                                            @readonly($isCore)
                                        >
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">
                                        Orden del catálogo
                                    </label>

                                    <input
                                        type="number"
                                        name="sort_order"
                                        class="form-control"
                                        min="0"
                                        max="65535"
                                        value="{{ $module->sort_order ?? 0 }}"
                                        required
                                    >
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">
                                        Activación por escuela
                                    </label>

                                    <select
                                        name="self_activatable"
                                        class="form-select"
                                        @disabled($isCore)
                                    >
                                        <option
                                            value="0"
                                            @selected(!($module->self_activatable ?? false))
                                        >
                                            Solo Sysadmin
                                        </option>
                                        <option
                                            value="1"
                                            @selected((bool) ($module->self_activatable ?? false))
                                        >
                                            Permitida
                                        </option>
                                    </select>

                                    @if($isCore)
                                        <input
                                            type="hidden"
                                            name="self_activatable"
                                            value="0"
                                        >
                                    @endif
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mt-4 gap-3">
                                <span class="text-secondary small">
                                    Escuelas con entitlement activo:
                                    {{ $assignedCount }}
                                </span>

                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                >
                                    Guardar módulo
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
