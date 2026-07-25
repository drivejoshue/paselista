@extends('layouts.app')

@section('title', 'Configuración IA | PaseLista')
@section('section-label', 'Administración')
@section('page-title', 'Configuración de PaseLista IA')

@section('topbar-actions')
    <a href="{{ route('admin.ai.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="ti ti-arrow-left me-1"></i>
        PaseLista IA
    </a>
@endsection

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.ai.settings.update') }}" class="card">
        @csrf
        @method('PUT')

        <div class="card-header">
            <div>
                <h3 class="card-title">Disponibilidad y límites</h3>
                <div class="text-secondary">La clave API permanece en el servidor.</div>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-4">
                <div class="col-lg-6">
                    <label class="form-check form-switch mb-3">
                        <input type="checkbox" name="enabled" value="1" class="form-check-input" @checked(old('enabled', $settings->enabled))>
                        <span class="form-check-label"><strong>Activar PaseLista IA</strong></span>
                    </label>

                    <label class="form-label required">Modelo predeterminado</label>
                    <select name="default_model" class="form-select mb-3">
                        <option value="fast" @selected(old('default_model', $settings->default_model) === 'fast')>DeepSeek V4 Flash</option>
                        <option value="deep" @selected(old('default_model', $settings->default_model) === 'deep')>DeepSeek V4 Pro</option>
                    </select>

                    <label class="form-check form-switch">
                        <input type="checkbox" name="allow_pro" value="1" class="form-check-input" @checked(old('allow_pro', $settings->allow_pro))>
                        <span class="form-check-label">Permitir análisis profundo V4 Pro</span>
                    </label>
                </div>

                <div class="col-lg-6">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Límite mensual</label>
                            <div class="input-group">
                                <input type="number" name="monthly_query_limit" class="form-control" min="1" max="100000" value="{{ old('monthly_query_limit', $settings->monthly_query_limit) }}" required>
                                <span class="input-group-text">consultas</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Periodo máximo</label>
                            <div class="input-group">
                                <input type="number" name="max_range_days" class="form-control" min="7" max="366" value="{{ old('max_range_days', $settings->max_range_days) }}" required>
                                <span class="input-group-text">días</span>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div class="fw-bold mb-3">Alcances permitidos</div>
                    @foreach([
                        'allow_school_analysis' => 'Escuela completa',
                        'allow_group_analysis' => 'Grupos',
                        'allow_student_analysis' => 'Alumnos individuales',
                    ] as $field => $label)
                        <label class="form-check form-switch mb-3">
                            <input type="checkbox" name="{{ $field }}" value="1" class="form-check-input" @checked(old($field, $settings->{$field}))>
                            <span class="form-check-label">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card-footer d-flex justify-content-end">
            <button class="btn btn-primary">
                <i class="ti ti-device-floppy me-1"></i>
                Guardar configuración
            </button>
        </div>
    </form>

    <div class="alert alert-warning mt-3">
        Revisa el aviso de privacidad institucional antes de habilitar análisis individual.
    </div>
@endsection
