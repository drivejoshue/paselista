import { useCallback, useEffect, useMemo, useState } from 'react';
import Swal from 'sweetalert2';
import {
    ArrowDown, ArrowUp, Archive, CheckCircle2, Copy,
    FilePlus2, Library, Plus, RefreshCw, Save,
    Trash2, Users, X,
} from 'lucide-react';

type Cycle = {
    id: number;
    name: string;
    is_active: boolean | number;
};

type Level = {
    id: number;
    name: string;
};

type Plan = {
    id: number;
    name: string;
    framework_code: string | null;
    academic_cycle_id: number;
    academic_level_id: number;
    level_name: string;
    cycle_name: string;
    version: number;
    revision: number;
    status: string;
    parent_plan_id: number | null;
};

type Subject = {
    id: number;
    name: string;
    code: string;
    kind: string;
};

type Link = {
    id: number | null;
    subject_id: number;
    subject_name: string;
    grade_label: string | null;
    is_official: boolean | number;
    affects_average: boolean | number;
};

type Group = {
    id: number;
    name: string;
    grade_label: string | null;
    academic_cycle_id: number;
    academic_level_id: number;
};

type Template = {
    id: string;
    name: string;
    level: string;
    framework: string;
    source: string;
    note: string;
};

type Data = {
    cycles: Cycle[];
    levels: Level[];
    plans: Plan[];
    links: (Link & { curriculum_plan_id: number })[];
    subjects: Subject[];
    groups: Group[];
    bindings: {
        school_group_id: number;
        curriculum_plan_id: number;
    }[];
    templates: Template[];
    usage: Record<number, number>;
    locked_links: number[];
};

type Editor = {
    name: string;
    framework_code: string;
    links: Link[];
};

const ROOT = '/admin/schoolcore/academic/plan-manager';

const field =
    'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 ' +
    'text-sm text-slate-900 outline-none focus:border-blue-500 ' +
    'dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100';

const neutral =
    'inline-flex items-center justify-center gap-2 rounded-lg border ' +
    'border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-100 ' +
    'disabled:opacity-40 dark:border-slate-700 dark:hover:bg-slate-800';

const primary =
    'inline-flex items-center justify-center gap-2 rounded-lg ' +
    'bg-blue-600 px-3 py-2 text-sm font-semibold text-white ' +
    'hover:bg-blue-700 disabled:opacity-40';

const panel =
    'rounded-xl border border-slate-200 bg-white ' +
    'dark:border-slate-800 dark:bg-slate-900';

const initialData: Data = {
    cycles: [],
    levels: [],
    plans: [],
    links: [],
    subjects: [],
    groups: [],
    bindings: [],
    templates: [],
    usage: {},
    locked_links: [],
};

function message(value: unknown): string {
    if (!value || typeof value !== 'object') {
        return 'No se pudo completar la operación.';
    }

    const payload = value as {
        message?: string;
        errors?: Record<string, string[]>;
    };

    return (
        Object.values(payload.errors ?? {}).flat().join(' ') ||
        payload.message ||
        'No se pudo completar la operación.'
    );
}

async function request<T>(
    url: string,
    body?: object,
): Promise<T> {
    const csrf = document.querySelector<HTMLMetaElement>(
        'meta[name="csrf-token"]',
    )?.content;

    const response = await fetch(url, {
        method: body ? 'POST' : 'GET',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            ...(body
                ? {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf ?? '',
                }
                : {}),
        },
        ...(body ? { body: JSON.stringify(body) } : {}),
    });

    const value: unknown = await response.json().catch(() => null);

    if (!response.ok) {
        throw new Error(message(value));
    }

    if (!value || typeof value !== 'object') {
        throw new Error('El servidor no devolvió datos JSON.');
    }

    return value as T;
}

function theme() {
    const dark = document.documentElement.classList.contains('dark');

    return {
        background: dark ? '#0f172a' : '#ffffff',
        color: dark ? '#f8fafc' : '#0f172a',
        confirmButtonColor: '#2563eb',
    };
}

function gradeChoices(
    level: string,
    groups: Group[],
    links: Link[],
): string[] {
    const name = level.toLocaleLowerCase('es-MX');

    const count =
        name.includes('primaria') ||
        name.includes('bachillerato')
            ? 6
            : 3;

    const fromGroups = groups
        .map(g => g.grade_label)
        .filter((value): value is string => !!value);

    const fromLinks = links
        .map(link => link.grade_label)
        .filter((value): value is string => !!value);

    const defaults = Array.from(
        { length: count },
        (_, index) => {
            const n = index + 1;
            const ordinal = [
                '1er', '2do', '3er',
                '4to', '5to', '6to',
            ][index];

            return name.includes('bachillerato')
                ? `${ordinal} semestre`
                : String(n);
        },
    );

    return [...new Set([
        ...fromGroups,
        ...fromLinks,
        ...defaults,
    ])];
}

export default function AcademicPlanManager() {
    const [data, setData] = useState<Data>(initialData);

    const [selectedId, setSelectedId] =
        useState<number | null>(null);

    const [editor, setEditor] = useState<Editor>({
        name: '',
        framework_code: '',
        links: [],
    });

    const [grade, setGrade] = useState<string | null>('1');
    const [filter, setFilter] = useState('');
    const [dirty, setDirty] = useState(false);
    const [loading, setLoading] = useState(true);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');
    const [notice, setNotice] = useState('');

    const [subjectId, setSubjectId] = useState('');
    const [newSubjectName, setNewSubjectName] = useState('');
    const [newSubjectCode, setNewSubjectCode] = useState('');
    const [chosenGroups, setChosenGroups] = useState<number[]>([]);

    const [view, setView] = useState<
        'curriculum' | 'groups' | 'library' | 'subjects'
    >('curriculum');

    const [showCreate, setShowCreate] = useState(false);
    const [newName, setNewName] = useState('');
    const [newCycle, setNewCycle] = useState('');
    const [newLevel, setNewLevel] = useState('');
    const [newTemplate, setNewTemplate] = useState('');

    const plan =
        data.plans.find(p => p.id === selectedId) ?? null;

    const compatibleGroups = data.groups.filter(
        g =>
            g.academic_cycle_id === plan?.academic_cycle_id &&
            g.academic_level_id === plan?.academic_level_id,
    );

    const grades = plan
        ? gradeChoices(
            plan.level_name,
            compatibleGroups,
            editor.links,
        )
        : [];

    const visible = editor.links
        .map((link, index) => ({ ...link, index }))
        .filter(link => link.grade_label === grade);

    const general = editor.links.filter(
        link => link.grade_label === null,
    );

    const available = data.subjects.filter(
        subject =>
            !editor.links.some(
                link =>
                    link.subject_id === subject.id &&
                    (
                        link.grade_label === grade ||
                        link.grade_label === null ||
                        grade === null
                    ),
            ),
    );

    const isDraft = plan?.status === 'draft';

    const locked = useMemo(
        () => new Set(data.locked_links),
        [data.locked_links],
    );

    const usage = plan
        ? Number(data.usage[plan.id] ?? 0)
        : 0;

    function fillEditor(fresh: Data, id: number | null) {
        const p = fresh.plans.find(x => x.id === id);

        setSelectedId(p?.id ?? null);

        setEditor(
            p
                ? {
                    name: p.name,
                    framework_code: p.framework_code ?? '',
                    links: fresh.links
                        .filter(x => x.curriculum_plan_id === p.id)
                        .map(x => ({
                            id: x.id,
                            subject_id: x.subject_id,
                            subject_name: x.subject_name,
                            grade_label: x.grade_label,
                            is_official: !!x.is_official,
                            affects_average: !!x.affects_average,
                        })),
                }
                : {
                    name: '',
                    framework_code: '',
                    links: [],
                },
        );

        setGrade('1');
        setDirty(false);
        setSubjectId('');
        setChosenGroups([]);
    }

    const load = useCallback(
        async (target?: number | null) => {
            setLoading(true);

            try {
                const fresh = await request<Data>(ROOT);
                setData(fresh);

                const id = target === undefined
                    ? (
                        selectedId ??
                        fresh.plans[0]?.id ??
                        null
                    )
                    : target;

                fillEditor(fresh, id);
                setError('');
            } catch (cause) {
                setError(
                    cause instanceof Error
                        ? cause.message
                        : 'No se pudo consultar el gestor.',
                );
            } finally {
                setLoading(false);
            }
        },
        [selectedId],
    );

    useEffect(() => {
        void load();
        // Carga inicial del gestor.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    function mutate(fn: (previous: Editor) => Editor) {
        setEditor(fn);
        setDirty(true);
        setNotice('');
    }

    async function choosePlan(id: number) {
        if (dirty) {
            const confirmation = await Swal.fire({
                title: 'Cambios sin guardar',
                text: 'Cambiar de plan descartará tus modificaciones.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Descartar',
                cancelButtonText: 'Continuar editando',
                ...theme(),
            });

            if (!confirmation.isConfirmed) return;
        }

        fillEditor(data, id);
        setView('curriculum');
        setError('');
    }

    async function perform(
        action: string,
        extra: Record<string, unknown> = {},
        target?: number,
    ) {
        if (busy) return;

        const p = data.plans.find(
            x => x.id === (target ?? selectedId),
        );

        setBusy(true);
        setError('');
        setNotice('');

        try {
            const result = await request<{
                id: number | null;
                message: string;
            }>(
                `${ROOT}/manage`,
                {
                    action,
                    ...(p
                        ? {
                            plan_id: p.id,
                            revision: p.revision,
                        }
                        : {}),
                    ...extra,
                },
            );

            await load(
                result.id === null
                    ? (
                        action === 'delete'
                            ? null
                            : selectedId
                    )
                    : result.id,
            );

            setNotice(result.message);

            if (action === 'create') {
                setShowCreate(false);
                setView('curriculum');
            }
        } catch (cause) {
            setError(
                cause instanceof Error
                    ? cause.message
                    : 'No se pudo completar la acción.',
            );
        } finally {
            setBusy(false);
        }
    }

    async function save() {
        if (!plan || !isDraft) return;

        if (!editor.name.trim()) {
            setError('Escribe un nombre para el plan.');
            return;
        }

        await perform('save', {
            name: editor.name.trim(),
            framework_code: editor.framework_code || null,
            links: editor.links.map(link => ({
                id: link.id,
                subject_id: link.subject_id,
                grade_label: link.grade_label,
                is_official: !!link.is_official,
                affects_average: !!link.affects_average,
            })),
        });
    }

    function appendSubject(id: number, name: string) {
        if (!isDraft) return;

        if (editor.links.some(
            link =>
                link.subject_id === id &&
                (
                    link.grade_label === grade ||
                    link.grade_label === null ||
                    grade === null
                ),
        )) {
            setError(
                'La materia ya está vinculada a este ámbito.',
            );
            return;
        }

        mutate(previous => ({
            ...previous,
            links: [
                ...previous.links,
                {
                    id: null,
                    subject_id: id,
                    subject_name: name,
                    grade_label: grade,
                    is_official: false,
                    affects_average: false,
                },
            ],
        }));

        setSubjectId('');
    }

    async function createSubject() {
        if (!newSubjectName.trim() || !newSubjectCode.trim()) {
            return;
        }

        setBusy(true);
        setError('');

        try {
            const result = await request<{
                id: number;
                message: string;
            }>(
                '/admin/schoolcore/academic/catalog/subjects',
                {
                    name: newSubjectName.trim(),
                    code: newSubjectCode.trim().toLowerCase(),
                    kind: 'custom',
                    curriculum_plan_id: null,
                },
            );

            const subject: Subject = {
                id: result.id,
                name: newSubjectName.trim(),
                code: newSubjectCode.trim().toLowerCase(),
                kind: 'custom',
            };

            setData(previous => ({
                ...previous,
                subjects: [...previous.subjects, subject]
                    .sort((a, b) => a.name.localeCompare(b.name)),
            }));

            appendSubject(subject.id, subject.name);

            setNewSubjectName('');
            setNewSubjectCode('');

            setNotice(
                'Materia registrada en el catálogo escolar. Si estás editando un borrador, guarda el plan para vincularla.',
            );
        } catch (cause) {
            setError(
                cause instanceof Error
                    ? cause.message
                    : 'Error al crear materia.',
            );
        } finally {
            setBusy(false);
        }
    }

    function changeLink(index: number, patch: Partial<Link>) {
        mutate(previous => ({
            ...previous,
            links: previous.links.map(
                (row, i) =>
                    i === index
                        ? { ...row, ...patch }
                        : row,
            ),
        }));
    }

    function removeLink(index: number) {
        mutate(previous => ({
            ...previous,
            links: previous.links.filter(
                (_, i) => i !== index,
            ),
        }));
    }

    function reorder(index: number, direction: -1 | 1) {
        const groupIndices = editor.links
            .map(
                (link, i) =>
                    link.grade_label === grade
                        ? i
                        : -1,
            )
            .filter(i => i >= 0);

        const at = groupIndices.indexOf(index);
        const to = groupIndices[at + direction];

        if (to === undefined) return;

        mutate(previous => {
            const links = [...previous.links];

            [links[index], links[to]] = [
                links[to],
                links[index],
            ];

            return { ...previous, links };
        });
    }

    async function confirmAction(
        action: 'activate' | 'clone' | 'archive' | 'delete',
    ) {
        if (!plan) return;

        if (action === 'clone') {
            const out = await Swal.fire({
                title: 'Crear nueva versión',
                input: 'text',
                inputValue:
                    `${plan.name} · v${plan.version + 1}`,
                inputValidator: value =>
                    value?.trim()
                        ? undefined
                        : 'Escribe un nombre',
                showCancelButton: true,
                confirmButtonText: 'Duplicar',
                ...theme(),
            });

            if (out.isConfirmed) {
                await perform('clone', {
                    name: String(out.value),
                });
            }

            return;
        }

        if (dirty) {
            setError(
                'Guarda o descarta tus cambios antes de continuar.',
            );
            return;
        }

        const texts = {
            activate:
                'Una vez activado, el plan se cerrará para edición. Los cambios posteriores requerirán otra versión.',
            archive:
                'El plan dejará de estar disponible para nuevas aplicaciones.',
            delete:
                'El borrador y sus vínculos se eliminarán definitivamente. Solo se permite si no tiene usos.',
        };

        const out = await Swal.fire({
            title:
                action === 'activate'
                    ? 'Activar plan'
                    : action === 'archive'
                      ? 'Archivar plan'
                      : 'Eliminar borrador',
            text: texts[action],
            icon: action === 'delete' ? 'warning' : 'question',
            showCancelButton: true,
            confirmButtonText:
                action === 'activate'
                    ? 'Activar'
                    : action === 'archive'
                      ? 'Archivar'
                      : 'Eliminar',
            cancelButtonText: 'Cancelar',
            ...theme(),
        });

        if (out.isConfirmed) {
            await perform(action);
        }
    }

    async function createPlan() {
        if (!newName.trim() || !newCycle || !newLevel) {
            setError(
                'Nombre, ciclo y nivel son obligatorios.',
            );
            return;
        }

        await perform('create', {
            name: newName.trim(),
            academic_cycle_id: Number(newCycle),
            academic_level_id: Number(newLevel),
            template_id: newTemplate || null,
            framework_code: newTemplate ? '' : null,
        });
    }

    async function apply() {
        if (!plan || !chosenGroups.length) return;

        const out = await Swal.fire({
            title: 'Aplicar plan a grupos',
            text:
                `Se asociará ${plan.name} con ` +
                `${chosenGroups.length} grupo(s). ` +
                'No se modificarán docentes ni calificaciones.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Aplicar',
            ...theme(),
        });

        if (out.isConfirmed) {
            await perform('apply', {
                group_ids: chosenGroups,
            });
        }
    }

    async function manageSubject(
        subject: Subject,
        action: 'subject_update' | 'subject_disable',
    ) {
        if (dirty) {
            setError(
                'Guarda o descarta el borrador antes de editar el catálogo.',
            );
            return;
        }

        if (action === 'subject_disable') {
            const result = await Swal.fire({
                title: 'Desactivar materia',
                text:
                    `¿Desactivar ${subject.name}? ` +
                    'Solo se permite si no está vinculada a ningún plan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Desactivar',
                ...theme(),
            });

            if (result.isConfirmed) {
                await perform('subject_disable', {
                    subject_id: subject.id,
                });
            }

            return;
        }

        const name = await Swal.fire({
            title: 'Editar nombre de materia',
            input: 'text',
            inputValue: subject.name,
            showCancelButton: true,
            inputValidator: value =>
                value?.trim()
                    ? undefined
                    : 'Nombre requerido',
            ...theme(),
        });

        if (!name.isConfirmed) return;

        const code = await Swal.fire({
            title: 'Editar código técnico',
            input: 'text',
            inputValue: subject.code,
            showCancelButton: true,
            inputValidator: value =>
                /^[a-z0-9][a-z0-9_-]*$/.test(value ?? '')
                    ? undefined
                    : 'Solo minúsculas, números, guiones y guiones bajos',
            ...theme(),
        });

        if (!code.isConfirmed) return;

        await perform('subject_update', {
            subject_id: subject.id,
            subject_name: String(name.value),
            subject_code: String(code.value),
        });
    }

    const filteredPlans = data.plans.filter(
        p =>
            (
                p.name + ' ' +
                p.level_name + ' ' +
                p.cycle_name
            ).toLowerCase().includes(filter.toLowerCase()),
    );

    return (
        <div className="space-y-4 text-slate-900 dark:text-slate-100">
            <header className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p className="text-xs font-bold uppercase tracking-widest text-blue-500">
                        Academic / Organización
                    </p>
                    <h1 className="mt-1 text-2xl font-bold">
                        Gestor de planes y programas
                    </h1>
                    <p className="mt-1 text-sm text-slate-500">
                        Diseña por grado o semestre, cierra versiones
                        y aplica planes a tus grupos.
                    </p>
                </div>

                <div className="flex flex-wrap gap-2">
                    <button
                        className={neutral}
                        onClick={() => void load()}
                        disabled={busy || loading}
                    >
                        <RefreshCw size={16} />
                        Actualizar
                    </button>

                    <button
                        className={primary}
                        onClick={() => {
                            setShowCreate(true);
                            setNewCycle(String(
                                data.cycles.find(c => c.is_active)?.id ?? '',
                            ));
                            setNewLevel('');
                            setNewTemplate('');
                            setNewName('');
                        }}
                    >
                        <Plus size={16} />
                        Nuevo plan
                    </button>
                </div>
            </header>

            {error && (
                <p
                    role="alert"
                    className="rounded-lg border border-red-800 bg-red-950 p-3 text-sm text-red-200"
                >
                    {error}
                </p>
            )}

            {notice && (
                <p
                    role="status"
                    className="rounded-lg border border-emerald-800 bg-emerald-950 p-3 text-sm text-emerald-200"
                >
                    {notice}
                </p>
            )}

            <div className="grid gap-4 lg:grid-cols-[270px_minmax(0,1fr)]">
                {/* LISTADO DE PLANES */}

                <aside className={`${panel} p-3`}>
                    <div className="mb-3 flex items-center justify-between">
                        <strong>Planes de la escuela</strong>
                        <span className="text-xs text-slate-500">
                            {filteredPlans.length}
                        </span>
                    </div>

                    <input
                        className={field}
                        value={filter}
                        onChange={e => setFilter(e.target.value)}
                        placeholder="Buscar plan..."
                        aria-label="Buscar plan"
                    />

                    <div className="mt-3 max-h-[72vh] space-y-1 overflow-y-auto">
                        {filteredPlans.map(p => (
                            <button
                                key={p.id}
                                type="button"
                                className={
                                    'w-full rounded-lg border p-3 text-left ' +
                                    (
                                        p.id === selectedId
                                            ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/40'
                                            : 'border-transparent hover:bg-slate-100 dark:hover:bg-slate-800'
                                    )
                                }
                                onClick={() => void choosePlan(p.id)}
                            >
                                <div className="text-sm font-semibold">
                                    {p.name}
                                </div>

                                <div className="mt-1 text-xs text-slate-500">
                                    {p.level_name} · {p.cycle_name}
                                    {' · v'}{p.version}
                                </div>

                                <div className="mt-2 text-xs">
                                    <span
                                        className={
                                            p.status === 'active'
                                                ? 'text-emerald-600 dark:text-emerald-400'
                                                : p.status === 'draft'
                                                  ? 'text-amber-600'
                                                  : 'text-slate-400'
                                        }
                                    >
                                        {p.status === 'active'
                                            ? 'Activo'
                                            : p.status === 'draft'
                                              ? 'Borrador'
                                              : 'Archivado'}
                                    </span>
                                </div>
                            </button>
                        ))}

                        {!filteredPlans.length && (
                            <p className="p-3 text-sm text-slate-500">
                                Sin planes.
                            </p>
                        )}
                    </div>
                </aside>

                {/* ADMINISTRADOR DEL PLAN */}

                <main className={`${panel} min-w-0 p-4`}>
                    {!plan ? (
                        <div className="p-8 text-center text-sm text-slate-500">
                            Selecciona un plan o crea uno desde una plantilla.
                        </div>
                    ) : (
                        <>
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p className="text-xs text-slate-500">
                                        {plan.level_name} · {plan.cycle_name}
                                        {' · versión '}{plan.version}
                                    </p>

                                    <h2 className="mt-1 text-xl font-bold">
                                        {plan.name}
                                    </h2>

                                    <p className="mt-1 text-xs text-slate-500">
                                        {editor.links.length} vínculos curriculares
                                        {' · '}{usage} asignación(es) docente(s)
                                    </p>
                                </div>

                                <div className="flex flex-wrap gap-2">
                                    <button
                                        className={neutral}
                                        disabled={busy}
                                        onClick={() => void confirmAction('clone')}
                                    >
                                        <Copy size={15} />
                                        Nueva versión
                                    </button>

                                    {isDraft && (
                                        <button
                                            className={primary}
                                            disabled={!dirty || busy}
                                            onClick={() => void save()}
                                        >
                                            <Save size={15} />
                                            Guardar cambios
                                        </button>
                                    )}

                                    {isDraft && (
                                        <button
                                            className={neutral}
                                            disabled={busy || dirty}
                                            onClick={() =>
                                                void confirmAction('activate')
                                            }
                                        >
                                            <CheckCircle2 size={15} />
                                            Activar
                                        </button>
                                    )}

                                    {(isDraft || plan.status === 'active') && (
                                        <button
                                            className={neutral}
                                            disabled={busy}
                                            onClick={() =>
                                                void confirmAction('archive')
                                            }
                                        >
                                            <Archive size={15} />
                                            Archivar
                                        </button>
                                    )}

                                    {isDraft && (
                                        <button
                                            className="rounded-lg border border-red-700 px-3 py-2 text-sm text-red-500"
                                            disabled={busy}
                                            onClick={() =>
                                                void confirmAction('delete')
                                            }
                                        >
                                            <Trash2 size={15} />
                                            Eliminar
                                        </button>
                                    )}
                                </div>
                            </div>

                            {dirty && (
                                <p className="mt-3 rounded-lg bg-amber-50 p-2 text-sm text-amber-800 dark:bg-amber-950 dark:text-amber-200">
                                    Hay cambios sin guardar. Todas las altas,
                                    bajas y ajustes se guardarán juntos.
                                </p>
                            )}

                            {!isDraft && (
                                <p className="mt-3 rounded-lg border border-slate-300 p-2 text-xs dark:border-slate-700">
                                    Plan cerrado: crea una nueva versión para
                                    modificar materias. Las asignaciones actuales
                                    permanecen intactas.
                                </p>
                            )}

                            {/* PESTAÑAS INTERNAS */}

                            <div className="my-4 flex flex-wrap gap-2 border-b border-slate-200 pb-3 dark:border-slate-800">
                                <button
                                    className={view === 'curriculum' ? primary : neutral}
                                    onClick={() => setView('curriculum')}
                                >
                                    <FilePlus2 size={15} />
                                    Materias por grado
                                </button>

                                <button
                                    className={view === 'groups' ? primary : neutral}
                                    onClick={() => setView('groups')}
                                >
                                    <Users size={15} />
                                    Aplicar a grupos
                                </button>

                                <button
                                    className={view === 'subjects' ? primary : neutral}
                                    onClick={() => setView('subjects')}
                                >
                                    Catálogo de materias
                                </button>

                                <button
                                    className={view === 'library' ? primary : neutral}
                                    onClick={() => setView('library')}
                                >
                                    <Library size={15} />
                                    Biblioteca SEP
                                </button>
                            </div>

                            {/* EDITOR CURRICULAR */}

                            {view === 'curriculum' && (
                                <>
                                    <div className="grid gap-3 md:grid-cols-2">
                                        <label className="text-xs font-semibold">
                                            Nombre del plan
                                            <input
                                                className={`${field} mt-1`}
                                                disabled={!isDraft}
                                                value={editor.name}
                                                onChange={e =>
                                                    mutate(previous => ({
                                                        ...previous,
                                                        name: e.target.value,
                                                    }))
                                                }
                                            />
                                        </label>

                                        <label className="text-xs font-semibold">
                                            Clave del marco curricular
                                            <input
                                                className={`${field} mt-1`}
                                                disabled={!isDraft}
                                                value={editor.framework_code}
                                                onChange={e =>
                                                    mutate(previous => ({
                                                        ...previous,
                                                        framework_code: e.target.value,
                                                    }))
                                                }
                                                placeholder="NEM_2022 / MCCEMS_2026"
                                            />
                                        </label>
                                    </div>

                                    <div className="mt-4 flex flex-wrap gap-2">
                                        {grades.map(g => (
                                            <button
                                                key={g}
                                                className={grade === g ? primary : neutral}
                                                onClick={() => {
                                                    setGrade(g);
                                                    setSubjectId('');
                                                }}
                                            >
                                                {g.includes('semestre')
                                                    ? g
                                                    : `Grado ${g}`}
                                            </button>
                                        ))}

                                        <button
                                            className={grade === null ? primary : neutral}
                                            onClick={() => {
                                                setGrade(null);
                                                setSubjectId('');
                                            }}
                                        >
                                            Todos los grados
                                        </button>
                                    </div>

                                    <div className="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(260px,.8fr)]">
                                        {/* MATERIAS DEL GRADO */}

                                        <div className="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                                            <h3 className="font-semibold">
                                                Materias{' '}
                                                {grade === null
                                                    ? 'generales'
                                                    : `del ${
                                                        grade.includes('semestre')
                                                            ? grade
                                                            : 'grado ' + grade
                                                    }`}
                                            </h3>

                                            <p className="mt-1 text-xs text-slate-500">
                                                {visible.length} vínculos específicos.
                                                Usa las flechas para ordenar;
                                                los cambios se guardan juntos.
                                            </p>

                                            <div className="mt-3 space-y-2">
                                                {visible.map(link => (
                                                    <div
                                                        key={`${link.id ?? 'new'}-${link.index}`}
                                                        className="rounded-lg border border-slate-200 p-3 dark:border-slate-700"
                                                    >
                                                        <div className="flex items-center justify-between gap-2">
                                                            <strong className="text-sm">
                                                                {link.subject_name}
                                                            </strong>

                                                            <div className="flex gap-1">
                                                                <button
                                                                    type="button"
                                                                    aria-label="Subir"
                                                                    className={neutral}
                                                                    disabled={
                                                                        !isDraft ||
                                                                        locked.has(link.id ?? -1)
                                                                    }
                                                                    onClick={() =>
                                                                        reorder(link.index, -1)
                                                                    }
                                                                >
                                                                    <ArrowUp size={14} />
                                                                </button>

                                                                <button
                                                                    type="button"
                                                                    aria-label="Bajar"
                                                                    className={neutral}
                                                                    disabled={
                                                                        !isDraft ||
                                                                        locked.has(link.id ?? -1)
                                                                    }
                                                                    onClick={() =>
                                                                        reorder(link.index, 1)
                                                                    }
                                                                >
                                                                    <ArrowDown size={14} />
                                                                </button>

                                                                <button
                                                                    type="button"
                                                                    aria-label="Quitar materia"
                                                                    className="rounded-lg border border-red-700 px-2 text-red-500 disabled:opacity-40"
                                                                    disabled={
                                                                        !isDraft ||
                                                                        locked.has(link.id ?? -1)
                                                                    }
                                                                    onClick={() =>
                                                                        removeLink(link.index)
                                                                    }
                                                                >
                                                                    <X size={15} />
                                                                </button>
                                                            </div>
                                                        </div>

                                                        <div className="mt-2 grid gap-2 sm:grid-cols-2">
                                                            <label className="text-xs">
                                                                <span className="mr-1">
                                                                    Ámbito
                                                                </span>

                                                                <select
                                                                    className={field}
                                                                    disabled={
                                                                        !isDraft ||
                                                                        locked.has(link.id ?? -1)
                                                                    }
                                                                    value={link.grade_label ?? ''}
                                                                    onChange={e =>
                                                                        changeLink(link.index, {
                                                                            grade_label:
                                                                                e.target.value || null,
                                                                        })
                                                                    }
                                                                >
                                                                    <option value="">
                                                                        Todos los grados
                                                                    </option>

                                                                    {grades.map(g => (
                                                                        <option key={g} value={g}>
                                                                            {g}
                                                                        </option>
                                                                    ))}
                                                                </select>
                                                            </label>

                                                            <div className="flex flex-col justify-center gap-1">
                                                                <label className="text-xs">
                                                                    <input
                                                                        type="checkbox"
                                                                        disabled={
                                                                            !isDraft ||
                                                                            locked.has(link.id ?? -1)
                                                                        }
                                                                        checked={!!link.is_official}
                                                                        onChange={e =>
                                                                            changeLink(link.index, {
                                                                                is_official:
                                                                                    e.target.checked,
                                                                            })
                                                                        }
                                                                    />
                                                                    {' '}Referencia oficial
                                                                </label>

                                                                <label className="text-xs">
                                                                    <input
                                                                        type="checkbox"
                                                                        disabled={
                                                                            !isDraft ||
                                                                            locked.has(link.id ?? -1)
                                                                        }
                                                                        checked={!!link.affects_average}
                                                                        onChange={e =>
                                                                            changeLink(link.index, {
                                                                                affects_average:
                                                                                    e.target.checked,
                                                                            })
                                                                        }
                                                                    />
                                                                    {' '}Participa en promedio
                                                                </label>
                                                            </div>
                                                        </div>

                                                        {locked.has(link.id ?? -1) && (
                                                            <p className="mt-2 text-xs text-amber-500">
                                                                Con asignaciones existentes:
                                                                modifica mediante una nueva versión.
                                                            </p>
                                                        )}
                                                    </div>
                                                ))}

                                                {visible.length === 0 && (
                                                    <p className="py-6 text-center text-sm text-slate-500">
                                                        Este grado todavía no tiene materias específicas.
                                                    </p>
                                                )}
                                            </div>

                                            {grade !== null && general.length > 0 && (
                                                <div className="mt-3 text-xs text-slate-500">
                                                    También aplican las generales:{' '}
                                                    {general.map(
                                                        link => link.subject_name,
                                                    ).join(', ')}
                                                </div>
                                            )}
                                        </div>

                                        {/* AGREGAR MATERIA */}

                                        <div className="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                                            <h3 className="font-semibold">
                                                Añadir materia
                                            </h3>

                                            <p className="mt-1 text-xs text-slate-500">
                                                Catálogo institucional; las materias
                                                no se duplican entre grupos.
                                            </p>

                                            <select
                                                className={`${field} mt-3`}
                                                value={subjectId}
                                                disabled={!isDraft}
                                                onChange={e =>
                                                    setSubjectId(e.target.value)
                                                }
                                            >
                                                <option value="">
                                                    Seleccionar materia...
                                                </option>

                                                {available.map(subject => (
                                                    <option
                                                        key={subject.id}
                                                        value={subject.id}
                                                    >
                                                        {subject.name}
                                                    </option>
                                                ))}
                                            </select>

                                            <button
                                                className={`${primary} mt-2`}
                                                disabled={!isDraft || !subjectId}
                                                onClick={() => {
                                                    const subject = data.subjects.find(
                                                        x => x.id === Number(subjectId),
                                                    );

                                                    if (subject) {
                                                        appendSubject(
                                                            subject.id,
                                                            subject.name,
                                                        );
                                                    }
                                                }}
                                            >
                                                <Plus size={15} />
                                                Añadir al borrador
                                            </button>

                                            <div className="mt-5 border-t border-slate-200 pt-3 dark:border-slate-700">
                                                <h4 className="text-sm font-semibold">
                                                    Nueva materia de la escuela
                                                </h4>

                                                <input
                                                    className={`${field} mt-2`}
                                                    placeholder="Nombre de la materia"
                                                    disabled={!isDraft}
                                                    value={newSubjectName}
                                                    onChange={e =>
                                                        setNewSubjectName(e.target.value)
                                                    }
                                                />

                                                <input
                                                    className={`${field} mt-2`}
                                                    placeholder="Código técnico, ej. educacion_financiera"
                                                    disabled={!isDraft}
                                                    value={newSubjectCode}
                                                    onChange={e =>
                                                        setNewSubjectCode(e.target.value)
                                                    }
                                                />

                                                <button
                                                    className={`${neutral} mt-2`}
                                                    disabled={
                                                        !isDraft ||
                                                        busy ||
                                                        !newSubjectName.trim() ||
                                                        !newSubjectCode.trim()
                                                    }
                                                    onClick={() =>
                                                        void createSubject()
                                                    }
                                                >
                                                    Crear y añadir
                                                </button>
                                            </div>

                                            <p className="mt-3 text-xs text-slate-500">
                                                Los campos formativos SEP pueden ser
                                                agrupadores, no necesariamente materias
                                                con promedio. Verifica estas marcas
                                                antes de activar.
                                            </p>
                                        </div>
                                    </div>
                                </>
                            )}

                            {/* PLAN APLICADO A GRUPOS */}

                            {view === 'groups' && (
                                <div className="space-y-3">
                                    <p className="text-sm text-slate-500">
                                        El plan se vinculará persistentemente
                                        con los grupos seleccionados.
                                        No sustituye asignaciones ni calificaciones.
                                    </p>

                                    {plan.status !== 'active' && (
                                        <p className="text-sm text-amber-500">
                                            Activa el plan antes de aplicarlo
                                            a un grupo.
                                        </p>
                                    )}

                                    <div className="grid gap-2 md:grid-cols-2">
                                        {compatibleGroups.map(group => {
                                            const current = data.bindings.find(
                                                x => x.school_group_id === group.id,
                                            )?.curriculum_plan_id;

                                            const coverage = editor.links.some(
                                                link =>
                                                    link.grade_label === null ||
                                                    link.grade_label === group.grade_label,
                                            );

                                            return (
                                                <label
                                                    key={group.id}
                                                    className="flex items-center gap-3 rounded-lg border border-slate-200 p-3 text-sm dark:border-slate-700"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        disabled={
                                                            plan.status !== 'active' ||
                                                            !coverage
                                                        }
                                                        checked={chosenGroups.includes(group.id)}
                                                        onChange={e =>
                                                            setChosenGroups(previous =>
                                                                e.target.checked
                                                                    ? [...previous, group.id]
                                                                    : previous.filter(
                                                                        id => id !== group.id,
                                                                    ),
                                                            )
                                                        }
                                                    />

                                                    <span className="flex-1">
                                                        <strong>{group.name}</strong>
                                                        <span className="ml-2 text-xs text-slate-500">
                                                            {group.grade_label ?? 'Sin grado'}
                                                        </span>

                                                        <span className="block text-xs text-slate-500">
                                                            {current === plan.id
                                                                ? 'Ya utiliza este plan'
                                                                : current
                                                                  ? `Utiliza plan #${current}`
                                                                  : 'Sin plan aplicado'}

                                                            {!coverage
                                                                ? ' · Sin materias para su grado'
                                                                : ''}
                                                        </span>
                                                    </span>
                                                </label>
                                            );
                                        })}

                                        {compatibleGroups.length === 0 && (
                                            <p className="text-sm text-slate-500">
                                                Aún no hay grupos compatibles;
                                                créalos desde SchoolCore.
                                            </p>
                                        )}
                                    </div>

                                    <button
                                        className={primary}
                                        disabled={
                                            busy ||
                                            plan.status !== 'active' ||
                                            !chosenGroups.length
                                        }
                                        onClick={() => void apply()}
                                    >
                                        <Users size={16} />
                                        Aplicar a {chosenGroups.length} grupo(s)
                                    </button>
                                </div>
                            )}

                            {/* CATÁLOGO DE MATERIAS */}

                            {view === 'subjects' && (
                                <div className="space-y-3">
                                    <p className="text-sm text-slate-500">
                                        Las materias pertenecen a la escuela
                                        y pueden utilizarse en varios planes.
                                        Las utilizadas en asignaciones
                                        no se pueden renombrar ni desactivar.
                                    </p>

                                    <div className="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                                        <h3 className="font-semibold">
                                            Nueva materia institucional
                                        </h3>

                                        <div className="mt-2 grid gap-2 sm:grid-cols-[1fr_1fr_auto]">
                                            <input
                                                className={field}
                                                value={newSubjectName}
                                                onChange={e =>
                                                    setNewSubjectName(e.target.value)
                                                }
                                                placeholder="Nombre"
                                            />

                                            <input
                                                className={field}
                                                value={newSubjectCode}
                                                onChange={e =>
                                                    setNewSubjectCode(e.target.value)
                                                }
                                                placeholder="codigo_tecnico"
                                            />

                                            <button
                                                className={primary}
                                                disabled={
                                                    busy ||
                                                    !newSubjectName.trim() ||
                                                    !newSubjectCode.trim()
                                                }
                                                onClick={() =>
                                                    void createSubject()
                                                }
                                            >
                                                Crear
                                            </button>
                                        </div>
                                    </div>

                                    <div className="max-h-96 divide-y divide-slate-200 overflow-auto rounded-lg border border-slate-200 dark:divide-slate-800 dark:border-slate-700">
                                        {data.subjects.map(subject => (
                                            <div
                                                key={subject.id}
                                                className="flex flex-wrap items-center justify-between gap-2 px-3 py-2"
                                            >
                                                <div>
                                                    <strong className="text-sm">
                                                        {subject.name}
                                                    </strong>
                                                    <p className="text-xs text-slate-500">
                                                        {subject.code} · {subject.kind}
                                                    </p>
                                                </div>

                                                <div className="flex gap-2">
                                                    <button
                                                        className={neutral}
                                                        disabled={busy}
                                                        onClick={() =>
                                                            void manageSubject(
                                                                subject,
                                                                'subject_update',
                                                            )
                                                        }
                                                    >
                                                        Editar
                                                    </button>

                                                    <button
                                                        className="rounded-lg border border-red-700 px-3 py-2 text-xs text-red-500"
                                                        disabled={busy}
                                                        onClick={() =>
                                                            void manageSubject(
                                                                subject,
                                                                'subject_disable',
                                                            )
                                                        }
                                                    >
                                                        Desactivar
                                                    </button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* BIBLIOTECA SEP */}

                            {view === 'library' && (
                                <div className="space-y-3">
                                    <p className="text-sm text-slate-500">
                                        Plantillas de referencia compartidas
                                        para todas las escuelas. Para utilizarlas,
                                        crea un plan nuevo desde una plantilla.
                                    </p>

                                    {data.templates.map(template => (
                                        <div
                                            key={template.id}
                                            className="rounded-lg border border-slate-200 p-3 dark:border-slate-700"
                                        >
                                            <strong>{template.name}</strong>

                                            <p className="mt-1 text-xs text-slate-500">
                                                {template.note}
                                            </p>

                                            <a
                                                className="mt-2 block text-xs text-blue-500 underline"
                                                href={template.source}
                                                target="_blank"
                                                rel="noreferrer"
                                            >
                                                Fuente oficial
                                            </a>

                                            <button
                                                className={`${neutral} mt-2`}
                                                onClick={() => {
                                                    setNewTemplate(template.id);

                                                    setNewName(
                                                        template.name +
                                                        ' · ' +
                                                        (
                                                            data.cycles.find(
                                                                cycle => cycle.is_active,
                                                            )?.name ?? ''
                                                        ),
                                                    );

                                                    setNewCycle(String(
                                                        data.cycles.find(
                                                            cycle => cycle.is_active,
                                                        )?.id ?? '',
                                                    ));

                                                    setNewLevel(String(
                                                        data.levels.find(
                                                            level =>
                                                                level.name.toLowerCase()
                                                                    .includes(template.level),
                                                        )?.id ?? '',
                                                    ));

                                                    setShowCreate(true);
                                                }}
                                            >
                                                Usar plantilla
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </>
                    )}
                </main>
            </div>

            {/* CREAR PLAN */}

            {showCreate && (
                <div
                    className="fixed inset-0 z-[95] flex items-center justify-center bg-slate-950/70 p-4"
                    onMouseDown={e => {
                        if (
                            e.target === e.currentTarget &&
                            !busy
                        ) {
                            setShowCreate(false);
                        }
                    }}
                >
                    <div
                        role="dialog"
                        aria-modal="true"
                        aria-label="Crear plan curricular"
                        className="w-full max-w-lg space-y-3 rounded-xl border border-slate-700 bg-white p-5 dark:bg-slate-900"
                    >
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-bold">
                                Crear plan curricular
                            </h2>

                            <button
                                className={neutral}
                                onClick={() => setShowCreate(false)}
                                disabled={busy}
                            >
                                <X size={16} />
                            </button>
                        </div>

                        <label className="block text-sm">
                            Nombre
                            <input
                                className={`${field} mt-1`}
                                value={newName}
                                onChange={e =>
                                    setNewName(e.target.value)
                                }
                                maxLength={160}
                                placeholder="Plan institucional 2026–2027"
                            />
                        </label>

                        <label className="block text-sm">
                            Fuente inicial
                            <select
                                className={`${field} mt-1`}
                                value={newTemplate}
                                onChange={e => {
                                    setNewTemplate(e.target.value);

                                    const template = data.templates.find(
                                        x => x.id === e.target.value,
                                    );

                                    if (template) {
                                        setNewName(
                                            template.name +
                                            ' · ' +
                                            (
                                                data.cycles.find(
                                                    cycle => cycle.is_active,
                                                )?.name ?? ''
                                            ),
                                        );

                                        setNewLevel(String(
                                            data.levels.find(
                                                level =>
                                                    level.name.toLowerCase()
                                                        .includes(template.level),
                                            )?.id ?? '',
                                        ));
                                    }
                                }}
                            >
                                <option value="">
                                    Plan personalizado vacío
                                </option>

                                {data.templates.map(template => (
                                    <option
                                        key={template.id}
                                        value={template.id}
                                    >
                                        {template.name}
                                    </option>
                                ))}
                            </select>
                        </label>

                        <div className="grid gap-3 sm:grid-cols-2">
                            <label className="text-sm">
                                Ciclo
                                <select
                                    className={`${field} mt-1`}
                                    value={newCycle}
                                    onChange={e =>
                                        setNewCycle(e.target.value)
                                    }
                                >
                                    {data.cycles.map(cycle => (
                                        <option
                                            key={cycle.id}
                                            value={cycle.id}
                                        >
                                            {cycle.name}
                                        </option>
                                    ))}
                                </select>
                            </label>

                            <label className="text-sm">
                                Nivel
                                <select
                                    className={`${field} mt-1`}
                                    value={newLevel}
                                    onChange={e =>
                                        setNewLevel(e.target.value)
                                    }
                                >
                                    <option value="">
                                        Seleccionar nivel
                                    </option>

                                    {data.levels.map(level => (
                                        <option
                                            key={level.id}
                                            value={level.id}
                                        >
                                            {level.name}
                                        </option>
                                    ))}
                                </select>
                            </label>
                        </div>

                        <p className="text-xs text-slate-500">
                            La copia de referencia será independiente
                            de la biblioteca. Podrás personalizarla
                            antes de activarla.
                        </p>

                        <div className="flex justify-end gap-2">
                            <button
                                className={neutral}
                                disabled={busy}
                                onClick={() => setShowCreate(false)}
                            >
                                Cancelar
                            </button>

                            <button
                                className={primary}
                                disabled={
                                    busy ||
                                    !newName.trim() ||
                                    !newCycle ||
                                    !newLevel
                                }
                                onClick={() => void createPlan()}
                            >
                                <Plus size={15} />
                                Crear borrador
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
