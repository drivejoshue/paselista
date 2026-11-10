import {
    useCallback,
    useEffect,
    useMemo,
    useState,
    type FormEvent,
} from 'react';

import Swal from 'sweetalert2';

import {
    BookOpen,
    CircleHelp,
    Download,
    GraduationCap,
    Plus,
    Printer,
    RefreshCw,
    Search,
    UserRoundPlus,
    UsersRound,
    X,
} from 'lucide-react';

type Group = {
    id: number;
    name: string;
    grade_label: string | null;
    academic_level_id: number;
    academic_cycle_id: number;
    applied_plan_id?: number | null;
    level_name: string;
    cycle_name: string;
    active_students: number;
};

type Plan = {
    id: number;
    name: string;
    academic_cycle_id: number;
    academic_level_id: number;
    version: number;
    status: string;
};

type Link = {
    id: number;
    curriculum_plan_id: number;
    grade_label: string | null;
    subject_id: number;
    subject_name: string;
    is_official: boolean | number;
};

type Assignment = {
    id: number;
    school_group_id: number;
    plan_subject_id: number;
    teacher_profile_id: number | null;
    teacher_name: string | null;
};

type AssignmentTeacher = {
    teaching_assignment_id: number;
    teacher_profile_id: number;
    teacher_name: string;
};

type Teacher = {
    id: number;
    name: string;
    employee_code: string | null;
};

type Payload = {
    groups: Group[];
    plans: Plan[];
    links: Link[];
    assignments: Assignment[];
    assignment_teachers?: AssignmentTeacher[];
    teachers: Teacher[];
};

type SubjectRow = Link & {
    assignment: Assignment | null;
    teachers: AssignmentTeacher[];
};

const ENDPOINT = '/admin/schoolcore/academic/groups';

const toolbar =
    'inline-flex min-h-10 items-center justify-center gap-2 ' +
    'rounded-lg border border-slate-300 bg-white px-3 py-2 ' +
    'text-sm font-medium hover:bg-slate-50 ' +
    'disabled:cursor-not-allowed disabled:opacity-40 ' +
    'dark:border-slate-700 dark:bg-slate-900 ' +
    'dark:text-slate-100 dark:hover:bg-slate-800';

const field =
    'min-h-10 rounded-lg border border-slate-300 bg-white ' +
    'px-3 py-2 text-sm text-slate-900 outline-none ' +
    'focus:border-blue-500 ' +
    'dark:border-slate-700 dark:bg-slate-900 ' +
    'dark:text-slate-100';

const panel =
    'rounded-xl border border-slate-200 bg-white ' +
    'dark:border-slate-800 dark:bg-slate-900';

const primary =
    'inline-flex min-h-10 items-center justify-center gap-2 ' +
    'rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold ' +
    'text-white hover:bg-blue-700 ' +
    'disabled:cursor-not-allowed disabled:opacity-40';

function alertTheme() {
    const dark = document.documentElement.classList.contains('dark');

    return {
        background: dark ? '#0f172a' : '#ffffff',
        color: dark ? '#f8fafc' : '#0f172a',
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#64748b',
    };
}

function apiError(payload: unknown): string {
    if (!payload || typeof payload !== 'object') {
        return 'No se pudo procesar la solicitud.';
    }

    const result = payload as {
        message?: string;
        errors?: Record<string, string[]>;
    };

    return (
        Object.values(result.errors ?? {}).flat().join(' ') ||
        result.message ||
        'No se pudo procesar la solicitud.'
    );
}

export default function AcademicGroups() {
    const [data, setData] = useState<Payload | null>(null);

    const [selectedGroup, setSelectedGroup] =
        useState<number | null>(null);

    const [selectedPlan, setSelectedPlan] =
        useState<number | null>(null);

    const [query, setQuery] = useState('');

    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);

    const [error, setError] = useState<string | null>(null);

    // Modal para administrar docentes de una materia.
    const [openSubjectId, setOpenSubjectId] =
        useState<number | null>(null);

    const [newTeacherId, setNewTeacherId] = useState('');

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(ENDPOINT, {
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                },
            });

            const payload: unknown =
                await response.json().catch(() => null);

            if (!response.ok) {
                throw new Error(apiError(payload));
            }

            setData(payload as Payload);
        } catch (reason) {
            setError(
                reason instanceof Error
                    ? reason.message
                    : 'No fue posible consultar los grupos.',
            );
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        void load();
    }, [load]);

    // Grupo seleccionado.
    const group = useMemo(() => {
        const groups = data?.groups ?? [];

        return (
            groups.find((g) => g.id === selectedGroup) ??
            groups.find((g) => g.level_name === 'Secundaria') ??
            groups[0] ??
            null
        );
    }, [data, selectedGroup]);

    // Planes compatibles con el grupo.
    const matchingPlans = useMemo(() => {
        if (!group || !data) return [];

        return data.plans.filter(
            (p) =>
                p.academic_cycle_id === group.academic_cycle_id &&
                p.academic_level_id === group.academic_level_id,
        );
    }, [data, group]);

   const plan = useMemo(() => {
    return (
        matchingPlans.find(
            p => p.id === group?.applied_plan_id,
        ) ??
        matchingPlans.find(
            p => p.id === selectedPlan,
        ) ??
        (
            matchingPlans.length === 1
                ? matchingPlans[0]
                : null
        )
    );
}, [
    matchingPlans,
    selectedPlan,
    group?.applied_plan_id,
]);

    // Materias y docentes vinculados.
    const subjects = useMemo<SubjectRow[]>(() => {
        if (!data || !group || !plan) return [];

        return data.links
            .filter(
                (link) =>
                    link.curriculum_plan_id === plan.id &&
                    (
                        link.grade_label === null ||
                        link.grade_label === group.grade_label
                    ),
            )
            .map((link) => {
                const assignment =
                    data.assignments.find(
                        (a) =>
                            a.school_group_id === group.id &&
                            a.plan_subject_id === link.id,
                    ) ?? null;

                let teachers: AssignmentTeacher[] = [];

                if (assignment) {
                    if (data.assignment_teachers !== undefined) {
                        teachers = data.assignment_teachers.filter(
                            (t) =>
                                t.teaching_assignment_id ===
                                assignment.id,
                        );
                    } else if (
                        assignment.teacher_profile_id !== null &&
                        assignment.teacher_name
                    ) {
                        // Compatibilidad visual con el JSON anterior.
                        // La escritura múltiple requiere el nuevo backend.
                        teachers = [{
                            teaching_assignment_id: assignment.id,
                            teacher_profile_id:
                                assignment.teacher_profile_id,
                            teacher_name: assignment.teacher_name,
                        }];
                    }
                }

                return {
                    ...link,
                    assignment,
                    teachers,
                };
            });
    }, [data, group, plan]);

    const filteredSubjects = useMemo(() => {
        const value = query.trim().toLocaleLowerCase('es-MX');

        if (!value) return subjects;

        return subjects.filter((subject) =>
            [
                subject.subject_name,
                subject.grade_label ?? 'General',
                ...subject.teachers.map((t) => t.teacher_name),
            ].some((text) =>
                text.toLocaleLowerCase('es-MX').includes(value),
            ),
        );
    }, [subjects, query]);

    const assignedCount = subjects.filter(
        (subject) => subject.teachers.length > 0,
    ).length;

    const linkedTeachers = new Set(
        subjects.flatMap((subject) =>
            subject.teachers.map((t) => t.teacher_profile_id),
        ),
    ).size;

    const selectedSubject =
        subjects.find((subject) => subject.id === openSubjectId) ??
        null;

    const availableTeachers = (data?.teachers ?? []).filter(
        (teacher) =>
            !selectedSubject?.teachers.some(
                (t) => t.teacher_profile_id === teacher.id,
            ),
    );

    const multiTeacherReady =
        data?.assignment_teachers !== undefined;

    function openTeacherManager(subjectId: number) {
        setOpenSubjectId(subjectId);
        setNewTeacherId('');
        setError(null);
    }

    function closeTeacherManager() {
        if (saving) return;

        setOpenSubjectId(null);
        setNewTeacherId('');
    }

    async function addTeacher(e: FormEvent<HTMLFormElement>) {
        e.preventDefault();

        if (
            !group ||
            !selectedSubject ||
            !newTeacherId ||
            !multiTeacherReady ||
            saving
        ) {
            return;
        }

        const teacherId = Number(newTeacherId);

        if (
            !Number.isInteger(teacherId) ||
            !availableTeachers.some((t) => t.id === teacherId)
        ) {
            return;
        }

        setSaving(true);
        setError(null);

        try {
            const csrf =
                document.querySelector<HTMLMetaElement>(
                    'meta[name="csrf-token"]',
                )?.content;

            if (!csrf) {
                throw new Error(
                    'No se encontró el token CSRF. Recarga la página.',
                );
            }

            const response = await fetch(
                `${ENDPOINT}/assign`,
                {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({
                        school_group_id: group.id,
                        plan_subject_id: selectedSubject.id,
                        teacher_profile_id: teacherId,
                    }),
                },
            );

            const payload: unknown =
                await response.json().catch(() => null);

            if (!response.ok) {
                throw new Error(apiError(payload));
            }

            setOpenSubjectId(null);
            setNewTeacherId('');

            await load();

            void Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Docente agregado correctamente',
                timer: 2500,
                timerProgressBar: true,
                showConfirmButton: false,
                ...alertTheme(),
            });
        } catch (reason) {
            const message =
                reason instanceof Error
                    ? reason.message
                    : 'No se pudo agregar al docente.';

            setError(message);

            void Swal.fire({
                title: 'No se pudo agregar',
                text: message,
                icon: 'error',
                confirmButtonText: 'Entendido',
                ...alertTheme(),
            });
        } finally {
            setSaving(false);
        }
    }


    async function updateTeacherAssignment(
        action: 'remove' | 'replace',
        teacherId: number,
        replacementId?: number,
    ) {
        if (!group || !selectedSubject || saving) return;

        setSaving(true);
        setError(null);

        try {
            const csrf = document.querySelector<HTMLMetaElement>(
                'meta[name="csrf-token"]',
            )?.content;

            if (!csrf) {
                throw new Error('No se encontró el token CSRF.');
            }

            const response = await fetch(`${ENDPOINT}/assign`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    action,
                    school_group_id: group.id,
                    plan_subject_id: selectedSubject.id,
                    teacher_profile_id: teacherId,
                    ...(action === 'replace'
                        ? { replacement_teacher_profile_id: replacementId }
                        : {}),
                }),
            });

            const payload: unknown =
                await response.json().catch(() => null);

            if (!response.ok) {
                throw new Error(apiError(payload));
            }

            await load();

            void Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: action === 'replace'
                    ? 'Docente reemplazado'
                    : 'Docente desasignado',
                text: 'Las calificaciones se conservaron.',
                timer: 2500,
                showConfirmButton: false,
                ...alertTheme(),
            });
        } catch (cause) {
            const message = cause instanceof Error
                ? cause.message
                : 'No se pudo modificar la asignación.';

            setError(message);

            void Swal.fire({
                title: 'Error de asignación',
                text: message,
                icon: 'error',
                ...alertTheme(),
            });
        } finally {
            setSaving(false);
        }
    }

    async function removeTeacher(teacher: AssignmentTeacher) {
        if (saving || !group || !selectedSubject) return;

        const confirmation = await Swal.fire({
            title: 'Desasignar docente',
            text:
                `${teacher.teacher_name} dejará de tener acceso a ` +
                `${selectedSubject.subject_name} de ${group.name}. ` +
                'Las calificaciones se conservarán.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Desasignar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            ...alertTheme(),
        });

        if (!confirmation.isConfirmed) return;

        await updateTeacherAssignment(
            'remove',
            teacher.teacher_profile_id,
        );
    }

    async function replaceTeacher(teacher: AssignmentTeacher) {
        if (
            saving ||
            !group ||
            !selectedSubject ||
            availableTeachers.length === 0
        ) return;

        const options = Object.fromEntries(
            availableTeachers.map((item) => [
                String(item.id),
                item.name,
            ]),
        );

        const result = await Swal.fire({
            title: 'Reemplazar docente',
            text:
                `Selecciona quién continuará impartiendo ` +
                `${selectedSubject.subject_name} en ${group.name}.`,
            input: 'select',
            inputOptions: options,
            inputPlaceholder: 'Seleccionar docente',
            inputValidator: (value) =>
                value ? undefined : 'Selecciona un docente.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Confirmar reemplazo',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            ...alertTheme(),
        });

        if (!result.isConfirmed) return;

        const replacementId = Number(result.value);

        if (
            !availableTeachers.some(
                (item) => item.id === replacementId,
            )
        ) return;

        await updateTeacherAssignment(
            'replace',
            teacher.teacher_profile_id,
            replacementId,
        );
    }


    function exportCsv() {
        if (!group || !plan || filteredSubjects.length === 0) {
            return;
        }

        const rows = [
            ['Materia', 'Grado', 'Grupo', 'Docentes'],
            ...filteredSubjects.map((subject) => [
                subject.subject_name,
                subject.grade_label ?? 'General',
                group.name,
                subject.teachers
                    .map((t) => t.teacher_name)
                    .join(' / ') || 'Sin asignar',
            ]),
        ];

        const safeCell = (cell: string) => {
            const safe = /^[\s]*[=+\-@]/.test(cell)
                ? `'${cell}`
                : cell;

            return `"${safe.replaceAll('"', '""')}"`;
        };

        const csv =
            '\uFEFF' +
            rows.map((row) =>
                row.map(safeCell).join(','),
            ).join('\r\n');

        const url = URL.createObjectURL(
            new Blob([csv], {
                type: 'text/csv;charset=utf-8',
            }),
        );

        const anchor = document.createElement('a');

        anchor.href = url;
        anchor.download =
            `academic-grupo-${group.id}-materias.csv`;

        document.body.appendChild(anchor);
        anchor.click();
        anchor.remove();

        window.setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    return (
        <div className="space-y-5 text-slate-900 dark:text-slate-100">
            {/* ENCABEZADO */}

            <header>
                <p className="text-xs font-semibold uppercase tracking-widest text-blue-600">
                    Academic / Organización
                </p>

                <h1 className="mt-1 text-2xl font-semibold">
                    Grupos y materias
                </h1>

                <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Consulta las materias de cada grupo y administra
                    los docentes que las imparten.
                </p>
            </header>

            {/* HERRAMIENTAS */}

            <div className="flex flex-wrap items-center gap-2 rounded-xl border border-slate-300 bg-slate-100 p-2 dark:border-slate-700 dark:bg-slate-800">
               <label
    htmlFor="academic-group-selector"
    className="text-sm font-semibold text-slate-700 dark:text-slate-200"
>
    Grupo escolar:
</label>
 <select
                     id="academic-group-selector"
                    aria-label="Elegir grupo"
                    className={`${field} min-w-48 flex-1`}
                    value={group?.id ?? ''}
                    onChange={(e) => {
                        setSelectedGroup(Number(e.target.value));
                        setSelectedPlan(null);
                        setOpenSubjectId(null);
                        setQuery('');
                    }}
                >
                    {data?.groups.length === 0 && (
                        <option value="">Sin grupos activos</option>
                    )}

                    {(data?.groups ?? []).map((g) => (
                        <option key={g.id} value={g.id}>
                            {g.level_name} · {g.name} · {g.cycle_name}
                        </option>
                    ))}
                </select>

                <div className="flex min-w-40 flex-1 items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 dark:border-slate-700 dark:bg-slate-900">
                    <Search size={16} className="text-slate-500" />

                    <input
                        aria-label="Buscar materias"
                        className="min-h-10 w-full bg-transparent text-sm outline-none"
                        placeholder="Buscar materia o docente..."
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                    />
                </div>

                <button
                    type="button"
                    className={toolbar}
                    disabled={loading}
                    onClick={() => void load()}
                >
                    <RefreshCw size={16} />
                    Actualizar
                </button>

                <button
                    type="button"
                    className={toolbar}
                    disabled={filteredSubjects.length === 0}
                    onClick={exportCsv}
                >
                    <Download size={16} />
                    CSV
                </button>

                <button
                    type="button"
                    className={toolbar}
                    onClick={() => window.print()}
                >
                    <Printer size={16} />
                    Imprimir / PDF
                </button>
            </div>

            {error && (
                <div
                    role="alert"
                    className="rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300"
                >
                    {error}
                </div>
            )}

            {loading && (
                <p className="text-sm text-slate-500">
                    Consultando grupos y asignaciones...
                </p>
            )}

            {!loading && data && !multiTeacherReady && (
                <div className="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300">
                    El servidor todavía no entrega
                    {' '}<strong>assignment_teachers</strong>.
                    Actualiza AcademicGroupsController.php antes de
                    habilitar asignaciones múltiples.
                </div>
            )}

            {/* MÉTRICAS */}

            {!loading && group && (
                <>
                    <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                        {[
                            {
                                label: 'Grupo',
                                value: group.name,
                                icon: UsersRound,
                            },
                            {
                                label: 'Alumnos activos',
                                value: String(group.active_students),
                                icon: UsersRound,
                            },
                            {
                                label: 'Materias del grado',
                                value: plan
                                    ? String(subjects.length)
                                    : '—',
                                icon: BookOpen,
                            },
                            {
                                label: 'Materias con docente',
                                value: plan
                                    ? `${assignedCount}/${subjects.length}`
                                    : '—',
                                icon: GraduationCap,
                            },
                        ].map(({ label, value, icon: Icon }) => (
                            <section
                                key={label}
                                className={`${panel} p-4`}
                            >
                                <div className="flex items-center justify-between text-xs text-slate-500">
                                    <span>{label}</span>
                                    <Icon
                                        size={17}
                                        className="text-blue-500"
                                    />
                                </div>

                                <div className="mt-2 text-xl font-semibold">
                                    {value}
                                </div>
                            </section>
                        ))}
                    </div>

                    {/* PLAN Y MATERIAS */}

                    <section className={panel}>
                        <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4 dark:border-slate-800">
                            <div>
                                <h2 className="font-semibold">
                                    Materias de {group.name}
                                </h2>

                                <p className="mt-1 text-xs text-slate-500">
                                    {group.level_name}
                                    {' · Grado '}
                                    {group.grade_label ?? 'sin definir'}
                                    {' · '}
                                    {group.cycle_name}
                                </p>

                                {plan && (
                                    <p className="mt-2 text-xs text-blue-600 dark:text-blue-400">
                                        {linkedTeachers} docente(s) vinculado(s)
                                        a las materias del grupo.
                                    </p>
                                )}
                            </div>

                            <label className="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                Plan curricular

                                <select
                                    className={field}
                                    value={plan?.id ?? ''}
                                  disabled={
    Boolean(group?.applied_plan_id) ||
    matchingPlans.length <= 1
}
                                    onChange={(e) => {
                                        setSelectedPlan(
                                            Number(e.target.value) || null,
                                        );
                                        setOpenSubjectId(null);
                                    }}
                                >
                                    {matchingPlans.length === 0 && (
                                        <option value="">
                                            Sin plan compatible
                                        </option>
                                    )}

                                    {matchingPlans.length > 1 && (
                                        <option value="">
                                            Selecciona un plan
                                        </option>
                                    )}

                                    {matchingPlans.map((p) => (
                                        <option key={p.id} value={p.id}>
                                            {p.name} · v{p.version}
                                        </option>
                                    ))}
                                </select>
                            </label>
                        </div>

                        {!plan ? (
                            <div className="p-5 text-sm text-amber-600 dark:text-amber-300">
                                {matchingPlans.length > 1
                                    ? 'Hay varios planes compatibles. Selecciona uno para consultar sus materias.'
                                    : 'No existe un plan curricular compatible con este grupo. Configúralo en Planes y programas.'}
                            </div>
                        ) : subjects.length === 0 ? (
                            <div className="p-5 text-sm text-slate-500">
                                El plan no tiene materias vinculadas
                                para este grado.
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[780px] text-left text-sm">
                                    <thead className="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800/60">
                                        <tr>
                                            <th className="px-4 py-3">
                                                Materia
                                            </th>
                                            <th className="px-4 py-3">
                                                Grado
                                            </th>
                                            <th className="px-4 py-3">
                                                Docentes asignados
                                            </th>
                                            <th className="px-4 py-3">
                                                Estado
                                            </th>
                                            <th className="px-4 py-3 text-right">
                                                Acción
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        {filteredSubjects.map((subject) => (
                                            <tr
                                                key={subject.id}
                                                className="border-t border-slate-100 dark:border-slate-800"
                                            >
                                                <td className="px-4 py-4 font-medium">
                                                    {subject.subject_name}
                                                </td>

                                                <td className="px-4 py-4">
                                                    {subject.grade_label ??
                                                        'Todos los grados'}
                                                </td>

                                                <td className="px-4 py-4">
                                                    {subject.teachers.length ? (
                                                        <div className="flex flex-wrap gap-2">
                                                            {subject.teachers.map((t) => (
                                                                <span
                                                                    key={t.teacher_profile_id}
                                                                    className="rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-300"
                                                                >
                                                                    {t.teacher_name}
                                                                </span>
                                                            ))}
                                                        </div>
                                                    ) : (
                                                        <span className="text-slate-500">
                                                            Sin docentes
                                                        </span>
                                                    )}
                                                </td>

                                                <td className="px-4 py-4">
                                                    <span
                                                        className={
                                                            subject.teachers.length
                                                                ? 'text-emerald-600 dark:text-emerald-400'
                                                                : 'text-amber-600 dark:text-amber-400'
                                                        }
                                                    >
                                                        {subject.teachers.length
                                                            ? `${subject.teachers.length} asignado(s)`
                                                            : 'Pendiente'}
                                                    </span>
                                                </td>

                                                <td className="px-4 py-4 text-right">
                                                    <button
                                                        type="button"
                                                        className={toolbar}
                                                        disabled={
                                                            saving ||
                                                            !multiTeacherReady
                                                        }
                                                        onClick={() =>
                                                            openTeacherManager(subject.id)
                                                        }
                                                    >
                                                        <UserRoundPlus size={16} />
                                                        Administrar docentes
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}

                                        {filteredSubjects.length === 0 && (
                                            <tr>
                                                <td
                                                    colSpan={5}
                                                    className="p-8 text-center text-slate-500"
                                                >
                                                    No hay materias que coincidan
                                                    con la búsqueda.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </section>

                    <div className="flex items-start gap-2 rounded-xl border border-blue-200 bg-blue-50 p-4 text-xs text-blue-800 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                        <CircleHelp
                            size={18}
                            className="shrink-0"
                        />

                        <p>
                            Una materia puede tener varios docentes
                            asignados al mismo grupo y ciclo escolar.
                            Agregar otro docente no sustituye al
                            anterior ni modifica alumnos, matrículas
                            o libros de calificaciones.
                            La retirada y el historial de asignaciones
                            se habilitarán en una etapa posterior.
                        </p>
                    </div>
                </>
            )}

            {!loading && !error && data?.groups.length === 0 && (
                <div className={`${panel} p-6 text-sm text-slate-500`}>
                    No hay grupos activos en el ciclo vigente.
                </div>
            )}

            {/* MODAL ADMINISTRAR DOCENTES */}

            {selectedSubject && group && (
                <div
                    className="fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm"
                    onMouseDown={(e) => {
                        if (e.target === e.currentTarget) {
                            closeTeacherManager();
                        }
                    }}
                >
                    <div
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="teacher-manager-title"
                        className="w-full max-w-xl space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-slate-700 dark:bg-slate-900"
                    >
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wide text-blue-600">
                                    Asignación docente
                                </p>

                                <h2
                                    id="teacher-manager-title"
                                    className="mt-1 text-xl font-bold"
                                >
                                    {selectedSubject.subject_name}
                                </h2>

                                <p className="mt-1 text-sm text-slate-500">
                                    {group.name} · {group.cycle_name}
                                </p>
                            </div>

                            <button
                                type="button"
                                className="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800"
                                aria-label="Cerrar"
                                disabled={saving}
                                onClick={closeTeacherManager}
                            >
                                <X size={20} />
                            </button>
                        </div>

                        <div>
                            <h3 className="text-sm font-semibold">
                                Docentes actualmente asignados
                            </h3>

                            {selectedSubject.teachers.length === 0 ? (
                                <p className="mt-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-500 dark:bg-slate-800">
                                    Esta materia todavía no tiene docentes.
                                </p>
                            ) : (
                                <div className="mt-3 space-y-2">
                                    {selectedSubject.teachers.map((teacher) => (
                                        <div
                                            key={teacher.teacher_profile_id}
                                            className="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-3 dark:border-slate-700"
                                        >
                                            <span className="text-sm font-medium">
                                                {teacher.teacher_name}
                                            </span>


<div className="flex flex-wrap items-center gap-2">
    <button
        type="button"
        className={toolbar}
        disabled={saving || availableTeachers.length === 0}
        onClick={() => void replaceTeacher(teacher)}
    >
        Reemplazar
    </button>

    <button
        type="button"
        className={
            'rounded-lg border border-red-300 px-3 py-2 ' +
            'text-xs font-semibold text-red-700 ' +
            'hover:bg-red-50 disabled:opacity-40 ' +
            'dark:border-red-900 dark:text-red-300 ' +
            'dark:hover:bg-red-950'
        }
        disabled={saving}
        onClick={() => void removeTeacher(teacher)}
    >
        Desasignar
    </button>
</div>

                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>

                        <form
                            onSubmit={(e) => void addTeacher(e)}
                            className="space-y-3 border-t border-slate-200 pt-4 dark:border-slate-800"
                        >
                            <label className="block text-sm font-semibold">
                                Agregar otro docente
                            </label>

                            <select
                                className={`${field} w-full`}
                                value={newTeacherId}
                                disabled={
                                    saving ||
                                    availableTeachers.length === 0
                                }
                                onChange={(e) =>
                                    setNewTeacherId(e.target.value)
                                }
                            >
                                <option value="">
                                    Selecciona un docente
                                </option>

                                {availableTeachers.map((teacher) => (
                                    <option
                                        key={teacher.id}
                                        value={teacher.id}
                                    >
                                        {teacher.name}
                                        {teacher.employee_code
                                            ? ` · ${teacher.employee_code}`
                                            : ''}
                                    </option>
                                ))}
                            </select>

                            {availableTeachers.length === 0 && (
                                <p className="text-xs text-slate-500">
                                    No hay más docentes activos disponibles
                                    para esta materia.
                                </p>
                            )}

                            <p className="text-xs text-slate-500">
                                El nuevo docente se agrega a esta asignación.
                                Los demás conservarán su vínculo.
                            </p>

                            <div className="flex flex-wrap justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    className={toolbar}
                                    disabled={saving}
                                    onClick={closeTeacherManager}
                                >
                                    Cerrar
                                </button>

                                <button
                                    type="submit"
                                    className={primary}
                                    disabled={
                                        saving ||
                                        !newTeacherId ||
                                        !multiTeacherReady
                                    }
                                >
                                    <Plus size={17} />
                                    {saving
                                        ? 'Guardando...'
                                        : 'Agregar docente'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
