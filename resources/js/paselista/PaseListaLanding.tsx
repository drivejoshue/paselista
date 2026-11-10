import { useRef, useState, type KeyboardEvent, type ReactNode } from 'react';
import './paselista-landing.css';

export interface PaseListaLandingProps {
  coreUrl?: string;
  loginUrl?: string;
  demoUrl?: string;
}

type IconName =
  | 'arrow'
  | 'arrow-up'
  | 'back'
  | 'check'
  | 'qr'
  | 'people'
  | 'chart'
  | 'shield'
  | 'phone'
  | 'bell'
  | 'menu'
  | 'close'
  | 'layers'
  | 'clock';

function Icon({ name, size = 20, className = '' }: { name: IconName; size?: number; className?: string }) {
  const content: Record<IconName, ReactNode> = {
    arrow: <><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></>,
    'arrow-up': <><path d="M7 17 17 7"/><path d="M8 7h9v9"/></>,
    back: <><path d="m15 18-6-6 6-6"/><path d="M9 12h12"/></>,
    check: <><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></>,
    qr: <><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM21 14v3h-2M14 21h3M19 19h2v2"/></>,
    people: <><circle cx="9" cy="8" r="3"/><path d="M3 20v-2a6 6 0 0 1 12 0v2M17 5a3 3 0 0 1 0 6M18 14a5 5 0 0 1 3 5v1"/></>,
    chart: <><path d="M4 20V5M4 20h17"/><path d="m8 15 4-5 3 2 5-7"/></>,
    shield: <><path d="M12 2 4 5v6c0 5.2 3.2 8.5 8 11 4.8-2.5 8-5.8 8-11V5l-8-3Z"/><path d="m9 12 2 2 4-4"/></>,
    phone: <><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/></>,
    bell: <><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></>,
    menu: <><path d="M4 7h16M4 12h16M4 17h16"/></>,
    close: <><path d="M5 5 19 19M19 5 5 19"/></>,
    layers: <><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 9h10M7 13h6"/></>,
    clock: <><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></>,
  };

  return (
    <svg
      aria-hidden="true"
      className={className}
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.75"
      strokeLinecap="round"
      strokeLinejoin="round"
    >
      {content[name]}
    </svg>
  );
}

const screenshots = {
  dashboard: '/landing/images/schoolpass/dashboard-schoolpass.jpeg',
  staff: '/landing/images/schoolpass/app-staff.jpeg',
  family: '/landing/images/schoolpass/app-family.jpeg',
} as const;

const experiences = [
  {
    id: 'direccion',
    tab: 'Dirección',
    icon: 'chart' as IconName,
    title: 'Lo que sucede en el plantel, en un solo lugar.',
    description: 'Consulta asistencia, puntualidad, movimientos e incidencias desde un panel pensado para el seguimiento escolar.',
    highlights: ['Resumen de actividad por periodo', 'Consulta por grupos y alumnos autorizados', 'Historial y reportes operativos'],
    image: screenshots.dashboard,
    imageAlt: 'Panel de dirección de PaseLista',
    mobile: false,
  },
  {
    id: 'personal',
    tab: 'Personal escolar',
    icon: 'qr' as IconName,
    title: 'Registra movimientos desde el punto de acceso.',
    description: 'La aplicación Staff apoya la lectura de credenciales, el registro de accesos y las incidencias durante la operación diaria.',
    highlights: ['Lectura de códigos QR', 'Registro de entradas y salidas', 'Operación desde dispositivos autorizados'],
    image: screenshots.staff,
    imageAlt: 'Aplicación PaseLista Staff',
    mobile: true,
  },
  {
    id: 'familias',
    tab: 'Familias',
    icon: 'people' as IconName,
    title: 'Información útil para madres, padres y tutores.',
    description: 'PaseLista Family permite consultar la información que cada escuela habilita para las familias vinculadas.',
    highlights: ['Alumnos y credenciales vinculadas', 'Consulta de movimientos autorizados', 'Avisos y seguimiento escolar'],
    image: screenshots.family,
    imageAlt: 'Aplicación PaseLista Family',
    mobile: true,
  },
] as const;

const benefits = [
  { icon: 'qr' as IconName, number: '01', title: 'Accesos organizados', text: 'Identifica entradas y salidas con credenciales QR y reglas definidas por cada institución.' },
  { icon: 'chart' as IconName, number: '02', title: 'Visibilidad para dirección', text: 'Concentra asistencias, retardos, incidencias y movimientos para facilitar el seguimiento.' },
  { icon: 'people' as IconName, number: '03', title: 'Familias conectadas', text: 'Acerca a los tutores la información escolar que les corresponde, con accesos delimitados.' },
];

export default function PaseListaLanding({
  coreUrl = '/',
  loginUrl = '/login',
  demoUrl = '/soporte',
}: PaseListaLandingProps) {
  const [activeExperience, setActiveExperience] = useState(0);
  const [menuOpen, setMenuOpen] = useState(false);
  const tabRefs = useRef<Array<HTMLButtonElement | null>>([]);
  const experience = experiences[activeExperience];

  const closeMenu = () => setMenuOpen(false);
  const handleTabKeyDown = (event: KeyboardEvent<HTMLButtonElement>, index: number) => {
    let nextIndex: number | null = null;
    if (event.key === 'ArrowRight') nextIndex = (index + 1) % experiences.length;
    if (event.key === 'ArrowLeft') nextIndex = (index + experiences.length - 1) % experiences.length;
    if (event.key === 'Home') nextIndex = 0;
    if (event.key === 'End') nextIndex = experiences.length - 1;
    if (nextIndex !== null) {
      event.preventDefault();
      setActiveExperience(nextIndex);
      tabRefs.current[nextIndex]?.focus();
    }
  };

  return (
    <div className="pl-page">
      <a className="pl-skip-link" href="#contenido">Saltar al contenido</a>

      <header className="pl-header">
        <div className="pl-wrap pl-header-inner">
          <div className="pl-header-brands">
            <a className="pl-core-link" href={coreUrl} aria-label="Ir a SchoolCore">
              <Icon name="back" size={16}/>
              <span>SchoolCore</span>
            </a>
            <span className="pl-brand-separator" aria-hidden="true" />
            <a className="pl-brand" href="#inicio" onClick={closeMenu} aria-label="PaseLista, ir al inicio">
              <span className="pl-brand-symbol" aria-hidden="true"><Icon name="check" size={21}/></span>
              <span>Pase<span>Lista</span></span>
            </a>
          </div>

          <button
            className="pl-mobile-menu"
            type="button"
            aria-label={menuOpen ? 'Cerrar menú' : 'Abrir menú'}
            aria-expanded={menuOpen}
            aria-controls="pl-main-nav"
            onClick={() => setMenuOpen(!menuOpen)}
          ><Icon name={menuOpen ? 'close' : 'menu'} size={22}/></button>

          <nav className={`pl-nav${menuOpen ? ' is-open' : ''}`} id="pl-main-nav" aria-label="Navegación PaseLista">
            <a href="#funcionamiento" onClick={closeMenu}>Cómo funciona</a>
            <a href="#experiencias" onClick={closeMenu}>Aplicaciones</a>
            <a href="#seguridad" onClick={closeMenu}>Seguridad</a>
            <a className="pl-nav-login" href={loginUrl} onClick={closeMenu}>Entrar <Icon name="arrow-up" size={15}/></a>
          </nav>
        </div>
      </header>

      <main id="contenido">
        <section className="pl-hero" id="inicio" aria-labelledby="pl-main-title">
          <div className="pl-hero-glow" aria-hidden="true" />
          <div className="pl-wrap pl-hero-grid">
            <div className="pl-hero-content">
              <div className="pl-breadcrumb"><a href={coreUrl}>SchoolCore</a><span>/</span><span>Módulos</span><span>/</span><strong>PaseLista</strong></div>
              <span className="pl-kicker"><span className="pl-kicker-dot"/> CONTROL DE ACCESOS Y ASISTENCIA</span>
              <h1 id="pl-main-title">Cada entrada cuenta.<br/><em>Cada familia importa.</em></h1>
              <p>Accesos, asistencia y comunicación escolar en un mismo flujo. Una experiencia para dirección, personal y familias, integrada al ecosistema <strong>SchoolCore</strong>.</p>
              <div className="pl-actions">
                <a className="pl-button pl-button-primary" href={demoUrl}>Solicitar una demostración <Icon name="arrow" size={18}/></a>
                <a className="pl-button pl-button-outline" href="#funcionamiento">Explorar el módulo <Icon name="arrow-up" size={17}/></a>
              </div>
              <div className="pl-hero-facts" aria-label="Principales características">
                <span><Icon name="check" size={16}/> Inicia con QR</span>
                <span><Icon name="check" size={16}/> Operación móvil</span>
                <span><Icon name="check" size={16}/> Permisos por rol</span>
              </div>
            </div>

            <div className="pl-visual" aria-label="Vista del panel PaseLista y sus aplicaciones móviles">
              <div className="pl-visual-orbit" aria-hidden="true"/>
              <div className="pl-dashboard">
                <div className="pl-window-bar"><span/><span/><span/><small>Panel de dirección</small></div>
                <img src={screenshots.dashboard} alt="Vista del panel administrativo PaseLista" loading="eager" />
              </div>
              <div className="pl-visual-tag pl-visual-tag-top"><Icon name="shield" size={17}/><span>Control institucional</span></div>
              <div className="pl-device pl-device-staff"><img src={screenshots.staff} alt="Pantalla PaseLista Staff" loading="eager" /></div>
              <div className="pl-device pl-device-family"><img src={screenshots.family} alt="Pantalla PaseLista Family" loading="eager" /></div>
              <div className="pl-visual-tag pl-visual-tag-bottom"><span className="pl-status-dot"/> <span>Información conectada</span></div>
            </div>
          </div>
          <div className="pl-hero-bottom" aria-hidden="true"/>
        </section>

        <section className="pl-benefits" aria-labelledby="pl-benefits-heading">
          <div className="pl-wrap">
            <div className="pl-section-heading pl-section-heading-centered">
              <span className="pl-overline">UNA OPERACIÓN MÁS CLARA</span>
              <h2 id="pl-benefits-heading">Menos dispersión. Más control del día escolar.</h2>
              <p>PaseLista concentra lo esencial para trabajar de forma coordinada desde el primer acceso hasta la consulta de cada movimiento.</p>
            </div>
            <div className="pl-benefits-grid">
              {benefits.map((benefit) => (
                <article className="pl-benefit" key={benefit.number}>
                  <div className="pl-benefit-head"><span className="pl-benefit-icon"><Icon name={benefit.icon} size={25}/></span><span className="pl-benefit-number">{benefit.number}</span></div>
                  <h3>{benefit.title}</h3><p>{benefit.text}</p>
                </article>
              ))}
            </div>
          </div>
        </section>

        <section className="pl-flow-section" id="funcionamiento" aria-labelledby="pl-flow-heading">
          <div className="pl-wrap pl-flow-grid">
            <div className="pl-flow-intro">
              <span className="pl-overline">CÓMO FUNCIONA</span>
              <h2 id="pl-flow-heading">De la credencial al seguimiento, sin complicar la operación.</h2>
              <p>Un recorrido sencillo que aprovecha dispositivos autorizados y la información registrada por cada escuela.</p>
              <a href="#experiencias" className="pl-inline-link">Conoce las tres experiencias <Icon name="arrow" size={18}/></a>
            </div>
            <ol className="pl-steps">
              <li><span className="pl-step-icon"><Icon name="qr" size={24}/></span><div><span className="pl-step-label">PASO 01</span><h3>Identifica</h3><p>La institución asigna credenciales y define quiénes pueden registrar accesos.</p></div></li>
              <li><span className="pl-step-icon"><Icon name="clock" size={24}/></span><div><span className="pl-step-label">PASO 02</span><h3>Registra</h3><p>El personal autorizado captura entradas, salidas e incidencias.</p></div></li>
              <li><span className="pl-step-icon"><Icon name="chart" size={24}/></span><div><span className="pl-step-label">PASO 03</span><h3>Consulta</h3><p>Dirección y familias revisan la información disponible según sus permisos.</p></div></li>
            </ol>
          </div>
        </section>

        <section className="pl-experiences" id="experiencias" aria-labelledby="pl-experiences-heading">
          <div className="pl-wrap">
            <div className="pl-section-heading pl-section-heading-centered">
              <span className="pl-overline">ECOSISTEMA PASELISTA</span>
              <h2 id="pl-experiences-heading">Una plataforma. Tres formas de utilizarla.</h2>
              <p>Cada perfil ve lo que necesita para trabajar o mantenerse informado, sin mezclar responsabilidades.</p>
            </div>
            <div className="pl-experience-panel">
              <div className="pl-tabs" role="tablist" aria-label="Experiencias PaseLista">
                {experiences.map((item, index) => (
                  <button
                    key={item.id}
                    ref={(node) => { tabRefs.current[index] = node; }}
                    type="button"
                    role="tab"
                    id={`pl-tab-${item.id}`}
                    aria-selected={activeExperience === index}
                    aria-controls="pl-experience-panel"
                    tabIndex={activeExperience === index ? 0 : -1}
                    className={`pl-tab${activeExperience === index ? ' is-active' : ''}`}
                    onClick={() => setActiveExperience(index)}
                    onKeyDown={(event) => handleTabKeyDown(event, index)}
                  ><Icon name={item.icon} size={19}/>{item.tab}</button>
                ))}
              </div>
              <div className="pl-experience-body" role="tabpanel" id="pl-experience-panel" aria-labelledby={`pl-tab-${experience.id}`} key={experience.id}>
                <div className="pl-experience-copy">
                  <span className="pl-overline">{experience.tab.toUpperCase()}</span>
                  <h3>{experience.title}</h3>
                  <p>{experience.description}</p>
                  <ul>{experience.highlights.map((highlight) => <li key={highlight}><Icon name="check" size={18}/>{highlight}</li>)}</ul>
                </div>
                <div className={`pl-experience-image${experience.mobile ? ' is-mobile' : ''}`}>
                  <img src={experience.image} alt={experience.imageAlt} loading="lazy" />
                </div>
              </div>
            </div>
          </div>
        </section>

        <section className="pl-security-section" id="seguridad" aria-labelledby="pl-security-heading">
          <div className="pl-wrap pl-security-grid">
            <div className="pl-security-visual"><span className="pl-security-big-icon"><Icon name="shield" size={48}/></span><span className="pl-security-label">Control institucional</span><p>Acceso por escuela, usuario y responsabilidad.</p></div>
            <div className="pl-security-content">
              <span className="pl-overline">SEGURIDAD POR DISEÑO</span>
              <h2 id="pl-security-heading">La información corresponde a quien debe verla.</h2>
              <p>La institución administra vinculaciones, credenciales y permisos. PaseLista organiza los registros para mantener separadas la operación interna y la consulta familiar.</p>
              <div className="pl-security-list">
                <span><Icon name="shield" size={19}/> Roles y permisos</span>
                <span><Icon name="layers" size={19}/> Información por institución</span>
                <span><Icon name="bell" size={19}/> Comunicación autorizada</span>
                <span><Icon name="chart" size={19}/> Seguimiento operativo</span>
              </div>
            </div>
          </div>
        </section>

        <section className="pl-cta" aria-labelledby="pl-cta-heading">
          <div className="pl-wrap">
            <div className="pl-cta-inner">
              <div><span className="pl-overline">PASELISTA + SCHOOLCORE</span><h2 id="pl-cta-heading">Un módulo especializado.<br/>Una plataforma que crece contigo.</h2><p>Conoce cómo integrar el control de accesos al resto de la operación escolar.</p></div>
              <div className="pl-cta-actions"><a className="pl-button pl-button-primary" href={demoUrl}>Solicitar demostración <Icon name="arrow" size={18}/></a><a className="pl-button pl-button-text" href={coreUrl}>Explorar SchoolCore <Icon name="arrow-up" size={17}/></a></div>
            </div>
          </div>
        </section>
      </main>

      <footer className="pl-footer">
        <div className="pl-wrap pl-footer-inner"><span><strong>PaseLista</strong> · Un módulo de SchoolCore</span><a href={coreUrl}>Volver a SchoolCore <Icon name="arrow-up" size={15}/></a></div>
      </footer>
    </div>
  );
}
