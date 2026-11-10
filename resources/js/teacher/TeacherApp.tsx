import { useEffect, useRef, useState, type ReactNode } from 'react';
import Swal from 'sweetalert2';

import {
    BookOpen,
    ChevronRight,
    ClipboardCheck,
    GraduationCap,
    LayoutDashboard,
    LogOut,
    Menu,
    Moon,
    PanelLeftClose,
    PanelLeftOpen,
    Sun,
    UserRound,
    Users,
    X,
    type LucideIcon,
} from 'lucide-react';

import GradeCapture from './GradeCapture';

export type Assignment = {
    id: number;
    group_id?: number;
    group_name: string;
    subject_name: string;
    subject_id?: number;
    grade_label?: string;
    level_name?: string;
    cycle_name?: string;
    academic_level_id?: number;
    academic_cycle_id?: number;
};

export type Period = {
    id: number;
    name: string;
    academic_cycle_id: number;
    academic_level_id: number | null;
};

export type Student = {
    enrollment_id?: number;
    student_code: string;
    first_name: string;
    last_name: string;
    numeric_value?: string | null;
};

export type TeacherBoot = {
    page: 'dashboard' | 'students' | 'grades' | 'capture';
    teacher: { name: string; email: string };
    assignments?: Assignment[];
    grade_input_decimals?: boolean;
    assigned?: Assignment;
    students?: Student[];
    metrics?: {
        groups: number;
        subjects: number;
        students: number;
        drafts: number;
        submitted: number;
        published: number;
    };
    periods?: Period[];
    books?: {
        teaching_assignment_id: number;
        grading_period_id: number;
        status: string;
    }[];
    a?: Assignment;
    p?: Period;
    book?: { id: number; status: string } | null;
    csrf: string;
    flash?: string | null;
    errors?: string[];
};

const card =
    'rounded-xl border border-slate-200 bg-white p-5 ' +
    'dark:border-slate-800 dark:bg-slate-900';

const linkButton =
    'inline-flex items-center gap-2 rounded-lg bg-blue-600 ' +
    'px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700';

const nav: {
    title: string;
    href: string;
    icon: LucideIcon;
}[] = [
    {
        title: 'Inicio',
        href: '/teacher/dashboard',
        icon: LayoutDashboard,
    },
    {
        title: 'Mis grupos',
        href: '/teacher/dashboard?section=groups',
        icon: Users,
    },
    {
        title: 'Materias',
        href: '/teacher/dashboard?section=subjects',
        icon: BookOpen,
    },
    {
        title: 'Alumnos',
        href: '/teacher/dashboard?section=students',
        icon: GraduationCap,
    },
    {
        title: 'Calificaciones',
        href: '/teacher/grades',
        icon: ClipboardCheck,
    },
    {
        title: 'Mi perfil',
        href: '/teacher/dashboard?section=profile',
        icon: UserRound,
    },
];

function Header({
    title,
    description,
}: {
    title: string;
    description: string;
}) {
    return (
        <div className="mb-6">
            <h1 className="text-2xl font-bold tracking-tight md:text-3xl">
                {title}
            </h1>
            <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {description}
            </p>
        </div>
    );
}

function Empty({ children }: { children: ReactNode }) {
    return (
        <div className={card}>
            <p className="text-sm text-slate-500 dark:text-slate-400">
                {children}
            </p>
        </div>
    );
}

function Assignments({
    items,
    mode,
}: {
    items: Assignment[];
    mode: 'groups' | 'subjects' | 'students';
}) {
    if (!items.length) {
        return (
            <Empty>
                Dirección todavía no te ha asignado grupos o materias.
            </Empty>
        );
    }

    const groups = Object.values(
        items.reduce<Record<string, Assignment[]>>((acc, item) => {
            const key = String(item.group_id ?? item.group_name);
            (acc[key] ??= []).push(item);
            return acc;
        }, {}),
    );

    return (
      <div
    className="grid gap-4"
    style={{
        gridTemplateColumns:
            'repeat(auto-fit, minmax(min(100%, 320px), 1fr))',
    }}
>
            {groups.map((entries) => (
                <div
                    className={card}
                    key={entries[0].group_id ?? entries[0].group_name}
                >
                    <div className="mb-3">
                        <h2 className="text-lg font-semibold">
                            {entries[0].group_name}
                        </h2>
                        <p className="text-xs text-slate-500">
                            {entries[0].level_name} · {entries[0].cycle_name}
                        </p>
                    </div>

                    {entries.map((a) => (
                        <div
                            key={a.id}
                            className="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 py-3 dark:border-slate-800"
                        >
                            <div>
                                <p className="font-medium">{a.subject_name}</p>
                                <p className="text-xs text-slate-500">
                                    {mode === 'students'
                                        ? 'Lista de alumnos de la materia'
                                        : 'Asignación activa'}
                                </p>
                            </div>

                            <a
                                className={linkButton}
                                href={
                                    mode === 'students'
                                        ? `/teacher/assignments/${a.id}/students`
                                        : '/teacher/grades'
                                }
                            >
                                {mode === 'students'
                                    ? 'Ver alumnos'
                                    : 'Calificar'}
                                <ChevronRight size={15} />
                            </a>
                        </div>
                    ))}
                </div>
            ))}
        </div>
    );
}

function GradeList({ boot }: { boot: TeacherBoot }) {
    const assignments = boot.assignments ?? [];
    const periods = boot.periods ?? [];

    return (
        <>
            <Header
                title="Mis calificaciones"
                description="Selecciona materia y periodo para capturar o consultar notas."
            />

            {!assignments.length && (
                <Empty>
                    No tienes asignaciones activas en el ciclo actual.
                </Empty>
            )}

            <div className="grid gap-4 lg:grid-cols-2">
                {assignments.map((a) => {
                    const compatible = periods.filter(
                        (p) =>
                            p.academic_cycle_id === a.academic_cycle_id &&
                            (p.academic_level_id === null ||
                                p.academic_level_id === a.academic_level_id),
                    );

                    return (
                        <div className={card} key={a.id}>
                            <h2 className="text-lg font-semibold">
                                {a.subject_name}
                            </h2>
                            <p className="mb-4 text-sm text-slate-500">
                                {a.group_name}
                            </p>

                            {compatible.length ? (
                                compatible.map((p) => {
                                    const book = boot.books?.find(
                                        (b) =>
                                            b.teaching_assignment_id === a.id &&
                                            b.grading_period_id === p.id,
                                    );

                                    return (
                                        <a
                                            key={p.id}
                                            href={`/teacher/grades/${a.id}/${p.id}`}
                                            className="mb-2 flex items-center justify-between rounded-lg border border-slate-200 px-3 py-3 hover:border-blue-500 dark:border-slate-700"
                                        >
                                            <span className="text-sm font-medium">
                                                {p.name}
                                            </span>

                                            <span className="text-xs text-slate-500">
                                                {book?.status === 'published'
                                                    ? 'Publicada'
                                                    : book?.status === 'submitted'
                                                      ? 'Enviada'
                                                      : book
                                                        ? 'Borrador'
                                                        : 'Sin iniciar'}
                                            </span>
                                        </a>
                                    );
                                })
                            ) : (
                                <p className="text-sm text-amber-600">
                                    Dirección aún no habilita periodos para este grupo.
                                </p>
                            )}
                        </div>
                    );
                })}
            </div>
        </>
    );
}

function StudentsPage({ boot }: { boot: TeacherBoot }) {
    const a = boot.assigned;
    const students = boot.students ?? [];

    return (
        <>
            <Header
                title={`Alumnos · ${a?.subject_name ?? ''}`}
                description={`${a?.group_name ?? ''} · ${a?.cycle_name ?? ''} · ${students.length} activos`}
            />

            <div className={`${card} overflow-x-auto`}>
                <table className="w-full min-w-[480px] text-left text-sm">
                    <thead className="text-xs uppercase text-slate-500">
                        <tr>
                            <th className="pb-3">Matrícula</th>
                            <th className="pb-3">Alumno</th>
                        </tr>
                    </thead>

                    <tbody>
                        {students.map((s, i) => (
                            <tr
                                key={`${s.student_code}-${i}`}
                                className="border-t border-slate-100 dark:border-slate-800"
                            >
                                <td className="py-3">{s.student_code}</td>
                                <td className="py-3">
                                    {s.last_name} {s.first_name}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>

                {!students.length && (
                    <p className="py-5 text-sm text-slate-500">
                        No hay alumnos activos.
                    </p>
                )}
            </div>

            <a href="/teacher/grades" className={`${linkButton} mt-4`}>
                Ir a calificaciones
                <ChevronRight size={16} />
            </a>
        </>
    );
}



function TeacherProfile({ boot }: { boot: TeacherBoot }) {
    const [decimals, setDecimals] = useState(
        boot.grade_input_decimals ?? true,
    );
    const [saving, setSaving] = useState(false);

    async function savePreference(next: boolean) {
        if (saving) return;

        setSaving(true);

        try {
            const response = await fetch(
                '/teacher/preferences/grade-input',
                {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': boot.csrf,
                    },
                    body: JSON.stringify({
                        grade_input_decimals: next,
                    }),
                },
            );

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(
                    data.message ?? 'No se pudo guardar la preferencia.',
                );
            }

            setDecimals(next);

            void Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Preferencia guardada',
                timer: 2200,
                showConfirmButton: false,
                background: document.documentElement.classList.contains('dark')
                    ? '#0f172a'
                    : '#ffffff',
                color: document.documentElement.classList.contains('dark')
                    ? '#f8fafc'
                    : '#0f172a',
            });
        } catch (error) {
            void Swal.fire({
                icon: 'error',
                title: 'No se pudo guardar',
                text: error instanceof Error
                    ? error.message
                    : 'Error desconocido.',
                confirmButtonColor: '#2563eb',
            });
        } finally {
            setSaving(false);
        }
    }

    return (
        <>
            <Header
                title="Mi perfil"
                description="Información y preferencias de tu cuenta docente."
            />

            <div className={`${card} space-y-5`}>
                <div>
                    <h2 className="font-semibold">
                        {boot.teacher.name}
                    </h2>
                    <p className="mt-1 text-sm text-slate-500">
                        {boot.teacher.email}
                    </p>
                </div>

                <div className="border-t border-slate-200 pt-5 dark:border-slate-800">
                    <h3 className="font-semibold">
                        Preferencias de captura
                    </h3>

                    <label className="mt-4 flex cursor-pointer items-center gap-3">
                        <input
                            type="checkbox"
                            checked={decimals}
                            disabled={saving}
                            onChange={(e) =>
                                void savePreference(e.target.checked)
                            }
                            className="h-5 w-5 accent-blue-600"
                        />

                        <span className="text-sm font-medium">
                            Usar decimales en calificaciones
                        </span>
                    </label>

                    <p className="mt-2 text-sm text-slate-500">
                        {decimals
                            ? 'Los botones aumentan o disminuyen en 0.1.'
                            : 'Los botones aumentan o disminuyen en 1.'}
                    </p>

                    <p className="mt-2 text-xs text-slate-500">
                        El cambio no modifica las notas guardadas.
                        Se aplicará al abrir nuevamente una captura.
                    </p>
                </div>
            </div>
        </>
    );
}



export default function TeacherApp({ boot }: { boot: TeacherBoot }) {
    const [dark, setDark] = useState(
        () => localStorage.getItem('schoolcore-teacher-theme') !== 'light',
    );


const alertShown = useRef(false);

useEffect(() => {
    if (alertShown.current) return;
    alertShown.current = true;

    const colors = {
        background: dark ? '#0f172a' : '#ffffff',
        color: dark ? '#f1f5f9' : '#0f172a',
    };

    if (boot.errors?.length) {
        void Swal.fire({
            title: 'No se pudo completar',
            text: boot.errors.join(' '),
            icon: 'error',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#2563eb',
            ...colors,
        });
        return;
    }

    if (boot.flash) {
        void Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: boot.flash,
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true,
            ...colors,
        });
    }
}, [boot.flash, boot.errors, dark]);


    const [collapsed, setCollapsed] = useState(false);
    const [mobileOpen, setMobileOpen] = useState(false);

    useEffect(() => {
        document.documentElement.classList.toggle('dark', dark);
        localStorage.setItem(
            'schoolcore-teacher-theme',
            dark ? 'dark' : 'light',
        );
    }, [dark]);

    const section = new URLSearchParams(
        window.location.search,
    ).get('section');

    const currentTitle =
        boot.page === 'grades' || boot.page === 'capture'
            ? 'Calificaciones'
            : boot.page === 'students'
              ? 'Alumnos'
              : section === 'groups'
                ? 'Mis grupos'
                : section === 'subjects'
                  ? 'Materias'
                  : section === 'students'
                    ? 'Alumnos'
                    : section === 'profile'
                      ? 'Mi perfil'
                      : 'Inicio';

    const items = boot.assignments ?? [];
    const m = boot.metrics;

    return (
        <div className="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 lg:flex">
            {mobileOpen && (
                <button
                    aria-label="Cerrar menú"
                    className="fixed inset-0 z-30 bg-black/50 lg:hidden"
                    onClick={() => setMobileOpen(false)}
                />
            )}

            <aside
                className={[
                    'fixed inset-y-0 left-0 z-40 flex w-64 flex-col',
                    'border-r border-slate-200 bg-white transition-all',
                    'dark:border-slate-800 dark:bg-slate-900',
                    'lg:sticky lg:top-0 lg:h-screen',
                    collapsed ? 'lg:w-20' : 'lg:w-64',
                    mobileOpen
                        ? 'translate-x-0'
                        : '-translate-x-full lg:translate-x-0',
                ].join(' ')}
            >
                <div className="flex h-20 items-center justify-between gap-2 border-b border-slate-200 px-4 dark:border-slate-800">
                    <a
                        href="/teacher/dashboard"
                        className="flex items-center gap-3 overflow-hidden"
                    >
                        <span className="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-blue-600 text-white">
                            <GraduationCap size={23} />
                        </span>

                        {!collapsed && (
                            <span className="whitespace-nowrap font-bold">
                                SchoolCore
                                <span className="block text-xs font-normal text-slate-500">
                                    Portal docente
                                </span>
                            </span>
                        )}
                    </a>

                    <button
                        className="rounded-lg p-2 hover:bg-slate-100 dark:hover:bg-slate-800 lg:hidden"
                        aria-label="Cerrar menú"
                        onClick={() => setMobileOpen(false)}
                    >
                        <X size={20} />
                    </button>
                </div>

                <nav
                    className="flex-1 space-y-1 p-3"
                    aria-label="Navegación docente"
                >
                    {nav.map(({ title, href, icon: Icon }) => (
                        <a
                            key={title}
                            href={href}
                            title={title}
                            onClick={() => setMobileOpen(false)}
                            className={[
                                'flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium',
                                title === currentTitle
                                    ? 'bg-blue-600 text-white'
                                    : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800',
                            ].join(' ')}
                        >
                            <Icon size={19} className="shrink-0" />
                            {!collapsed && title}
                        </a>
                    ))}
                </nav>

                <div className="border-t border-slate-200 p-3 dark:border-slate-800">
                    <form action="/logout" method="POST">
                        <input
                            type="hidden"
                            name="_token"
                            value={boot.csrf}
                        />
                        <button
                            type="submit"
                            title="Cerrar sesión"
                            className="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-950"
                        >
                            <LogOut size={19} />
                            {!collapsed && 'Cerrar sesión'}
                        </button>
                    </form>
                </div>
            </aside>

            <div className="min-w-0 flex-1">
                <header className="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur dark:border-slate-800 dark:bg-slate-900/95 md:px-7">
                    <div className="flex items-center gap-3">
                        <button
                            className="rounded-lg p-2 hover:bg-slate-100 dark:hover:bg-slate-800 lg:hidden"
                            aria-label="Abrir menú"
                            onClick={() => setMobileOpen(true)}
                        >
                            <Menu size={21} />
                        </button>

                        <button
                            className="hidden rounded-lg p-2 hover:bg-slate-100 dark:hover:bg-slate-800 lg:block"
                            aria-label="Plegar menú"
                            onClick={() => setCollapsed((v) => !v)}
                        >
                            {collapsed
                                ? <PanelLeftOpen size={20} />
                                : <PanelLeftClose size={20} />}
                        </button>

                        <span className="text-sm font-semibold">
                            {currentTitle}
                        </span>
                    </div>

                    <div className="flex items-center gap-3">
                        <button
                            className="rounded-lg border border-slate-200 p-2 dark:border-slate-700"
                            aria-label={dark ? 'Tema claro' : 'Tema oscuro'}
                            onClick={() => setDark((v) => !v)}
                        >
                            {dark ? <Sun size={18} /> : <Moon size={18} />}
                        </button>

                        <span className="hidden text-sm font-medium sm:block">
                            {boot.teacher.name}
                        </span>

                        <span className="grid h-9 w-9 place-items-center rounded-full bg-blue-100 font-bold text-blue-800">
                            {boot.teacher.name.slice(0, 1).toUpperCase()}
                        </span>
                    </div>
                </header>

                <main className="mx-auto max-w-7xl space-y-5 p-4 md:p-7">
                    {!!boot.flash && (
                        <div
                            role="status"
                            className="rounded-lg bg-emerald-100 p-3 text-sm text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200"
                        >
                            {boot.flash}
                        </div>
                    )}

                    {!!boot.errors?.length && (
                        <div
                            role="alert"
                            className="rounded-lg bg-red-100 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200"
                        >
                            {boot.errors.join(' · ')}
                        </div>
                    )}

                    {boot.page === 'capture' && (
                        <GradeCapture boot={boot} />
                    )}

                    {boot.page === 'grades' && (
                        <GradeList boot={boot} />
                    )}

                    {boot.page === 'students' && (
                        <StudentsPage boot={boot} />
                    )}

                    {boot.page === 'dashboard' && (
                       section === 'profile' ? (
    <TeacherProfile boot={boot} />
) : section === 'groups' ||
                            section === 'subjects' ||
                            section === 'students' ? (
                            <>
                                <Header
                                    title={currentTitle}
                                    description="Asignaciones vigentes del ciclo escolar."
                                />
                                <Assignments
                                    items={items}
                                    mode={
                                        section === 'students'
                                            ? 'students'
                                            : section === 'subjects'
                                              ? 'subjects'
                                              : 'groups'
                                    }
                                />
                            </>
                        ) : (
                            <>
                                <Header
                                    title={`Bienvenido, ${boot.teacher.name}`}
                                    description="Resumen académico de tus asignaciones activas."
                                />

                                <div className="grid grid-cols-2 gap-3 lg:grid-cols-3 xl:grid-cols-6">
                                    {[
                                        ['Grupos', m?.groups ?? 0],
                                        ['Materias', m?.subjects ?? 0],
                                        ['Alumnos', m?.students ?? 0],
                                        ['Borradores', m?.drafts ?? 0],
                                        ['Enviadas', m?.submitted ?? 0],
                                        ['Publicadas', m?.published ?? 0],
                                    ].map(([label, value]) => (
                                        <div className={card} key={label}>
                                            <p className="text-xs text-slate-500">
                                                {label}
                                            </p>
                                            <p className="mt-2 text-2xl font-bold tabular-nums">
                                                {value}
                                            </p>
                                        </div>
                                    ))}
                                </div>

                                <div className="flex items-center justify-between">
                                    <h2 className="text-lg font-semibold">
                                        Mis clases
                                    </h2>
                                    <a
                                        href="/teacher/grades"
                                        className="text-sm font-semibold text-blue-600 dark:text-blue-400"
                                    >
                                        Ver calificaciones →
                                    </a>
                                </div>

                                <Assignments items={items} mode="groups" />
                            </>
                        )
                    )}
                </main>
            </div>
        </div>
    );
}
