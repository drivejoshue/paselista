
import { useEffect, useState } from 'react';
import {
    ArrowRight,
    Blocks,
    BookOpen,
    CalendarDays,
    ChevronRight,
    ClipboardList,
    ContactRound,
    GraduationCap,
    LayoutDashboard,
    Menu,
    Moon,
    PanelLeftClose,
    PanelLeftOpen,
    ScanLine,
    Settings,
    ShieldCheck,
    Sun,
    Users,
    UsersRound,
     CalendarCheck,
    FileUp,
    Megaphone,
    UserCog,
    ArrowUpRight,
    X,
    type LucideIcon,
} from 'lucide-react';


import { Button } from '@/components/ui/button';
import AcademicWorkspace from './AcademicWorkspace';
import AcademicOverview, {
    type AcademicData,
} from '@/modules/academic/AcademicOverview';

import AcademicCatalog, {
    type AcademicCatalogSection,
} from './modules/academic/AcademicCatalog';
import AcademicGroups from './modules/academic/AcademicGroups';
import AcademicGrades from './modules/academic/AcademicGrades';
// -----------------------------------------------------
// Tipos de SchoolCore
// -----------------------------------------------------

type Page = 'dashboard' | 'modules' | 'academic';
type Theme = 'light' | 'dark';

type ModuleSource = 'core' | 'contract' | 'legacy' | null;

type SchoolCoreModule = {
    code: string;
    name: string;
    description: string | null;
    category: string;
    icon?: string | null;
    isCore: boolean;
    active: boolean;
    source?: ModuleSource;
    status?: string | null;
};

type SchoolCoreNavigationItem = {
    key: string;
    label: string;
    group: 'core' | 'tools' | 'free' | 'modules';
    url: string;
};


type SchoolCoreDashboard = {
    initialPage?: Page;
academic?: AcademicData | null;
    school: {
        id: number;
        name: string;
    };
    cycle: {
        id: number;
        name: string;
    } | null;
    stats: {
        students: number;
        teachers: number;
        groups: number;
        modules: number;
    };
    modules: SchoolCoreModule[];
    navigation?: SchoolCoreNavigationItem[];
    canManageFreeModules: boolean;
};


type InternalNavigationItem = {
    id: Page;
    label: string;
    icon: LucideIcon;
};

// -----------------------------------------------------
// Configuración de navegación e iconos
// -----------------------------------------------------

const internalNavigation: InternalNavigationItem[] = [
    {
        id: 'dashboard',
        label: 'Inicio',
        icon: LayoutDashboard,
    },
    {
        id: 'modules',
        label: 'Catálogo de módulos',
        icon: Blocks,
    },
];


const navigationIcons: Record<string, LucideIcon> = {
    students: Users,
    academic: GraduationCap,
    guardians: ContactRound,
    cycles: CalendarDays,
    groups: UsersRound,
    calendar: CalendarCheck,
    promotions: ClipboardList,
    users: UserCog,
    roles: ShieldCheck,
    imports: FileUp,
    tools: Settings,
    notices: Megaphone,
    attendance: ScanLine,
};

const navigationGroups = [
    { key: 'core', label: 'GESTIÓN INSTITUCIONAL' },
    { key: 'free', label: 'HERRAMIENTAS GRATUITAS' },
    { key: 'tools', label: 'ADMINISTRACIÓN' },
    { key: 'modules', label: 'MÓDULOS ACTIVOS' },
] as const;


const moduleIcons: Record<string, LucideIcon> = {
    core: ShieldCheck,
    attendance: ScanLine,
    academic: GraduationCap,
    enrollment_plus: ClipboardList,
    teacher_planning: BookOpen,
};

// -----------------------------------------------------
// Datos proporcionados por Laravel
// -----------------------------------------------------

function readDashboard(): SchoolCoreDashboard | null {
    const element = document.getElementById(
        'schoolcore-bootstrap'
    );

    if (!element?.textContent) {
        console.error(
            '[SchoolCore] No se recibieron datos de Laravel.'
        );

        return null;
    }

    try {
        return JSON.parse(
            element.textContent
        ) as SchoolCoreDashboard;
    } catch (error) {
        console.error(
            '[SchoolCore] Error leyendo los datos:',
            error
        );

        return null;
    }
}

const dashboard = readDashboard();

// -----------------------------------------------------
// Preferencias visuales
// -----------------------------------------------------

function readSidebarPreference(): boolean {
    try {
        return localStorage.getItem(
            'schoolcore-sidebar'
        ) !== 'collapsed';
    } catch {
        return true;
    }
}

function readThemePreference(): Theme {
    try {
        return localStorage.getItem(
            'schoolcore-theme'
        ) === 'dark'
            ? 'dark'
            : 'light';
    } catch {
        return 'light';
    }
}

// -----------------------------------------------------
// Aplicación principal
// -----------------------------------------------------


export default function App() {
    const academicActive =
        dashboard?.modules.some(
            (module) =>
                module.code === 'academic' &&
                module.active === true
        ) ?? false;

    const [page, setPage] = useState<Page>(() => {
        const initialPage = dashboard?.initialPage ?? 'dashboard';

        if (initialPage === 'academic' && !academicActive) {
            return 'dashboard';
        }

        return initialPage;
    });

   const [academicSection, setAcademicSection] =
    useState<AcademicCatalogSection>(() => {
        const params = new URLSearchParams(window.location.search);

        return params.get('academic') === 'grades'
            ? 'grades'
            : 'overview';
    });
    // A partir de aquí conserva los estados existentes:
    // expanded, theme y mobileOpen.


    const [expanded, setExpanded] = useState(
        readSidebarPreference
    );

    const [theme, setTheme] = useState<Theme>(
        readThemePreference
    );

    const [mobileOpen, setMobileOpen] = useState(false);

    useEffect(() => {
        try {
            localStorage.setItem(
                'schoolcore-sidebar',
                expanded ? 'expanded' : 'collapsed'
            );
        } catch {
            // Persistencia opcional.
        }
    }, [expanded]);

    useEffect(() => {
        try {
            localStorage.setItem(
                'schoolcore-theme',
                theme
            );
        } catch {
            // Persistencia opcional.
        }
    }, [theme]);

    const selectPage = (next: Page) => {
        setPage(next);
        setMobileOpen(false);
    };

    const sidebar = (mobile = false) => {
        const showLabels = expanded || mobile;

        return (
            <aside
                className={[
                    'flex h-full flex-col',
                    'bg-slate-950 text-slate-200',
                    'transition-[width] duration-200',
                    mobile
                        ? 'w-64'
                        : expanded
                          ? 'w-64'
                          : 'w-[68px]',
                ].join(' ')}
            >
                {/* Marca */}

                <div className="flex h-16 shrink-0 items-center justify-between border-b border-white/10 px-3">
                    <div className="flex min-w-0 items-center gap-3">
                        <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-lg font-bold text-white">
                            S
                        </div>

                        {showLabels && (
                            <div className="min-w-0">
                                <div className="truncate font-semibold text-white">
                                    SchoolCore
                                </div>

                                <div className="text-[11px] text-slate-400">
                                    Plataforma educativa
                                </div>
                            </div>
                        )}
                    </div>

                    {mobile && (
                        <button
                            type="button"
                            onClick={() => setMobileOpen(false)}
                            aria-label="Cerrar menú"
                            className="rounded-lg p-2 hover:bg-white/10"
                        >
                            <X size={18} />
                        </button>
                    )}
                </div>

                <nav
                    className="min-h-0 flex-1 space-y-1 overflow-y-auto p-2"
                    aria-label="Navegación SchoolCore"
                >
                    {showLabels && (
                        <p className="px-3 pb-2 pt-4 text-[10px] font-semibold tracking-widest text-slate-500">
                            PLATAFORMA
                        </p>
                    )}

                    {/* Navegación React */}

                    {internalNavigation.map((item) => {
                        const Icon = item.icon;
                        const selected = page === item.id;

                        return (
                            <button
                                key={item.id}
                                type="button"
                                title={item.label}
                                aria-label={item.label}
                                aria-current={
                                    selected ? 'page' : undefined
                                }
                                onClick={() => selectPage(item.id)}
                                className={[
                                    'flex h-10 w-full items-center rounded-lg text-sm transition-colors',
                                    showLabels
                                        ? 'gap-3 px-3'
                                        : 'justify-center',
                                    selected
                                        ? 'bg-blue-600 text-white'
                                        : 'text-slate-400 hover:bg-white/10 hover:text-white',
                                ].join(' ')}
                            >
                                <Icon
                                    size={19}
                                    className="shrink-0"
                                />

                                {showLabels && (
                                    <span className="truncate">
                                        {item.label}
                                    </span>
                                )}
                            </button>
                        );
                    })}



{academicActive && page === 'academic' && (
    <div className="mt-3">
        {showLabels && (
            <p className="px-3 pb-2 pt-4 text-[10px] font-semibold tracking-widest text-slate-500">
                GESTIÓN ACADÉMICA
            </p>
        )}

        {([
           ['overview', 'Resumen académico', LayoutDashboard],
['plans', 'Planes y programas', BookOpen],
['subjects', 'Materias', ClipboardList],
['groups', 'Grupos y materias', UsersRound],
['teachers', 'Docentes', GraduationCap],
['grades', 'Calificaciones', ClipboardList],
        ] as const).map(([id, label, Icon]) => (
            <button
                key={id}
                type="button"
                title={label}
                aria-current={
                    academicSection === id ? 'page' : undefined
                }
                onClick={() => {
                    setAcademicSection(id);
                    setMobileOpen(false);
                }}
                className={[
                    'flex h-10 w-full items-center rounded-lg text-sm transition-colors',
                    showLabels ? 'gap-3 px-3' : 'justify-center',
                    academicSection === id
                        ? 'bg-blue-600 text-white'
                        : 'text-slate-400 hover:bg-white/10 hover:text-white',
                ].join(' ')}
            >
                <Icon size={19} className="shrink-0" />

                {showLabels && (
                    <span className="truncate">{label}</span>
                )}
            </button>
        ))}
    </div>
)}



                    {/* Gestión institucional desde Laravel */}

{(page === 'academic' ? [] : navigationGroups).map((group) => {
    const items = (dashboard?.navigation ?? []).filter(
        (item) => item.group === group.key
    );

    if (items.length === 0) {
        return null;
    }

    return (
        <div key={group.key}>
            {showLabels ? (
                <p className="px-3 pb-2 pt-6 text-[10px] font-semibold tracking-widest text-slate-500">
                    {group.label}
                </p>
            ) : (
                <div className="my-3 border-t border-white/10" />
            )}
{group.key === 'modules' && academicActive && (
    <button
        type="button"
        onClick={() => {
            setAcademicSection('overview');
            selectPage('academic');
        }}
        title="Academic · Gestión académica"
        className={[
            'flex h-10 w-full items-center rounded-lg',
            'text-sm text-slate-400 transition-colors',
            'hover:bg-white/10 hover:text-white',
            showLabels ? 'gap-3 px-3' : 'justify-center',
        ].join(' ')}
    >
        <GraduationCap size={19} className="shrink-0" />

        {showLabels && (
            <span className="truncate">
                Academic · Gestión académica
            </span>
        )}
    </button>
)}
            {items.map((item) => {
                const Icon =
                    navigationIcons[item.key] ?? Blocks;

                return (
                    <a
                        key={item.key}
                        href={item.url}
                        title={item.label}
                        aria-label={item.label}
                        className={[
                            'flex h-10 w-full items-center rounded-lg text-sm',
                            'text-slate-400 transition-colors',
                            'hover:bg-white/10 hover:text-white',
                            showLabels
                                ? 'gap-3 px-3'
                                : 'justify-center',
                        ].join(' ')}
                    >
                        <Icon
                            size={19}
                            className="shrink-0"
                        />

                        {showLabels && (
                            <span className="truncate">
                                {item.label}
                            </span>
                        )}

                        {showLabels && item.group === 'modules' && (
                            <ArrowUpRight
                                size={13}
                                className="ml-auto opacity-60"
                            />
                        )}
                    </a>
                );
            })}
        </div>
    );
})}

                </nav>

                {/* Pie del sidebar */}

                <div className="border-t border-white/10 p-2">
                    <div
                        title="Entorno local"
                        className={[
                            'flex items-center gap-3 rounded-lg px-3 py-3 text-xs text-slate-400',
                            !showLabels && 'justify-center',
                        ].join(' ')}
                    >
                        <span className="size-2 shrink-0 rounded-full bg-emerald-400" />

                        {showLabels && (
                            <span>Entorno local</span>
                        )}
                    </div>
                </div>
            </aside>
        );
    };

    return (
        <div className={theme === 'dark' ? 'dark' : ''}>
            <div className="flex h-dvh overflow-hidden bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100">

                {/* Sidebar escritorio */}

                <div className="hidden shrink-0 md:block">
                    {sidebar()}
                </div>

                {/* Sidebar móvil */}

                {mobileOpen && (
                    <div className="fixed inset-0 z-50 md:hidden">
                        <button
                            type="button"
                            className="absolute inset-0 bg-black/60"
                            aria-label="Cerrar navegación"
                            onClick={() => setMobileOpen(false)}
                        />

                        <div className="relative h-full w-fit">
                            {sidebar(true)}
                        </div>
                    </div>
                )}

                <div className="flex min-w-0 flex-1 flex-col">

                    {/* Header */}

                    <header className="flex h-16 shrink-0 items-center justify-between border-b border-slate-200 bg-white px-4 dark:border-slate-800 dark:bg-slate-900 lg:px-6">
                        <div className="flex items-center gap-3">
                            <button
                                type="button"
                                onClick={() => setMobileOpen(true)}
                                className="rounded-lg p-2 hover:bg-slate-100 dark:hover:bg-slate-800 md:hidden"
                                aria-label="Abrir menú"
                            >
                                <Menu size={20} />
                            </button>

                            <button
                                type="button"
                                onClick={() => setExpanded(!expanded)}
                                className="hidden rounded-lg p-2 hover:bg-slate-100 dark:hover:bg-slate-800 md:flex"
                                aria-label={
                                    expanded
                                        ? 'Contraer sidebar'
                                        : 'Expandir sidebar'
                                }
                                title={
                                    expanded
                                        ? 'Contraer sidebar'
                                        : 'Expandir sidebar'
                                }
                                aria-expanded={expanded}
                            >
                                {expanded ? (
                                    <PanelLeftClose size={20} />
                                ) : (
                                    <PanelLeftOpen size={20} />
                                )}
                            </button>

                            <div className="hidden text-sm font-medium sm:block">
                              {page === 'dashboard'
    ? 'Panel general'
    : page === 'academic'
      ? 'Academic'
      : 'Catálogo de módulos'}
                            </div>
                        </div>

                        <div className="flex items-center gap-2">
                            <button
                                type="button"
                                onClick={() =>
                                    setTheme(
                                        theme === 'light'
                                            ? 'dark'
                                            : 'light'
                                    )
                                }
                                className="flex size-9 items-center justify-center rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800"
                                aria-label="Cambiar tema"
                                title="Cambiar tema"
                            >
                                {theme === 'light' ? (
                                    <Moon size={19} />
                                ) : (
                                    <Sun size={19} />
                                )}
                            </button>

                            <div className="flex size-9 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                                SC
                            </div>

                             <form method="POST" action="/logout">
        <input
            type="hidden"
            name="_token"
            value={
                document.querySelector<HTMLMetaElement>(
                    'meta[name="csrf-token"]',
                )?.content ?? ''
            }
        />

        <button
            type="submit"
            className="rounded-lg border border-slate-700 px-3 py-2 text-sm font-medium text-slate-200 hover:bg-slate-800"
        >
            Cerrar sesión
        </button>
    </form>
                        </div>
                    </header>

                    {/* Contenido */}


{/* Contenido */}

<main className="min-h-0 flex-1 overflow-y-auto">
    <div className="w-full space-y-6 p-4 sm:p-5 lg:p-6">

        {/* Breadcrumb */}

        <div className="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
            <span>SchoolCore</span>
            <ChevronRight size={13} />

            <span>
                {page === 'dashboard'
                    ? 'Inicio'
                    : page === 'academic'
                      ? 'Academic'
                      : 'Módulos'}
            </span>
        </div>

      {/* Academic */}

{page === 'academic' && academicActive ? (
    academicSection === 'grades' ? (
        <AcademicGrades />
    ) : academicSection === 'groups' ? (
        <AcademicGroups />
    ) : (
        <AcademicCatalog
            section={academicSection}
            onSectionChange={setAcademicSection}
        />
    )
) : page === 'dashboard' ? (
    <>
        {/* Encabezado institucional */}

                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {dashboard?.school.name ??
                            'Institución escolar'}
                    </h1>

                    <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Administración de la plataforma escolar.
                        {dashboard?.cycle && (
                            <>
                                {' · Ciclo '}
                                {dashboard.cycle.name}
                            </>
                        )}
                    </p>
                </div>

                {/* Presentación SchoolCore */}

                <section className="flex flex-wrap items-center justify-between gap-5 rounded-xl bg-gradient-to-r from-slate-900 to-blue-700 p-5 text-white lg:p-7">
                    <div>
                        <p className="text-xs uppercase tracking-widest text-blue-200">
                            SCHOOLCORE
                        </p>

                        <h2 className="mt-2 text-xl font-semibold sm:text-2xl">
                            Una plataforma para toda tu escuela.
                        </h2>

                        <p className="mt-2 text-sm text-blue-100">
                            Gestión centralizada con módulos independientes.
                        </p>
                    </div>

                    <Button
                        type="button"
                        onClick={() => selectPage('modules')}
                        className="bg-white text-slate-900 hover:bg-blue-50"
                    >
                        Explorar módulos
                        <ArrowRight size={16} />
                    </Button>
                </section>

                {/* Estadísticas */}

                <StatsSection />

                {/* Tarjetas de módulos */}

                <ModuleSection
                    onOpenAcademic={() => selectPage('academic')}
                />
            </>
        ) : (
            <>
                {/* Catálogo */}

                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Catálogo de módulos
                    </h1>

                    <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Funciones disponibles en la plataforma.
                    </p>
                </div>

                <ModuleSection
                    onOpenAcademic={() => selectPage('academic')}
                />
            </>
        )}
    </div>
</main>

                </div>
            </div>
        </div>
    );
}

// -----------------------------------------------------
// Estadísticas institucionales
// -----------------------------------------------------

function StatsSection() {
    const stats = [
        {
            label: 'Alumnos matriculados',
            icon: Users,
            value: dashboard?.stats.students,
        },
        {
            label: 'Docentes',
            icon: GraduationCap,
            value: dashboard?.stats.teachers,
        },
        {
            label: 'Grupos activos',
            icon: UsersRound,
            value: dashboard?.stats.groups,
        },
        {
            label: 'Módulos habilitados',
            icon: ShieldCheck,
            value: dashboard?.stats.modules,
        },
    ];

    return (
        <section>
            <h2 className="mb-3 text-base font-semibold">
                Resumen institucional
            </h2>

            <div className="grid grid-cols-2 gap-3 xl:grid-cols-4">
                {stats.map((stat) => {
                    const Icon = stat.icon;

                    return (
                        <article
                            key={stat.label}
                            className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900"
                        >
                            <div className="flex items-center justify-between">
                                <span className="text-xs text-slate-500 dark:text-slate-400">
                                    {stat.label}
                                </span>

                                <Icon
                                    size={18}
                                    className="text-blue-500"
                                />
                            </div>

                            <div className="mt-3 text-2xl font-semibold">
                                {stat.value ?? '—'}
                            </div>

                            <p className="mt-1 text-[11px] text-slate-400">
                                {dashboard?.cycle?.name ??
                                    'Sin ciclo activo'}
                            </p>
                        </article>
                    );
                })}
            </div>
        </section>
    );
}

// -----------------------------------------------------
// Catálogo de módulos
// -----------------------------------------------------

function ModuleSection({
    onOpenAcademic,
}: {
    onOpenAcademic: () => void;
}) {

    const modules = dashboard?.modules ?? [];

const [savingCode, setSavingCode] = useState<string | null>(null);
const [moduleError, setModuleError] = useState<string | null>(null);

async function toggleFreeModule(
    code: string,
    enabled: boolean
): Promise<void> {
    const csrf = document.querySelector<HTMLMetaElement>(
        'meta[name="csrf-token"]'
    )?.content;

    if (!csrf) {
        setModuleError('No se encontró el token de seguridad.');
        return;
    }

    setSavingCode(code);
    setModuleError(null);

    try {
        const response = await fetch(
            `/admin/schoolcore/modules/${encodeURIComponent(code)}`,
            {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ enabled }),
            }
        );

        if (!response.ok) {
            throw new Error(
                `No se pudo actualizar el módulo (${response.status}).`
            );
        }

        window.location.reload();
    } catch (error) {
        setModuleError(
            error instanceof Error
                ? error.message
                : 'Error desconocido.'
        );
    } finally {
        setSavingCode(null);
    }
}


    return (
        <section>

{moduleError && (
    <div
        role="alert"
        className="mb-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300"
    >
        {moduleError}
    </div>
)}


            {modules.length === 0 ? (
                <div className="rounded-xl border border-slate-200 bg-white p-5 text-sm text-slate-500 dark:border-slate-800 dark:bg-slate-900">
                    No hay módulos disponibles.
                </div>
            ) : (
                <div className="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                    {modules.map((module) => {
                       const Icon =
    moduleIcons[module.code] ?? Blocks;

const canOpenAcademic =
    module.code === 'academic' &&
    module.active === true;

return (
    <article
        key={module.code}
        role={canOpenAcademic ? 'button' : undefined}
        tabIndex={canOpenAcademic ? 0 : undefined}
        onClick={
            canOpenAcademic
                ? onOpenAcademic
                : undefined
        }
        onKeyDown={
            canOpenAcademic
                ? (event) => {
                      if (
                          event.key === 'Enter' ||
                          event.key === ' '
                      ) {
                          event.preventDefault();
                          onOpenAcademic();
                      }
                  }
                : undefined
        }
        aria-label={
            canOpenAcademic
                ? 'Abrir módulo Academic'
                : undefined
        }
        className={[
            'flex min-h-40 flex-col rounded-xl border',
            'border-slate-200 bg-white p-4',
            'dark:border-slate-800 dark:bg-slate-900',
            canOpenAcademic
                ? 'cursor-pointer transition-all hover:border-blue-500 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-500'
                : '',
        ].join(' ')}
    >
                                <div className="mb-3 flex items-center justify-between gap-2">
                                    <div className="flex size-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950 dark:text-blue-300">
                                        <Icon size={21} />
                                    </div>

                                    <span
                                        className={[
                                            'rounded-full px-2 py-1 text-[11px]',
                                            module.active
                                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                                : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
                                        ].join(' ')}
                                    >
                                        {module.isCore
                                            ? 'Núcleo'
                                            : module.source === 'legacy'
                                              ? 'Heredado'
                                              : module.active
                                                ? 'Activo'
                                                : 'No activo'}
                                    </span>
                                </div>

                                <h3 className="font-semibold">
                                    {module.name}
                                </h3>

                                <p className="mt-1 flex-1 text-xs leading-5 text-slate-500 dark:text-slate-400">
                                    {module.description ??
                                        'Sin descripción disponible.'}
                                </p>

                                <div className="mt-3 border-t border-slate-100 pt-3 text-xs text-slate-500 dark:border-slate-800">
                                    {module.isCore
                                        ? 'Incluido en SchoolCore'
                                        : module.source === 'legacy'
                                          ? 'Incluido en licencia anterior'
                                          : module.active
                                            ? 'Habilitado para la institución'
                                            : 'Disponible para contratación'}
                                </div>

{module.code === 'notices' &&
    dashboard?.canManageFreeModules && (
        <Button
            type="button"
            variant={module.active ? 'outline' : 'default'}
            disabled={savingCode !== null}
            onClick={() => {
                void toggleFreeModule(
                    module.code,
                    !module.active
                );
            }}
            className="mt-3 w-full"
        >
            {savingCode === module.code
                ? 'Guardando...'
                : module.active
                  ? 'Desactivar herramienta'
                  : 'Activar gratis'}
        </Button>
    )}

                            </article>
                        );
                    })}
                </div>
            )}
        </section>
    );
}
