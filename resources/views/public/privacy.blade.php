@extends('layouts.public-site')

@section('title', 'Aviso de privacidad · PaseLista')
@section('meta-description', 'Aviso de privacidad integral de SchoolPass para aplicaciones Staff y Family.')

@section('content')
@php
    $legalName = (string) config('schoolpass_public.legal_name');
    $legalAddress = (string) config('schoolpass_public.legal_address');
    $configurationPending = str_contains($legalName, '[') || str_contains($legalAddress, '[');
@endphp

<section class="sp-legal-hero">
    <div class="container relative">
        <span class="sp-eyebrow">Privacidad y protección de datos</span>
        <h1>Aviso de Privacidad Integral de PaseLista</h1>
        <p>Aplicable al portal web, PaseLista Staff, PaseLista Family y servicios asociados.</p>
        <div class="sp-legal-meta">
            <span>Versión {{ config('schoolpass_public.privacy_version') }}</span>
            <span>Última actualización: {{ config('schoolpass_public.privacy_updated_at') }}</span>
        </div>
    </div>
</section>

<section class="sp-legal-section">
    <div class="container relative">
        <div class="grid lg:grid-cols-12 grid-cols-1 gap-8">
            <aside class="lg:col-span-3">
                <nav class="sp-legal-index">
                    <strong>Contenido</strong>
                    <a href="#identidad">Identidad y roles</a>
                    <a href="#datos">Datos tratados</a>
                    <a href="#finalidades">Finalidades</a>
                    <a href="#terceros">Proveedores y transferencias</a>
                    <a href="#conservacion">Conservación</a>
                    <a href="#derechos">Derechos ARCO</a>
                    <a href="#seguridad">Seguridad</a>
                    <a href="#cambios">Cambios al aviso</a>
                </nav>
            </aside>

            <article class="lg:col-span-9 sp-legal-article">
                @if($configurationPending && app()->environment('local'))
                    <div class="sp-legal-warning">
                        <strong>Configuración jurídica pendiente.</strong>
                        Antes de publicar, define SCHOOLPASS_PUBLIC_LEGAL_NAME y SCHOOLPASS_PUBLIC_LEGAL_ADDRESS en el archivo <code>.env</code>.
                    </div>
                @endif

                <section id="identidad">
                    <h2>1. Identidad del responsable y distribución de funciones</h2>
                    <p>
                        <strong>{{ $legalName }}</strong>, con domicilio para efectos de privacidad en
                        <strong>{{ $legalAddress }}</strong>, opera la plataforma tecnológica identificada comercialmente como <strong>PaseLista</strong> y pone a disposición el presente aviso.
                    </p>
                    <p>
                        PaseLista se utiliza por instituciones educativas mediante una relación contractual. La función jurídica respecto de los datos depende de la operación concreta:
                    </p>
                    <ul>
                        <li><strong>La institución educativa contratante actúa como responsable</strong> respecto de los datos de alumnos, tutores, personal, asistencia, accesos, horarios, grupos, autorizaciones de recogida y demás información escolar, porque determina las finalidades, reglas operativas y personas autorizadas.</li>
                        <li><strong>PaseLista actúa principalmente como persona encargada</strong> cuando almacena, organiza, consulta, comunica o elimina datos escolares por cuenta e instrucciones de la institución.</li>
                        <li><strong>PaseLista actúa como responsable independiente</strong> respecto de datos necesarios para seguridad de la plataforma, administración de licencias, soporte técnico, prevención de abuso, facturación, continuidad operativa y atención de solicitudes dirigidas directamente al proveedor.</li>
                    </ul>
                    <p>
                        Esta distribución no limita los derechos de las personas titulares. Cuando una solicitud corresponda a datos controlados por la escuela, PaseLista la canalizará a la institución o colaborará técnicamente para atenderla.
                    </p>
                </section>

                <section id="datos">
                    <h2>2. Datos personales que pueden ser tratados</h2>
                    <p>Dependiendo de la configuración contratada y del rol de la persona usuaria, se pueden tratar las siguientes categorías:</p>
                    <ul>
                        <li><strong>Datos de identificación:</strong> nombre, fotografía, código o matrícula escolar, relación con el alumno, cargo y rol dentro de la institución.</li>
                        <li><strong>Datos de contacto:</strong> correo electrónico, teléfono y medios definidos por la institución.</li>
                        <li><strong>Datos escolares:</strong> plantel, nivel, grado, grupo, ciclo, horario, inscripción y estado académico-operativo necesario para accesos.</li>
                        <li><strong>Datos de acceso y asistencia:</strong> entradas, salidas, puntualidad, retardos, ausencias, salidas anticipadas, incidencias, autorizaciones, dispositivo o punto de acceso utilizado.</li>
                        <li><strong>Credenciales:</strong> códigos QR, identificadores de tarjetas NFC, pases temporales y estado de vigencia o revocación.</li>
                        <li><strong>Datos de tutores:</strong> vínculo con el alumno, permisos de recogida, credencial y fotografía de referencia cuando la escuela la habilite.</li>
                        <li><strong>Datos técnicos:</strong> dirección IP, identificadores internos, versión de la aplicación, sistema operativo, registros de sesión, eventos de seguridad, tokens de notificaciones y diagnósticos.</li>
                        <li><strong>Soporte y comunicaciones:</strong> consultas, incidencias, archivos o evidencia enviados voluntariamente para resolver un problema.</li>
                        <li><strong>PaseLista IA:</strong> preguntas administrativas, alcance seleccionado, periodos analizados, métricas escolares minimizadas o seudonimizadas, respuesta generada, tokens, costos y registros técnicos de la ejecución.</li>
                    </ul>
                    <p>
                        PaseLista no requiere que se introduzcan datos de salud, discapacidad, religión, origen étnico, información financiera o datos biométricos para identificación automatizada. La fotografía de un tutor o alumno se utiliza como referencia visual y no como reconocimiento facial, salvo que en el futuro exista una función distinta expresamente informada y autorizada.
                    </p>
                    <p>
                        Los datos de niñas, niños y adolescentes reciben protección reforzada. Sus cuentas no deben ser creadas ni administradas libremente por terceros; la institución y las personas tutoras autorizadas determinan el acceso conforme a la relación escolar.
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
                        Los datos de contacto comercial de representantes de instituciones podrán utilizarse para seguimiento de demostraciones, propuestas, renovaciones o información sobre funciones relacionadas. La persona podrá oponerse escribiendo a
                        <a href="mailto:{{ config('schoolpass_public.privacy_email') }}">{{ config('schoolpass_public.privacy_email') }}</a>.
                    </p>

                    <h3>Decisiones automatizadas e inteligencia artificial</h3>
                    <p>
                        PaseLista IA produce resúmenes y hallazgos de apoyo. No toma por sí sola decisiones disciplinarias, académicas, laborales ni de seguridad. La institución debe revisar la evidencia antes de actuar. Las consultas pueden ser procesadas por un proveedor de modelos de inteligencia artificial después de aplicar medidas de minimización, seudonimización o redacción de datos personales cuando corresponda.
                    </p>
                </section>

                <section id="terceros">
                    <h2>4. Personas encargadas, proveedores y transferencias</h2>
                    <p>
                        PaseLista puede utilizar proveedores que tratan información únicamente para prestar el servicio, tales como infraestructura de nube y alojamiento, envío de correo, notificaciones push, almacenamiento, monitoreo, respaldo y procesamiento de inteligencia artificial. Entre las tecnologías que pueden intervenir se encuentran servicios de Google/Firebase y DeepSeek, de acuerdo con las funciones habilitadas.
                    </p>
                    <p>
                        También pueden comunicarse datos a la institución educativa contratante, a tutores vinculados, a personal autorizado o a autoridades competentes cuando exista una obligación legal, orden fundada o necesidad de proteger derechos y seguridad.
                    </p>
                    <p><strong>PaseLista no vende datos personales.</strong> Los proveedores están sujetos a obligaciones de confidencialidad, seguridad, uso limitado y tratamiento conforme a instrucciones.</p>
                </section>

                <section id="conservacion">
                    <h2>5. Conservación, bloqueo y eliminación</h2>
                    <p>
                        Los datos escolares se conservan mientras exista una relación activa con la institución y durante el tiempo necesario para cumplir las finalidades informadas, atender obligaciones contractuales, resolver incidencias, mantener trazabilidad o cumplir disposiciones legales. La institución define los periodos académicos y administrativos aplicables a sus expedientes.
                    </p>
                    <p>
                        Cuando los datos dejan de ser necesarios, se bloquean y posteriormente se eliminan o anonimizan conforme a las instrucciones de la institución, los plazos contractuales y las obligaciones aplicables. Las copias de respaldo pueden conservar temporalmente información eliminada hasta completar su ciclo normal de rotación, sin utilizarse para finalidades ordinarias.
                    </p>
                    <p>
                        Los tokens de notificaciones se revocan o sustituyen cuando dejan de ser válidos. Los registros técnicos, de seguridad, soporte y auditoría se conservan durante el tiempo razonablemente necesario para investigar incidentes, demostrar cumplimiento y mantener la continuidad del servicio.
                    </p>
                </section>

                <section id="derechos">
                    <h2>6. Derechos ARCO, revocación y limitación</h2>
                    <p>
                        Las personas titulares o sus representantes pueden solicitar Acceso, Rectificación, Cancelación u Oposición; revocar su consentimiento cuando proceda; limitar el uso o divulgación; solicitar una copia de sus datos o pedir la eliminación de una cuenta y datos asociados.
                    </p>
                    <p>
                        Para datos escolares, la vía principal es la institución educativa que administra la cuenta. También puede utilizarse el formulario público de PaseLista, que identificará la institución correspondiente y dará seguimiento técnico.
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
                        PaseLista comunicará la determinación dentro del plazo legal aplicable y, cuando resulte procedente, la hará efectiva dentro del plazo correspondiente. Para iniciar una solicitud visita
                        <a href="{{ route('public.data-deletion') }}">Derechos ARCO y eliminación de datos</a>
                        o escribe a
                        <a href="mailto:{{ config('schoolpass_public.privacy_email') }}">{{ config('schoolpass_public.privacy_email') }}</a>.
                    </p>
                </section>

                <section id="seguridad">
                    <h2>7. Seguridad y confidencialidad</h2>
                    <p>
                        PaseLista aplica medidas administrativas y técnicas razonables según el riesgo, entre ellas autenticación, permisos por rol, separación lógica por institución, cifrado de comunicaciones mediante HTTPS, registros de auditoría, restricciones de acceso, respaldos y mecanismos de revocación de credenciales.
                    </p>
                    <p>
                        Ningún sistema es completamente infalible. Ante un incidente que pueda afectar significativamente los derechos de las personas titulares, se aplicarán los procedimientos de contención, investigación y comunicación que correspondan.
                    </p>
                </section>

                <section>
                    <h2>8. Aplicaciones móviles y permisos</h2>
                    <p>
                        PaseLista Staff puede solicitar acceso a la cámara para escanear códigos QR y, cuando se habilite, a funciones NFC. PaseLista Family puede utilizar cámara o archivos únicamente para funciones visibles como fotografía de perfil, credencial o evidencia solicitada. Los permisos se piden cuando son necesarios para la función correspondiente.
                    </p>
                    <p>
                        Las aplicaciones pueden utilizar notificaciones push y recopilar diagnósticos técnicos para entregar avisos y resolver fallas. La declaración de Seguridad de los datos publicada en Google Play debe ser consistente con este aviso y con el comportamiento real de cada aplicación.
                    </p>
                </section>

                <section>
                    <h2>9. Cookies y almacenamiento local</h2>
                    <p>
                        El sitio público puede usar almacenamiento local estrictamente necesario para recordar preferencias de interfaz, como tema claro u oscuro. No se utilizan cookies publicitarias ni perfiles comerciales en esta versión del sitio. Si se incorporan herramientas de analítica o marketing, se actualizará este aviso y se implementarán los controles correspondientes.
                    </p>
                </section>

                <section id="cambios">
                    <h2>10. Cambios al aviso y contacto</h2>
                    <p>
                        Las modificaciones se publicarán en esta misma dirección, indicando versión y fecha de actualización. Cuando el cambio sea material, podrá comunicarse también dentro de la plataforma o por los medios de contacto registrados.
                    </p>
                    <div class="sp-contact-panel">
                        <strong>Contacto de privacidad</strong>
                        <span>{{ config('schoolpass_public.privacy_email') }}</span>
                        <span>{{ config('schoolpass_public.legal_address') }}</span>
                        @if(config('schoolpass_public.phone'))
                            <span>{{ config('schoolpass_public.phone') }}</span>
                        @endif
                    </div>
                </section>

                <div class="sp-legal-note">
                    Este documento es un borrador operativo diseñado para reflejar las funciones actuales de PaseLista. Debe validarse contra la identidad jurídica real del operador, contratos con escuelas, infraestructura utilizada y configuración final de Staff y Family antes de su publicación definitiva.
                </div>
            </article>
        </div>
    </div>
</section>
@endsection
