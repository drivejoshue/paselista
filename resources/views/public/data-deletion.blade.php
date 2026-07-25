@extends('layouts.public-site')

@section('title', 'Derechos ARCO y eliminación de datos · PaseLista')
@section('meta-description', 'Formulario para solicitar acceso, corrección, oposición, cancelación o eliminación de datos en PaseLista.')

@section('content')
<section class="sp-legal-hero">
    <div class="container relative">
        <span class="sp-eyebrow">Control de datos personales</span>
        <h1>Derechos ARCO y eliminación de datos</h1>
        <p>Envía una solicitud para acceder, corregir, cancelar, oponerte o pedir la eliminación de una cuenta y sus datos asociados.</p>
    </div>
</section>

<section class="sp-legal-section">
    <div class="container relative">
        <div class="grid lg:grid-cols-12 grid-cols-1 gap-8">
            <div class="lg:col-span-5">
                <div class="sp-info-card">
                    <h2>Antes de enviar</h2>
                    <p>Las cuentas de Staff y Family normalmente son administradas por la institución educativa. Para proteger a alumnos y tutores, PaseLista verificará la identidad y el vínculo antes de entregar, modificar o eliminar información.</p>
                    <ul class="sp-check-list mt-5">
                        <li><i class="uil uil-check-circle"></i> No incluyas contraseñas ni imágenes de identificaciones en este formulario.</li>
                        <li><i class="uil uil-check-circle"></i> Indica la escuela y el correo de la cuenta.</li>
                        <li><i class="uil uil-check-circle"></i> PaseLista podrá pedir evidencia por un canal seguro.</li>
                        <li><i class="uil uil-check-circle"></i> Algunos registros podrán conservarse cuando exista una obligación legal o de seguridad.</li>
                    </ul>
                    <a href="{{ route('public.privacy') }}" class="sp-text-link mt-6 inline-flex">Leer aviso de privacidad <i class="uil uil-arrow-right"></i></a>
                </div>
            </div>

            <div class="lg:col-span-7">
                <div class="sp-form-card">
                    @if(session('privacy_request_status'))
                        <div class="sp-success-alert">
                            <i class="uil uil-check-circle"></i>
                            <div>{{ session('privacy_request_status') }}</div>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="sp-error-alert">
                            <i class="uil uil-exclamation-triangle"></i>
                            <div>
                                <strong>Revisa la solicitud.</strong>
                                <ul>
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('public.privacy-requests.store') }}" class="sp-public-form">
                        @csrf

                        <div class="hidden" aria-hidden="true">
                            <label for="website">Sitio web</label>
                            <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="grid md:grid-cols-2 grid-cols-1 gap-5">
                            <div class="md:col-span-2">
                                <label for="request_type">Tipo de solicitud</label>
                                <select id="request_type" name="request_type" required>
                                    <option value="">Selecciona una opción</option>
                                    <option value="access" @selected(old('request_type') === 'access')>Acceso a mis datos</option>
                                    <option value="rectification" @selected(old('request_type') === 'rectification')>Rectificación o corrección</option>
                                    <option value="cancellation" @selected(old('request_type') === 'cancellation')>Cancelación</option>
                                    <option value="opposition" @selected(old('request_type') === 'opposition')>Oposición o limitación</option>
                                    <option value="account_deletion" @selected(old('request_type') === 'account_deletion')>Eliminar cuenta</option>
                                    <option value="data_deletion" @selected(old('request_type') === 'data_deletion')>Eliminar datos asociados</option>
                                    <option value="data_copy" @selected(old('request_type') === 'data_copy')>Solicitar copia de datos</option>
                                    <option value="security_report" @selected(old('request_type') === 'security_report')>Reportar acceso no autorizado</option>
                                    <option value="other" @selected(old('request_type') === 'other')>Otra solicitud</option>
                                </select>
                            </div>

                            <div>
                                <label for="full_name">Nombre completo</label>
                                <input id="full_name" name="full_name" value="{{ old('full_name') }}" maxlength="180" required>
                            </div>

                            <div>
                                <label for="email">Correo de contacto</label>
                                <input id="email" name="email" type="email" value="{{ old('email') }}" maxlength="190" required>
                            </div>

                            <div>
                                <label for="relationship">Relación con PaseLista</label>
                                <select id="relationship" name="relationship">
                                    <option value="">Selecciona</option>
                                    <option value="guardian" @selected(old('relationship') === 'guardian')>Madre, padre o tutor</option>
                                    <option value="staff" @selected(old('relationship') === 'staff')>Personal escolar</option>
                                    <option value="student_representative" @selected(old('relationship') === 'student_representative')>Representante de alumno</option>
                                    <option value="school" @selected(old('relationship') === 'school')>Representante de institución</option>
                                    <option value="other" @selected(old('relationship') === 'other')>Otra</option>
                                </select>
                            </div>

                            <div>
                                <label for="school_name">Institución educativa</label>
                                <input id="school_name" name="school_name" value="{{ old('school_name') }}" maxlength="190">
                            </div>

                            <div class="md:col-span-2">
                                <label for="account_reference">Referencia de cuenta</label>
                                <input id="account_reference" name="account_reference" value="{{ old('account_reference') }}" maxlength="190" placeholder="Correo de acceso, matrícula o folio; no escribas contraseñas">
                            </div>

                            <div class="md:col-span-2">
                                <label for="description">Describe la solicitud</label>
                                <textarea id="description" name="description" rows="6" minlength="20" maxlength="3000" required>{{ old('description') }}</textarea>
                                <small>No adjuntes identificaciones en esta etapa.</small>
                            </div>

                            <div class="md:col-span-2">
                                <label class="sp-checkbox-row">
                                    <input type="checkbox" name="privacy_acknowledgement" value="1" @checked(old('privacy_acknowledgement')) required>
                                    <span>Leí el <a href="{{ route('public.privacy') }}" target="_blank" rel="noopener">aviso de privacidad</a> y autorizo el tratamiento de los datos de este formulario para atender la solicitud.</span>
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="sp-button sp-button-primary mt-6">
                            Registrar solicitud
                            <i class="uil uil-arrow-right"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
