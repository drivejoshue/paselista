import React, { useState } from 'react';
import { createRoot } from 'react-dom/client';
import {
    ArrowDown, ArrowRight, ArrowUpRight, BookOpen, CalendarCheck2,
    Check, CheckCircle2, ChevronDown, ClipboardList, FileCheck2,
    FileText, GraduationCap, LayoutDashboard, LockKeyhole,
    Menu, MonitorSmartphone, PanelsTopLeft, ScanLine, School,
    ShieldCheck, UsersRound, X,
} from 'lucide-react';
import '../../css/schoolcore-public.css';

type SiteConfig = {
    name: string;
    assets: string;
    login: string;
    support: string;
    privacy: string;
    deletion: string;
    demo: string;
    paselista: string;
};

type ScreenId = 'schoolcore' | 'academic' | 'teacher' | 'family';

const screenshots: Array<{
    id: ScreenId;
    title: string;
    eyebrow: string;
    description: string;
    image: string;
    alt: string;
}> = [
    {
        id: 'schoolcore',
        title: 'El centro de toda la institución',
        eyebrow: 'Gestión institucional',
        description: 'Un espacio para organizar alumnos, familias, ciclos escolares, grupos, usuarios y módulos desde una sola plataforma.',
        image: 'dashboard.webp',
        alt: 'Panel general de SchoolCore con resumen institucional y módulos',
    },
    {
        id: 'academic',
        title: 'Evaluaciones con un flujo claro',
        eyebrow: 'SchoolCore Academic',
        description: 'Captura docente, envío, validación, publicación y exportación de calificaciones con control de permisos.',
        image: 'academic.webp',
        alt: 'Panel de validación y publicación de calificaciones de SchoolCore Academic',
    },
    {
        id: 'teacher',
        title: 'Un portal enfocado en el docente',
        eyebrow: 'Portal docente',
        description: 'Cada docente visualiza sus grupos y materias, captura resultados y comparte libros de calificaciones cuando existe codocencia.',
        image: 'capture.webp',
        alt: 'Vista de captura de calificaciones en el portal docente',
    },
    {
        id: 'family',
        title: 'Información accesible para las familias',
        eyebrow: 'Portal escolar',
        description: 'Las instituciones pueden publicar resultados para consulta individual y descarga en PDF desde un portal con su identidad.',
        image: 'family.webp',
        alt: 'Vista de reporte académico individual del portal familiar',
    },
];

const frequentlyAsked = [
    {
        question: '¿Puedo comenzar con pocos módulos e incorporar otros después?',
        answer: 'Sí. SchoolCore está organizado para que cada institución utilice los módulos que necesita y amplíe sus servicios conforme cambian sus procesos y prioridades.',
    },
    {
        question: '¿Los módulos comparten la información de la escuela?',
        answer: 'El núcleo institucional concentra alumnos, grupos, ciclos y responsables. Cada módulo utiliza la información que le corresponde, con permisos según el perfil de usuario.',
    },
    {
        question: '¿SchoolCore se adapta a escuelas de distintos tamaños?',
        answer: 'La estructura modular permite organizar desde operaciones sencillas hasta flujos escolares con más grupos, usuarios y áreas administrativas, según la configuración institucional.',
    },
    {
        question: '¿Cada usuario ve solamente las funciones que necesita?',
        answer: 'Sí. Los accesos se organizan por roles, de modo que dirección, docentes, personal administrativo y familias tengan espacios acordes con sus responsabilidades.',
    },
    {
        question: '¿Las familias pueden acceder desde su celular?',
        answer: 'Los servicios web de la plataforma pueden consultarse desde un navegador compatible. Las funciones disponibles para cada familia dependen de lo que habilite su institución.',
    },
    {
        question: '¿La plataforma puede mostrar la identidad de mi escuela?',
        answer: 'SchoolCore permite utilizar la identidad institucional, como nombre, escudo y colores, en los espacios escolares que cuentan con personalización.',
    },
];

function Brand({ config, dark = false }: { config: SiteConfig; dark?: boolean }) {
    return (
        <a className="sc-site-brand" href="#inicio" aria-label={`${config.name}, ir al inicio`}>
            <img
                src={`${config.assets}/${dark ? 'logo-white.webp' : 'logo-dark.webp'}`}
                alt={config.name}
                width="210"
                height="64"
            />
        </a>
    );
}

function Header({ config }: { config: SiteConfig }) {
    const [menuOpen, setMenuOpen] = useState(false);
    const close = () => setMenuOpen(false);

    return (
        <header className="sc-site-header">
            <div className="sc-site-container sc-header-inner">
                <Brand config={config} dark />
                <nav className={`sc-header-nav ${menuOpen ? 'is-open' : ''}`} aria-label="Navegación principal">
                    <a href="#plataforma" onClick={close}>Plataforma</a>
                    <a href="#modulos" onClick={close}>Módulos</a>
                    <a href="#experiencias" onClick={close}>Vistas reales</a>
                    <a href="#preguntas" onClick={close}>Preguntas</a>
                    <div className="sc-header-mobile-actions">
                        <a href={config.login} className="sc-btn sc-btn-solid">Iniciar sesión <ArrowRight size={16} /></a>
                    </div>
                </nav>
                <a href={config.login} className="sc-header-login">Acceder <ArrowUpRight size={16} /></a>
                <button
                    className="sc-menu-toggle"
                    type="button"
                    onClick={() => setMenuOpen(v => !v)}
                    aria-expanded={menuOpen}
                    aria-label={menuOpen ? 'Cerrar menú' : 'Abrir menú'}
                >
                    {menuOpen ? <X size={23} /> : <Menu size={23} />}
                </button>
            </div>
        </header>
    );
}

function Hero({ config }: { config: SiteConfig }) {
    return (
        <section id="inicio" className="sc-hero">
            <div className="sc-hero-glow sc-hero-glow-one" aria-hidden="true" />
            <div className="sc-hero-glow sc-hero-glow-two" aria-hidden="true" />
            <div className="sc-site-container sc-hero-layout">
                <div className="sc-hero-content">
                    <div className="sc-eyebrow sc-eyebrow-light"><span className="sc-live-dot" /> Una plataforma. Toda tu escuela.</div>
                    <h1>Tu escuela, <span>conectada.</span><br />Tu gestión, <span>bajo control.</span></h1>
                    <p>
                        Administración escolar, docentes, familias y servicios académicos
                        en un ecosistema modular pensado para la operación real de una institución.
                    </p>
                    <div className="sc-hero-buttons">
                        <a className="sc-btn sc-btn-solid" href="#modulos">Conocer la plataforma <ArrowRight size={18} /></a>
                        <a className="sc-btn sc-btn-glass" href={config.login}>Entrar a SchoolCore <ArrowUpRight size={17} /></a>
                    </div>
                    <div className="sc-hero-proof">
                        <span><CheckCircle2 size={16} /> Plataforma modular</span>
                        <span><CheckCircle2 size={16} /> Accesos por roles</span>
                        <span><CheckCircle2 size={16} /> Identidad por escuela</span>
                    </div>
                </div>
                <div className="sc-hero-visual" aria-label="Vista del producto">
                    <div className="sc-hero-browser">
                        <div className="sc-browser-chrome">
                            <span /><span /><span />
                            <span className="sc-browser-address">schoolcore / panel institucional</span>
                        </div>
                        <img src={`${config.assets}/dashboard.webp`} alt="Panel general de SchoolCore" loading="eager" />
                    </div>
                    <div className="sc-float-note sc-float-note-top"><span className="sc-mini-icon sc-mini-blue"><GraduationCap size={19} /></span><span><strong>Academic</strong><small>Gestión de calificaciones</small></span><Check size={16} className="sc-float-check" /></div>
                    <div className="sc-float-note sc-float-note-bottom"><span className="sc-mini-icon sc-mini-green"><ShieldCheck size={19} /></span><span><strong>Gestión centralizada</strong><small>Instituciones y módulos</small></span></div>
                </div>
            </div>
            <a href="#plataforma" className="sc-scroll-hint" aria-label="Desplazarse a la plataforma"><ArrowDown size={16} /> Descubre más</a>
        </section>
    );
}

function Intro() {
    const features = [
        { icon: School, title: 'Núcleo institucional', copy: 'La información de tu escuela en un único lugar.' },
        { icon: ShieldCheck, title: 'Roles y permisos', copy: 'Cada usuario accede a las funciones que le corresponden.' },
        { icon: PanelsTopLeft, title: 'Módulos independientes', copy: 'Una plataforma que crece según tus necesidades.' },
    ];
    return (
        <section id="plataforma" className="sc-intro">
            <div className="sc-site-container">
                <div className="sc-section-heading sc-centered">
                    <span className="sc-kicker">PLATAFORMA EDUCATIVA</span>
                    <h2>Menos sistemas separados.<br /><em>Más control para tu escuela.</em></h2>
                    <p>Una estructura compartida para administrar el trabajo escolar sin duplicar registros entre áreas.</p>
                </div>
                <div className="sc-feature-strip">
                    {features.map(item => <article className="sc-feature-intro" key={item.title}><span className="sc-feature-icon"><item.icon size={24} strokeWidth={1.8} /></span><h3>{item.title}</h3><p>{item.copy}</p></article>)}
                </div>
            </div>
        </section>
    );
}

function Modules({ config }: { config: SiteConfig }) {
    const modules = [
        {
            name: 'SchoolCore',
            category: 'NÚCLEO INSTITUCIONAL',
            description: 'Alumnos, familias, grupos, ciclos escolares, usuarios y permisos reunidos en una misma administración.',
            icon: School,
            color: '',
            core: true,
            href: '#experiencias',
            action: 'Conocer la plataforma',
        },
        {
            name: 'Academic',
            category: 'GESTIÓN ACADÉMICA',
            description: 'Planes de estudio, materias, docentes, captura, validación, publicación de calificaciones y reportes.',
            icon: BookOpen,
            color: 'sc-symbol-violet',
            core: false,
            href: '#experiencias',
            action: 'Explorar Academic',
        },
        {
            name: 'PaseLista',
            category: 'ASISTENCIA Y ACCESOS',
            description: 'Asistencia, registro de entradas y salidas, credenciales y seguimiento de la comunidad escolar.',
            icon: ScanLine,
            color: 'sc-symbol-teal',
            core: false,
            href: config.paselista,
            action: 'Conocer el módulo',
        },
        {
            name: 'Enrollment',
            category: 'ADMISIONES E INSCRIPCIONES',
            description: 'Preinscripción en línea, solicitudes, seguimiento, expedientes e incorporación de nuevos alumnos.',
            icon: ClipboardList,
            color: 'sc-symbol-violet',
            core: false,
            href: config.demo,
            action: 'Solicitar información',
        },
        {
            name: 'Aula virtual',
            category: 'APRENDIZAJE DIGITAL',
            description: 'Espacios de aprendizaje, actividades, recursos académicos y comunicación entre docentes y estudiantes.',
            icon: MonitorSmartphone,
            color: 'sc-symbol-teal',
            core: false,
            href: config.demo,
            action: 'Solicitar información',
        },
        {
            name: 'Avisos escolares',
            category: 'COMUNICACIÓN INSTITUCIONAL',
            description: 'Comunicados, circulares, avisos y seguimiento de información dirigida a la comunidad educativa.',
            icon: FileText,
            color: 'sc-symbol-violet',
            core: false,
            href: config.demo,
            action: 'Solicitar información',
        },
    ];

    return (
        <section id="modulos" className="sc-modules sc-section">
            <div className="sc-site-container">
                <div className="sc-section-top">
                    <div className="sc-section-heading">
                        <span className="sc-kicker">ECOSISTEMA SCHOOLCORE</span>
                        <h2>Tu escuela cambia.<br /><em>Tu plataforma crece contigo.</em></h2>
                        <p>Comienza con las herramientas que necesitas y organiza los servicios de tu institución en una sola plataforma modular.</p>
                    </div>
                    <span className="sc-section-side-note">Elige lo que tu escuela necesita</span>
                </div>

                <div className="sc-module-grid">
                    {modules.map((module) => {
                        const Icon = module.icon;
                        return (
                            <article
                                className={`sc-module-card ${module.core ? 'sc-module-core' : ''}`}
                                key={module.name}
                            >
                                <div className="sc-module-card-top">
                                    <span className={`sc-module-symbol ${module.color}`}>
                                        <Icon size={27} />
                                    </span>
                                </div>
                                <div>
                                    <span className="sc-module-overline">{module.category}</span>
                                    <h3>{module.name}</h3>
                                    <p>{module.description}</p>
                                </div>
                                <a href={module.href}>
                                    {module.action} <ArrowUpRight size={17} />
                                </a>
                            </article>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}

function Experiences({ config }: { config: SiteConfig }) {
    const [selected, setSelected] = useState<ScreenId>('schoolcore');
    const active = screenshots.find(screen => screen.id === selected) ?? screenshots[0];
    return (
        <section id="experiencias" className="sc-section sc-experiences">
            <div className="sc-site-container">
                <div className="sc-section-heading sc-centered">
                    <span className="sc-kicker">CONOCE EL PRODUCTO</span>
                    <h2>Una misma plataforma.<br /><em>Experiencias para cada perfil.</em></h2>
                    <p>Conoce los espacios de administración, gestión académica, trabajo docente y consulta familiar.</p>
                </div>
                <div className="sc-experience-tabs" role="tablist" aria-label="Vistas de SchoolCore">
                    {screenshots.map(screen => (
                        <button type="button" role="tab" aria-selected={selected === screen.id} key={screen.id} className={`sc-experience-tab ${selected === screen.id ? 'active' : ''}`} onClick={() => setSelected(screen.id)}>
                            {screen.id === 'schoolcore' ? <LayoutDashboard size={18} /> : screen.id === 'academic' ? <GraduationCap size={18} /> : screen.id === 'teacher' ? <BookOpen size={18} /> : <UsersRound size={18} />}
                            {screen.id === 'schoolcore' ? 'Administración' : screen.id === 'academic' ? 'Academic' : screen.id === 'teacher' ? 'Docentes' : 'Familias'}
                        </button>
                    ))}
                </div>
                <div className="sc-showcase" role="tabpanel">
                    <div className="sc-showcase-shot"><div className="sc-showcase-toolbar"><span /><span /><span /><div>Vista del sistema · {active.eyebrow}</div></div><img src={`${config.assets}/${active.image}`} alt={active.alt} loading="lazy" /></div>
                    <div className="sc-showcase-copy"><span className="sc-kicker">{active.eyebrow}</span><h3>{active.title}</h3><p>{active.description}</p><div className="sc-showcase-rule" /><div className="sc-mini-proof"><CheckCircle2 size={19} /> Diseño enfocado en tareas reales</div><div className="sc-mini-proof"><CheckCircle2 size={19} /> Datos y permisos por institución</div><a href={config.login} className="sc-text-link">Entrar a SchoolCore <ArrowRight size={18} /></a></div>
                </div>
            </div>
        </section>
    );
}

function HowItWorks() {
    const items = [
        { number: '01', icon: School, title: 'Organiza tu institución', description: 'Configura ciclos, grupos, estudiantes, responsables y perfiles de acceso.' },
        { number: '02', icon: CalendarCheck2, title: 'Activa tus procesos', description: 'Utiliza los módulos habilitados para la operación administrativa y académica.' },
        { number: '03', icon: FileCheck2, title: 'Consulta y da seguimiento', description: 'Revisa resultados, publica información y genera reportes desde un mismo entorno.' },
    ];
    return (
        <section className="sc-section sc-process"><div className="sc-site-container"><div className="sc-section-heading sc-centered"><span className="sc-kicker">UN FLUJO MÁS CLARO</span><h2>De la organización<br /><em>a los resultados.</em></h2></div><div className="sc-process-grid">{items.map(item => <article className="sc-process-card" key={item.number}><span className="sc-process-number">{item.number}</span><span className="sc-process-icon"><item.icon size={27} /></span><h3>{item.title}</h3><p>{item.description}</p></article>)}</div></div></section>
    );
}



function Security() {
    const rows = [
        { icon: LockKeyhole, title: 'Accesos diferenciados', text: 'Perfiles y permisos para las distintas funciones de la comunidad escolar.' },
        { icon: School, title: 'Separación institucional', text: 'La configuración y los registros se organizan por escuela.' },
        { icon: FileCheck2, title: 'Publicación controlada', text: 'Los resultados académicos se validan antes de mostrarse a las familias.' },
    ];
    return <section className="sc-section sc-trust"><div className="sc-site-container sc-trust-layout"><div className="sc-section-heading"><span className="sc-kicker">OPERACIÓN RESPONSABLE</span><h2>La información escolar <em>merece cuidado.</em></h2><p>Permisos, separación por institución y flujos de validación forman parte de la arquitectura del producto.</p></div><div className="sc-trust-cards">{rows.map(row => <div className="sc-trust-row" key={row.title}><span><row.icon size={22} /></span><div><h3>{row.title}</h3><p>{row.text}</p></div></div>)}</div></div></section>;
}

function FAQ() {
    return (
        <section className="sc-section sc-faq" id="preguntas">
            <div className="sc-site-container sc-faq-layout">
                <div className="sc-section-heading">
                    <span className="sc-kicker">PREGUNTAS FRECUENTES</span>
                    <h2>Crece a tu ritmo.<br /><em>Usa lo que necesitas.</em></h2>
                    <p>Una plataforma modular para simplificar la operación de tu institución, sin multiplicar sistemas ni duplicar información.</p>
                </div>
                <div className="sc-faq-items">
                    {frequentlyAsked.map((item) => (
                        <details key={item.question}>
                            <summary>{item.question}<ChevronDown size={19} /></summary>
                            <p>{item.answer}</p>
                        </details>
                    ))}
                </div>
            </div>
        </section>
    );
}

function CallToAction({ config }: { config: SiteConfig }) {
    return <section className="sc-cta-wrap"><div className="sc-site-container"><div className="sc-cta"><div className="sc-cta-text"><span>CONOCE SCHOOLCORE</span><h2>Una mejor forma de organizar tu escuela comienza aquí.</h2><p>Conoce cómo organizar los servicios de tu institución con una plataforma que se adapta a sus necesidades.</p></div><div className="sc-cta-actions"><a href={config.demo} className="sc-btn sc-btn-white">Solicitar información <ArrowUpRight size={18} /></a><a href={config.login} className="sc-btn sc-btn-glass">Entrar a la plataforma <ArrowRight size={18} /></a></div></div></div></section>;
}

function Footer({ config }: { config: SiteConfig }) {
    return (
        <footer className="sc-site-footer">
            <div className="sc-site-container">
                <div className="sc-footer-top">
                    <div className="sc-footer-brand">
                        <Brand config={config} dark />
                        <p>Una plataforma educativa modular para conectar la gestión institucional, académica y familiar.</p>
                    </div>
                    <div className="sc-footer-col">
                        <strong>Plataforma</strong>
                        <a href="#plataforma">SchoolCore</a>
                        <a href="#modulos">Módulos</a>
                        <a href="#experiencias">Conoce el sistema</a>
                    </div>
                    <div className="sc-footer-col">
                        <strong>Accesos y ayuda</strong>
                        <a href={config.login}>Iniciar sesión</a>
                        <a href={config.support}>Soporte</a>
                        <a href={config.demo}>Solicitar información</a>
                    </div>
                    <div className="sc-footer-col">
                        <strong>Información y privacidad</strong>
                        <a href={config.privacy}>Aviso de privacidad</a>
                        <a href={config.deletion}>Derechos ARCO</a>
                        <a href={config.support}>Contacto</a>
                    </div>
                </div>
                <div className="sc-footer-bottom">
                    <span>© {new Date().getFullYear()} {config.name}. Plataforma educativa.</span>
                    <span>Diseñada para instituciones educativas</span>
                </div>
            </div>
        </footer>
    );
}

function Landing({ config }: { config: SiteConfig }) {
    return <><Header config={config} /><main><Hero config={config} /><Intro /><Modules config={config} /><Experiences config={config} /><HowItWorks /><Security /><FAQ /><CallToAction config={config} /></main><Footer config={config} /></>;
}

const mount = document.getElementById('schoolcore-public-root');
if (mount) {
    const config: SiteConfig = {
        name: mount.dataset.appName || 'SchoolCore',
        assets: (mount.dataset.assetsUrl || '/landing/images/schoolcore').replace(/\/$/, ''),
        login: mount.dataset.loginUrl || '/login',
        support: mount.dataset.supportUrl || '/soporte',
        privacy: mount.dataset.privacyUrl || '/privacidad',
        deletion: mount.dataset.deletionUrl || '/eliminacion-de-datos',
        demo: mount.dataset.demoUrl || '/soporte',
        paselista: mount.dataset.paselistaUrl || '/modulos/paselista',
    };
    createRoot(mount).render(<Landing config={config} />);
}
