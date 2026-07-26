@extends('layouts.public-site')

@section('title', 'Aviso de privacidad · PaseLista')
@section('meta-description', 'Aviso de privacidad integral de PaseLista para el portal web y las aplicaciones Staff y Family.')

@push('styles')
<style>
    .ps-page-hero {
        position: relative;
        padding: 150px 0 72px;
        overflow: hidden;
        background:
            radial-gradient(
                circle at 85% 15%,
                color-mix(in srgb, var(--accent-color), transparent 84%),
                transparent 28%
            ),
            linear-gradient(
                180deg,
                color-mix(in srgb, var(--accent-color), transparent 96%) 0%,
                var(--background-color) 100%
            );
    }

    .ps-page-hero::after {
        content: '';
        position: absolute;
        width: 430px;
        height: 430px;
        right: -190px;
        bottom: -280px;
        border-radius: 50%;
        border: 1px solid color-mix(in srgb, var(--accent-color), transparent 78%);
        pointer-events: none;
    }

    .ps-page-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 14px;
        color: var(--accent-color);
        font-size: .82rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .055em;
    }

    .ps-page-hero h1 {
        max-width: 940px;
        margin: 0 0 16px;
        color: var(--heading-color);
        font-size: clamp(2.35rem, 5vw, 4rem);
        line-height: 1.08;
        letter-spacing: -.045em;
        font-weight: 800;
    }

    .ps-page-hero > .container > p {
        max-width: 820px;
        margin: 0;
        color: color-mix(in srgb, var(--default-color), transparent 18%);
        font-size: 1.08rem;
        line-height: 1.75;
    }

    .ps-privacy-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10px 18px;
        margin-top: 22px;
    }

    .ps-privacy-meta span {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 7px 11px;
        border: 1px solid color-mix(in srgb, var(--accent-color), transparent 78%);
        border-radius: 999px;
        background: color-mix(in srgb, var(--accent-color), transparent 92%);
        color: color-mix(in srgb, var(--default-color), transparent 8%);
        font-size: .86rem;
        font-weight: 600;
    }

    .ps-privacy-section {
        padding: 76px 0 96px;
    }

    .ps-privacy-index {
        position: sticky;
        top: 110px;
        padding: 24px;
        border: 1px solid color-mix(in srgb, var(--default-color), transparent 88%);
        border-radius: 16px;
        background: var(--surface-color);
        box-shadow: 0 12px 34px rgba(15, 23, 42, .055);
    }

    .ps-privacy-index strong {
        display: flex;
        align-items: center;
        gap: 8px;
        padding-bottom: 14px;
        margin-bottom: 10px;
        color: var(--heading-color);
        border-bottom: 1px solid color-mix(in srgb, var(--default-color), transparent 90%);
        font-size: .98rem;
        font-weight: 800;
    }

    .ps-privacy-index nav {
        display: grid;
        gap: 3px;
    }

    .ps-privacy-index a {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 9px 10px;
        border-radius: 8px;
        color: color-mix(in srgb, var(--default-color), transparent 12%);
        font-size: .91rem;
        line-height: 1.35;
        text-decoration: none;
        transition:
            color .2s ease,
            background .2s ease,
            transform .2s ease;
    }

    .ps-privacy-index a:hover {
        color: var(--accent-color);
        background: color-mix(in srgb, var(--accent-color), transparent 93%);
        transform: translateX(2px);
    }

    .ps-privacy-index a i {
        flex: 0 0 auto;
        color: var(--accent-color);
        font-size: .82rem;
    }

    .ps-privacy-article {
        padding: 34px;
        border: 1px solid color-mix(in srgb, var(--default-color), transparent 89%);
        border-radius: 18px;
        background: var(--surface-color);
        box-shadow: 0 14px 38px rgba(15, 23, 42, .05);
    }

    .ps-privacy-article section {
        scroll-margin-top: 110px;
    }

    .ps-privacy-article section + section {
        margin-top: 42px;
        padding-top: 40px;
        border-top: 1px solid color-mix(in srgb, var(--default-color), transparent 90%);
    }

    .ps-privacy-article h2 {
        margin: 0 0 17px;
        color: var(--heading-color);
        font-size: clamp(1.42rem, 2.4vw, 1.85rem);
        line-height: 1.25;
        letter-spacing: -.02em;
        font-weight: 800;
    }

    .ps-privacy-article h3 {
        margin: 26px 0 10px;
        color: var(--heading-color);
        font-size: 1.08rem;
        font-weight: 800;
    }

    .ps-privacy-article p,
    .ps-privacy-article li {
        color: color-mix(in srgb, var(--default-color), transparent 13%);
        line-height: 1.78;
    }

    .ps-privacy-article p {
        margin-bottom: 15px;
    }

    .ps-privacy-article ul {
        margin: 16px 0 20px;
        padding-left: 1.35rem;
    }

    .ps-privacy-article li + li {
        margin-top: 9px;
    }

    .ps-privacy-article strong {
        color: color-mix(in srgb, var(--heading-color), transparent 2%);
    }

    .ps-privacy-article a {
        color: var(--accent-color);
        font-weight: 700;
        text-decoration: none;
    }

    .ps-privacy-article a:hover {
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .ps-legal-warning {
        display: flex;
        gap: 13px;
        align-items: flex-start;
        margin-bottom: 30px;
        padding: 16px 18px;
        border: 1px solid #fde68a;
        border-radius: 11px;
        background: #fffbeb;
        color: #92400e;
        line-height: 1.6;
    }

    .ps-legal-warning > i {
        flex: 0 0 auto;
        margin-top: 2px;
        font-size: 1.2rem;
    }

    .ps-legal-warning strong {
        color: #78350f;
    }

    .ps-contact-panel {
        display: grid;
        gap: 7px;
        margin-top: 22px;
        padding: 22px;
        border: 1px solid color-mix(in srgb, var(--accent-color), transparent 82%);
        border-radius: 14px;
        background: color-mix(in srgb, var(--accent-color), transparent 93%);
    }

    .ps-contact-panel strong {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 5px;
        color: var(--heading-color);
        font-size: 1rem;
    }

    .ps-contact-panel span {
        color: color-mix(in srgb, var(--default-color), transparent 10%);
        line-height: 1.55;
        overflow-wrap: anywhere;
    }

    .ps-legal-note {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        margin-top: 42px;
        padding: 20px 22px;
        border: 1px solid color-mix(in srgb, var(--default-color), transparent 88%);
        border-radius: 13px;
        background: color-mix(in srgb, var(--default-color), transparent 96%);
        color: color-mix(in srgb, var(--default-color), transparent 16%);
        line-height: 1.68;
        font-size: .93rem;
    }

    .ps-legal-note > i {
        flex: 0 0 auto;
        margin-top: 2px;
        color: var(--accent-color);
        font-size: 1.2rem;
    }

    .ps-privacy-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 22px;
    }

    .ps-privacy-button {
        min-height: 45px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 0 17px;
        border-radius: 8px;
        font-size: .91rem;
        font-weight: 700;
        text-decoration: none !important;
    }

    .ps-privacy-button-primary {
        background: var(--accent-color);
        color: var(--contrast-color) !important;
    }

    .ps-privacy-button-outline {
        border: 1px solid color-mix(in srgb, var(--accent-color), transparent 64%);
        background: transparent;
        color: var(--accent-color) !important;
    }

    @media (max-width: 991.98px) {
        .ps-privacy-index {
            position: static;
            margin-bottom: 8px;
        }

        .ps-privacy-index nav {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .ps-page-hero {
            padding: 124px 0 58px;
        }

        .ps-privacy-section {
            padding: 58px 0 76px;
        }

        .ps-privacy-index,
        .ps-privacy-article {
            padding: 23px;
        }

        .ps-privacy-index nav {
            grid-template-columns: 1fr;
        }

        .ps-privacy-article section + section {
            margin-top: 34px;
            padding-top: 32px;
        }
    }
</style>
@endpush

@section('content')
@php
    $legalName = (string) config('schoolpass_public.legal_name');
    $legalAddress = (string) config('schoolpass_public.legal_address');

    $configurationPending =
        str_contains($legalName, '[')
        || str_contains($legalAddress, '[');
@endphp

<section class="ps-page-hero">
    <div class="container position-relative">
        <span class="ps-page-eyebrow">
            <i class="bi bi-shield-lock"></i>
            Privacidad y protección de datos
        </span>

        <h1>Aviso de Privacidad Integral de PaseLista</h1>

        <p>
            Aplicable al portal web, PaseLista Staff, PaseLista Family
            y servicios asociados.
        </p>

        <div class="ps-privacy-meta">
            <span>
                <i class="bi bi-file-earmark-text"></i>
                Versión {{ config('schoolpass_public.privacy_version') }}
            </span>

            <span>
                <i class="bi bi-calendar3"></i>
                Última actualización: {{ config('schoolpass_public.privacy_updated_at') }}
            </span>
        </div>
    </div>
</section>

<section class="ps-privacy-section">
    <div class="container">

        <div class="row g-4 g-lg-5 align-items-start">

            <aside class="col-lg-3">
                <div class="ps-privacy-index">
                    <strong>
                        <i class="bi bi-list-ul"></i>
                        Contenido
                    </strong>

                    <nav aria-label="Contenido del aviso de privacidad">
                        <a href="#identidad">
                            <span>Identidad y roles</span>
                            <i class="bi bi-chevron-right"></i>
                        </a>

                        <a href="#datos">
                            <span>Datos tratados</span>
                            <i class="bi bi-chevron-right"></i>
                        </a>

                        <a href="#finalidades">
                            <span>Finalidades</span>
                            <i class="bi bi-chevron-right"></i>
                        </a>

                        <a href="#terceros">
                            <span>Proveedores y transferencias</span>
                            <i class="bi bi-chevron-right"></i>
                        </a>

                        <a href="#conservacion">
                            <span>Conservación</span>
                            <i class="bi bi-chevron-right"></i>
                        </a>

                        <a href="#derechos">
                            <span>Derechos ARCO</span>
                            <i class="bi bi-chevron-right"></i>
                        </a>

                        <a href="#seguridad">
                            <span>Seguridad</span>
                            <i class="bi bi-chevron-right"></i>
                        </a>

                        <a href="#aplicaciones">
                            <span>Apps y permisos</span>
                            <i class="bi bi-chevron-right"></i>
                        </a>

                        <a href="#cookies">
                            <span>Cookies y almacenamiento</span>
                            <i class="bi bi-chevron-right"></i>
                        </a>

                        <a href="#cambios">
                            <span>Cambios y contacto</span>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </nav>
                </div>
            </aside>

            <div class="col-lg-9">
                <article class="ps-privacy-article">

                    @if($configurationPending && app()->environment('local'))
                        <div class="ps-legal-warning">
                            <i class="bi bi-exclamation-triangle-fill"></i>

                            <div>
                                <strong>Configuración jurídica pendiente.</strong><br>
                                Antes de publicar, define
                                <code>SCHOOLPASS_PUBLIC_LEGAL_NAME</code>
                                y
                                <code>SCHOOLPASS_PUBLIC_LEGAL_ADDRESS</code>
                                en el archivo <code>.env</code>.
                            </div>
                        </div>
                    @endif

                    <section id="identidad">
                        <h2>1. Identidad del responsable y distribución de funciones</h2>

                        <p>
                            <strong>{{ $legalName }}</strong>, con domicilio para efectos de privacidad en
                            <strong>{{ $legalAddress }}</strong>, opera la plataforma tecnológica
                            identificada comercialmente como <strong>PaseLista</strong> y pone a disposición
                            el presente aviso.
                        </p>

                        <p>
                            PaseLista se utiliza por instituciones educativas mediante una relación contractual.
                            La función jurídica respecto de los datos depende de la operación concreta:
                        </p>

                        <ul>
                            <li>
                                <strong>La institución educativa contratante actúa como responsable</strong>
                                respecto de los datos de alumnos, tutores, personal, asistencia, accesos,
                                horarios, grupos, autorizaciones de recogida y demás información escolar,
                                porque determina las finalidades, reglas operativas y personas autorizadas.
                            </li>

                            <li>
                                <strong>PaseLista actúa principalmente como persona encargada</strong>
                                cuando almacena, organiza, consulta, comunica o elimina datos escolares
                                por cuenta e instrucciones de la institución.
                            </li>

                            <li>
                                <strong>PaseLista actúa como responsable independiente</strong>
                                respecto de datos necesarios para seguridad de la plataforma,
                                administración de licencias, soporte técnico, prevención de abuso,
                                facturación, continuidad operativa y atención de solicitudes dirigidas
                                directamente al proveedor.
                            </li>
                        </ul>

                        <p>
                            Esta distribución no limita los derechos de las personas titulares.
                            Cuando una solicitud corresponda a datos controlados por la escuela,
                            PaseLista la canalizará a la institución o colaborará técnicamente
                            para atenderla.
                        </p>
                    </section>

                    <section id="datos">
                        <h2>2. Datos personales que pueden ser tratados</h2>

                        <p>
                            Dependiendo de la configuración contratada y del rol de la persona usuaria,
                            se pueden tratar las siguientes categorías:
                        </p>

                        <ul>
                            <li>
                                <strong>Datos de identificación:</strong>
                                nombre, fotografía, código o matrícula escolar, relación con el alumno,
                                cargo y rol dentro de la institución.
                            </li>

                            <li>
                                <strong>Datos de contacto:</strong>
                                correo electrónico, teléfono y medios definidos por la institución.
                            </li>

                            <li>
                                <strong>Datos escolares:</strong>
                                plantel, nivel, grado, grupo, ciclo, horario, inscripción y estado
                                académico-operativo necesario para accesos.
                            </li>

                            <li>
                                <strong>Datos de acceso y asistencia:</strong>
                                entradas, salidas, puntualidad, retardos, ausencias, salidas anticipadas,
                                incidencias, autorizaciones, dispositivo o punto de acceso utilizado.
                            </li>

                            <li>
                                <strong>Credenciales:</strong>
                                códigos QR, identificadores de tarjetas NFC, pases temporales
                                y estado de vigencia o revocación.
                            </li>

                            <li>
                                <strong>Datos de tutores:</strong>
                                vínculo con el alumno, permisos de recogida, credencial
                                y fotografía de referencia cuando la escuela la habilite.
                            </li>

                            <li>
                                <strong>Datos técnicos:</strong>
                                dirección IP, identificadores internos, versión de la aplicación,
                                sistema operativo, registros de sesión, eventos de seguridad,
                                tokens de notificaciones y diagnósticos.
                            </li>

                            <li>
                                <strong>Soporte y comunicaciones:</strong>
                                consultas, incidencias, archivos o evidencia enviados voluntariamente
                                para resolver un problema.
                            </li>

                            <li>
                                <strong>PaseLista IA:</strong>
                                preguntas administrativas, alcance seleccionado, periodos analizados,
                                métricas escolares minimizadas o seudonimizadas, respuesta generada,
                                tokens, costos y registros técnicos de la ejecución.
                            </li>
                        </ul>

                        <p>
                            PaseLista no requiere que se introduzcan datos de salud, discapacidad,
                            religión, origen étnico, información financiera o datos biométricos
                            para identificación automatizada. La fotografía de un tutor o alumno
                            se utiliza como referencia visual y no como reconocimiento facial,
                            salvo que en el futuro exista una función distinta expresamente informada
                            y autorizada.
                        </p>

                        <p>
                            Los datos de niñas, niños y adolescentes reciben protección reforzada.
                            Sus cuentas no deben ser creadas ni administradas libremente por terceros;
                            la institución y las personas tutoras autorizadas determinan el acceso
                            conforme a la relación escolar.
                        </p>
                    </section>

                    <section id="finalidades">
                        <h2>3. Finalidades del tratamiento</h2>

                        <h3>Finalidades primarias</h3>

                        <ul>
                            <li>Crear y administrar escuelas, campus, ciclos, niveles, grupos, usuarios y roles.</li>
                            <li>Emitir, validar, revocar y controlar credenciales QR, NFC o pases temporales.</li>
                            <li>Registrar entradas, salidas, asistencia, puntualidad, retardos, incidencias y entregas a tutores autorizados.</li>
                            <li>Notificar a tutores y personal escolar sobre eventos habilitados por la institución.</li>
                            <li>Generar reportes administrativos, históricos y operativos.</li>
                            <li>Permitir análisis de información escolar mediante PaseLista IA, con minimización y protección de datos, sin sustituir la revisión humana.</li>
                            <li>Autenticar usuarios, mantener sesiones, prevenir accesos no autorizados, investigar incidentes y proteger la integridad del servicio.</li>
                            <li>Dar soporte, mantener la plataforma, administrar licencias y atender obligaciones contractuales o legales.</li>
                        </ul>

                        <h3>Finalidades secundarias</h3>

                        <p>
                            Los datos de contacto comercial de representantes de instituciones podrán
                            utilizarse para seguimiento de demostraciones, propuestas, renovaciones
                            o información sobre funciones relacionadas. La persona podrá oponerse
                            escribiendo a
                            <a href="mailto:{{ config('schoolpass_public.privacy_email') }}">
                                {{ config('schoolpass_public.privacy_email') }}
                            </a>.
                        </p>

                        <h3>Decisiones automatizadas e inteligencia artificial</h3>

                        <p>
                            PaseLista IA produce resúmenes y hallazgos de apoyo. No toma por sí sola
                            decisiones disciplinarias, académicas, laborales ni de seguridad.
                            La institución debe revisar la evidencia antes de actuar.
                            Las consultas pueden ser procesadas por un proveedor de modelos de
                            inteligencia artificial después de aplicar medidas de minimización,
                            seudonimización o redacción de datos personales cuando corresponda.
                        </p>
                    </section>

                    <section id="terceros">
                        <h2>4. Personas encargadas, proveedores y transferencias</h2>

                        <p>
                            PaseLista puede utilizar proveedores que tratan información únicamente
                            para prestar el servicio, tales como infraestructura de nube y alojamiento,
                            envío de correo, notificaciones push, almacenamiento, monitoreo, respaldo
                            y procesamiento de inteligencia artificial. Entre las tecnologías que pueden
                            intervenir se encuentran servicios de Google/Firebase y DeepSeek, de acuerdo
                            con las funciones habilitadas.
                        </p>

                        <p>
                            También pueden comunicarse datos a la institución educativa contratante,
                            a tutores vinculados, a personal autorizado o a autoridades competentes
                            cuando exista una obligación legal, orden fundada o necesidad de proteger
                            derechos y seguridad.
                        </p>

                        <p>
                            <strong>PaseLista no vende datos personales.</strong>
                            Los proveedores están sujetos a obligaciones de confidencialidad, seguridad,
                            uso limitado y tratamiento conforme a instrucciones.
                        </p>
                    </section>

                    <section id="conservacion">
                        <h2>5. Conservación, bloqueo y eliminación</h2>

                        <p>
                            Los datos escolares se conservan mientras exista una relación activa con la
                            institución y durante el tiempo necesario para cumplir las finalidades informadas,
                            atender obligaciones contractuales, resolver incidencias, mantener trazabilidad
                            o cumplir disposiciones legales. La institución define los periodos académicos
                            y administrativos aplicables a sus expedientes.
                        </p>

                        <p>
                            Cuando los datos dejan de ser necesarios, se bloquean y posteriormente
                            se eliminan o anonimizan conforme a las instrucciones de la institución,
                            los plazos contractuales y las obligaciones aplicables. Las copias de respaldo
                            pueden conservar temporalmente información eliminada hasta completar su ciclo
                            normal de rotación, sin utilizarse para finalidades ordinarias.
                        </p>

                        <p>
                            Los tokens de notificaciones se revocan o sustituyen cuando dejan de ser válidos.
                            Los registros técnicos, de seguridad, soporte y auditoría se conservan durante
                            el tiempo razonablemente necesario para investigar incidentes, demostrar
                            cumplimiento y mantener la continuidad del servicio.
                        </p>
                    </section>

                    <section id="derechos">
                        <h2>6. Derechos ARCO, revocación y limitación</h2>

                        <p>
                            Las personas titulares o sus representantes pueden solicitar Acceso,
                            Rectificación, Cancelación u Oposición; revocar su consentimiento cuando proceda;
                            limitar el uso o divulgación; solicitar una copia de sus datos o pedir la eliminación
                            de una cuenta y datos asociados.
                        </p>

                        <p>
                            Para datos escolares, la vía principal es la institución educativa que administra
                            la cuenta. También puede utilizarse el formulario público de PaseLista,
                            que identificará la institución correspondiente y dará seguimiento técnico.
                        </p>

                        <p>La solicitud debe incluir:</p>

                        <ul>
                            <li>Nombre de la persona titular o representante.</li>
                            <li>Medio para comunicar la respuesta.</li>
                            <li>Institución educativa y referencia de cuenta, cuando aplique.</li>
                            <li>Descripción clara del derecho que desea ejercer y de los datos involucrados.</li>
                            <li>Documentos necesarios para acreditar identidad, representación o vínculo con un menor, los cuales se solicitarán por un canal seguro.</li>
                        </ul>

                        <p>
                            PaseLista comunicará la determinación dentro del plazo legal aplicable y,
                            cuando resulte procedente, la hará efectiva dentro del plazo correspondiente.
                        </p>

                        <div class="ps-privacy-actions">
                            <a
                                href="{{ route('public.data-deletion') }}"
                                class="ps-privacy-button ps-privacy-button-primary"
                            >
                                <i class="bi bi-person-check"></i>
                                Iniciar solicitud ARCO
                            </a>

                            <a
                                href="mailto:{{ config('schoolpass_public.privacy_email') }}"
                                class="ps-privacy-button ps-privacy-button-outline"
                            >
                                <i class="bi bi-envelope"></i>
                                {{ config('schoolpass_public.privacy_email') }}
                            </a>
                        </div>
                    </section>

                    <section id="seguridad">
                        <h2>7. Seguridad y confidencialidad</h2>

                        <p>
                            PaseLista aplica medidas administrativas y técnicas razonables según el riesgo,
                            entre ellas autenticación, permisos por rol, separación lógica por institución,
                            cifrado de comunicaciones mediante HTTPS, registros de auditoría,
                            restricciones de acceso, respaldos y mecanismos de revocación de credenciales.
                        </p>

                        <p>
                            Ningún sistema es completamente infalible. Ante un incidente que pueda afectar
                            significativamente los derechos de las personas titulares, se aplicarán los
                            procedimientos de contención, investigación y comunicación que correspondan.
                        </p>
                    </section>

                    <section id="aplicaciones">
                        <h2>8. Aplicaciones móviles y permisos</h2>

                        <p>
                            PaseLista Staff puede solicitar acceso a la cámara para escanear códigos QR y,
                            cuando se habilite, a funciones NFC. PaseLista Family puede utilizar cámara
                            o archivos únicamente para funciones visibles como fotografía de perfil,
                            credencial o evidencia solicitada. Los permisos se piden cuando son necesarios
                            para la función correspondiente.
                        </p>

                        <p>
                            Las aplicaciones pueden utilizar notificaciones push y recopilar diagnósticos
                            técnicos para entregar avisos y resolver fallas. La declaración de Seguridad
                            de los datos publicada en Google Play debe ser consistente con este aviso
                            y con el comportamiento real de cada aplicación.
                        </p>
                    </section>

                    <section id="cookies">
                        <h2>9. Cookies y almacenamiento local</h2>

                        <p>
                            El sitio público puede usar almacenamiento local estrictamente necesario
                            para recordar preferencias de interfaz, como tema claro u oscuro.
                            No se utilizan cookies publicitarias ni perfiles comerciales en esta versión
                            del sitio. Si se incorporan herramientas de analítica o marketing,
                            se actualizará este aviso y se implementarán los controles correspondientes.
                        </p>
                    </section>

                    <section id="cambios">
                        <h2>10. Cambios al aviso y contacto</h2>

                        <p>
                            Las modificaciones se publicarán en esta misma dirección, indicando versión
                            y fecha de actualización. Cuando el cambio sea material, podrá comunicarse
                            también dentro de la plataforma o por los medios de contacto registrados.
                        </p>

                        <div class="ps-contact-panel">
                            <strong>
                                <i class="bi bi-envelope-shield"></i>
                                Contacto de privacidad
                            </strong>

                            <span>{{ config('schoolpass_public.privacy_email') }}</span>
                            <span>{{ config('schoolpass_public.legal_address') }}</span>

                            @if(config('schoolpass_public.phone'))
                                <span>{{ config('schoolpass_public.phone') }}</span>
                            @endif
                        </div>
                    </section>

                    <div class="ps-legal-note">
                        <i class="bi bi-info-circle"></i>

                        <div>
                            Este documento es un borrador operativo diseñado para reflejar las funciones
                            actuales de PaseLista. Debe validarse contra la identidad jurídica real del operador,
                            contratos con escuelas, infraestructura utilizada y configuración final de Staff
                            y Family antes de su publicación definitiva.
                        </div>
                    </div>

                </article>
            </div>

        </div>
    </div>
</section>
@endsection