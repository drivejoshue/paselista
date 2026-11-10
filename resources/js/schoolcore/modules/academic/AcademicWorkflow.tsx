import { useCallback, useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';

type Item = { id: number; name: string };

type Plan = Item & {
    status: string;
    academic_level_id: number;
    level_name: string;
};

type PlanSubject = {
    id: number;
    curriculum_plan_id: number;
    subject_name: string;
    grade_label: string | null;
    affects_average: boolean;
};

type Group = Item & {
    grade_label: string | null;
    academic_level_id: number;
};

type Assignment = {
    id: number;
    school_group_id: number;
    plan_subject_id: number;
    group_name: string;
    subject_name: string;
    teacher_name: string | null;
    status: string;
};

type Period = Item & {
    status: string;
    academic_level_id: number | null;
};

type Gradebook = {
    id: number;
    teaching_assignment_id: number;
    grading_period_id: number;
    period_name: string;
    status: string;
};

type Workflow = {
    cycle: Item;
    levels: Item[];
    groups: Group[];
    subjects: Item[];
    teachers: Item[];
    plans: Plan[];
    planSubjects: PlanSubject[];
    assignments: Assignment[];
    periods: Period[];
    gradebooks: Gradebook[];
};

type StudentGrade = {
    enrollment_id: number;
    student_code: string;
    first_name: string;
    last_name: string;
    numeric_value: string | null;
    text_value: string | null;
};

type Roster = {
    book: {
        id: number;
        status: string;
        period_name: string;
    };
    editable: boolean;
    students: StudentGrade[];
};

type Tab = 'plans' | 'assignments' | 'grades';

const field =
    'w-full rounded-lg border border-slate-200 bg-white p-2 text-sm ' +
    'dark:border-slate-700 dark:bg-slate-900';

async function api<T>(
    path: string,
    method = 'GET',
    body?: object
): Promise<T> {
    const csrf = document.querySelector<HTMLMetaElement>(
        'meta[name="csrf-token"]'
    )?.content;

    const response = await fetch(`/admin/academic${path}`, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
        },
        ...(body ? { body: JSON.stringify(body) } : {}),
    });

    const result = await response.json().catch(() => ({}));

    if (!response.ok) {
        const errors = Object.values(
            result.errors ?? {}
        ).flat();

        throw new Error(
            errors.length
                ? errors.join(' ')
                : result.message ?? `Error HTTP ${response.status}`
        );
    }

    return result as T;
}

export default function AcademicWorkflow() {
    const [data, setData] = useState<Workflow | null>(null);
    const [tab, setTab] = useState<Tab>('plans');
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');
    const [message, setMessage] = useState('');

    const [planName, setPlanName] = useState('');
    const [levelId, setLevelId] = useState('');
    const [planId, setPlanId] = useState('');
    const [subjectId, setSubjectId] = useState('');
    const [gradeLabel, setGradeLabel] = useState('');

    const [groupId, setGroupId] = useState('');
    const [planSubjectId, setPlanSubjectId] = useState('');
    const [teacherId, setTeacherId] = useState('');

    const [assignmentId, setAssignmentId] = useState('');
    const [periodId, setPeriodId] = useState('');
    const [bookId, setBookId] = useState('');
    const [roster, setRoster] = useState<Roster | null>(null);

    const load = useCallback(async () => {
        try {
            const result = await api<Workflow>('/workflow');
            setData(result);
            setError('');
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Error de consulta.');
        }
    }, []);

    useEffect(() => {
        void load();
    }, [load]);

    async function run(action: () => Promise<unknown>) {
        setBusy(true);
        setError('');
        setMessage('');

        try {
            await action();
            await load();
            setMessage('Operación guardada correctamente.');
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Error al guardar.');
        } finally {
            setBusy(false);
        }
    }

    async function openRoster(id: number) {
        setBusy(true);
        setError('');
        try {
            const result = await api<Roster>(
                `/gradebooks/${id}/roster`
            );
            setBookId(String(id));
            setRoster(result);
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Error al abrir libro.');
        } finally {
            setBusy(false);
        }
    }

    return (
        <section className="space-y-4">
            <div>
                <h2 className="text-xl font-semibold">
                    Planes y evaluación
                </h2>
                <p className="text-sm text-slate-500">
                    {data?.cycle.name ?? 'Consultando ciclo escolar'}
                </p>
            </div>

            <div className="flex flex-wrap gap-2">
                {([
                    ['plans', 'Planes de estudio'],
                    ['assignments', 'Asignaciones'],
                    ['grades', 'Calificaciones'],
                ] as const).map(([key, title]) => (
                    <Button
                        key={key}
                        type="button"
                        variant={tab === key ? 'default' : 'outline'}
                        onClick={() => setTab(key)}
                    >
                        {title}
                    </Button>
                ))}
            </div>

            {error && (
                <p role="alert" className="text-sm text-red-600">
                    {error}
                </p>
            )}
            {message && (
                <p role="status" className="text-sm text-emerald-600">
                    {message}
                </p>
            )}

            {tab === 'plans' && data && (
                <div className="space-y-4">
                    <form
                        className="grid gap-3 rounded-xl border p-4 md:grid-cols-3"
                        onSubmit={(e) => {
                            e.preventDefault();
                            void run(() =>
                                api('/plans', 'POST', {
                                    name: planName,
                                    academic_level_id: Number(levelId),
                                    framework_code: null,
                                })
                            );
                        }}
                    >
                        <input
                            className={field}
                            placeholder="Nombre del plan"
                            value={planName}
                            required
                            onChange={(e) => setPlanName(e.target.value)}
                        />
                        <select
                            className={field}
                            value={levelId}
                            required
                            onChange={(e) => setLevelId(e.target.value)}
                        >
                            <option value="">Nivel educativo</option>
                            {data.levels.map((level) => (
                                <option key={level.id} value={level.id}>
                                    {level.name}
                                </option>
                            ))}
                        </select>
                        <Button disabled={busy} type="submit">
                            Crear plan
                        </Button>
                    </form>

                    <div className="space-y-2">
                        {data.plans.map((plan) => (
                            <div
                                key={plan.id}
                                className="flex flex-wrap items-center justify-between gap-3 rounded-xl border p-3"
                            >
                                <span className="text-sm">
                                    {plan.name} · {plan.level_name} · {plan.status}
                                </span>
                                {plan.status === 'draft' && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={busy}
                                        onClick={() =>
                                            void run(() =>
                                                api(
                                                    `/plans/${plan.id}/activate`,
                                                    'PATCH'
                                                )
                                            )
                                        }
                                    >
                                        Activar plan
                                    </Button>
                                )}
                            </div>
                        ))}
                    </div>

                    <form
                        className="grid gap-3 rounded-xl border p-4 md:grid-cols-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            void run(() =>
                                api('/plan-subjects', 'POST', {
                                    curriculum_plan_id: Number(planId),
                                    subject_id: Number(subjectId),
                                    grade_label: gradeLabel,
                                    is_official: true,
                                    affects_average: true,
                                })
                            );
                        }}
                    >
                        <select
                            className={field}
                            required
                            value={planId}
                            onChange={(e) => setPlanId(e.target.value)}
                        >
                            <option value="">Plan en borrador</option>
                            {data.plans
                                .filter((p) => p.status === 'draft')
                                .map((plan) => (
                                    <option key={plan.id} value={plan.id}>
                                        {plan.name}
                                    </option>
                                ))}
                        </select>
                        <select
                            className={field}
                            required
                            value={subjectId}
                            onChange={(e) => setSubjectId(e.target.value)}
                        >
                            <option value="">Materia</option>
                            {data.subjects.map((subject) => (
                                <option key={subject.id} value={subject.id}>
                                    {subject.name}
                                </option>
                            ))}
                        </select>
                        <input
                            className={field}
                            required
                            placeholder="Grado: 1, 2, 3..."
                            value={gradeLabel}
                            onChange={(e) => setGradeLabel(e.target.value)}
                        />
                        <Button disabled={busy} type="submit">
                            Agregar materia
                        </Button>
                    </form>
                </div>
            )}

            {tab === 'assignments' && data && (
                <div className="space-y-4">
                    <form
                        className="grid gap-3 rounded-xl border p-4 md:grid-cols-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            void run(() =>
                                api('/assignments', 'POST', {
                                    school_group_id: Number(groupId),
                                    plan_subject_id: Number(planSubjectId),
                                    teacher_profile_id:
                                        teacherId ? Number(teacherId) : null,
                                })
                            );
                        }}
                    >
                        <select
                            className={field}
                            required
                            value={groupId}
                            onChange={(e) => setGroupId(e.target.value)}
                        >
                            <option value="">Grupo</option>
                            {data.groups.map((g) => (
                                <option key={g.id} value={g.id}>
                                    {g.name}
                                </option>
                            ))}
                        </select>
                        <select
                            className={field}
                            required
                            value={planSubjectId}
                            onChange={(e) => setPlanSubjectId(e.target.value)}
                        >
                            <option value="">Materia del plan</option>
                            {data.planSubjects.map((ps) => (
                                <option key={ps.id} value={ps.id}>
                                    {ps.subject_name} · {ps.grade_label}
                                </option>
                            ))}
                        </select>
                        <select
                            className={field}
                            value={teacherId}
                            onChange={(e) => setTeacherId(e.target.value)}
                        >
                            <option value="">Sin docente asignado</option>
                            {data.teachers.map((t) => (
                                <option key={t.id} value={t.id}>
                                    {t.name}
                                </option>
                            ))}
                        </select>
                        <Button disabled={busy} type="submit">
                            Asignar
                        </Button>
                    </form>

                    {data.assignments.map((a) => (
                        <div key={a.id} className="rounded-xl border p-3 text-sm">
                            {a.group_name} · {a.subject_name}
                            <span className="block text-slate-500">
                                {a.teacher_name ?? 'Docente pendiente'}
                            </span>
                        </div>
                    ))}
                </div>
            )}

            {tab === 'grades' && data && (
                <div className="space-y-4">
                    <form
                        className="grid gap-3 rounded-xl border p-4 md:grid-cols-3"
                        onSubmit={(e) => {
                            e.preventDefault();
                            void run(async () => {
                                const result = await api<{ id: number }>(
                                    '/gradebooks',
                                    'POST',
                                    {
                                        teaching_assignment_id:
                                            Number(assignmentId),
                                        grading_period_id: Number(periodId),
                                    }
                                );
                                await openRoster(result.id);
                            });
                        }}
                    >
                        <select
                            className={field}
                            value={assignmentId}
                            required
                            onChange={(e) => setAssignmentId(e.target.value)}
                        >
                            <option value="">Grupo y materia</option>
                            {data.assignments
                                .filter((a) => a.status === 'active')
                                .map((a) => (
                                    <option key={a.id} value={a.id}>
                                        {a.group_name} · {a.subject_name}
                                    </option>
                                ))}
                        </select>
                        <select
                            className={field}
                            value={periodId}
                            required
                            onChange={(e) => setPeriodId(e.target.value)}
                        >
                            <option value="">Período de evaluación</option>
                            {data.periods.map((p) => (
                                <option key={p.id} value={p.id}>
                                    {p.name}
                                </option>
                            ))}
                        </select>
                        <Button disabled={busy} type="submit">
                            Abrir libro
                        </Button>
                    </form>

                    <div className="flex flex-wrap gap-2">
                        {data.periods
                            .filter((p) => p.status === 'draft')
                            .map((p) => (
                                <Button
                                    key={p.id}
                                    type="button"
                                    variant="outline"
                                    disabled={busy}
                                    onClick={() =>
                                        void run(() =>
                                            api(
                                                `/periods/${p.id}/activate`,
                                                'PATCH'
                                            )
                                        )
                                    }
                                >
                                    Activar {p.name}
                                </Button>
                            ))}
                    </div>

                    {data.gradebooks.map((book) => (
                        <button
                            key={book.id}
                            type="button"
                            className="block w-full rounded-lg border p-3 text-left text-sm hover:bg-slate-50 dark:hover:bg-slate-800"
                            onClick={() => void openRoster(book.id)}
                        >
                            Libro #{book.id} · {book.period_name} · {book.status}
                        </button>
                    ))}

                    {roster && bookId && (
                        <div className="overflow-x-auto rounded-xl border">
                            <div className="border-b p-4">
                                <h3 className="font-semibold">
                                    {roster.book.period_name}
                                </h3>
                                <p className="text-xs text-slate-500">
                                    {roster.editable
                                        ? 'Captura habilitada'
                                        : 'Libro de solo lectura'}
                                </p>
                            </div>

                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left">
                                        <th className="p-3">Alumno</th>
                                        <th className="p-3">Calificación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {roster.students.map((student) => (
                                        <GradeRow
                                            key={`${bookId}-${student.enrollment_id}`}
                                            bookId={Number(bookId)}
                                            student={student}
                                            editable={roster.editable}
                                            onSaved={() =>
                                                openRoster(Number(bookId))
                                            }
                                        />
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            )}
        </section>
    );
}

function GradeRow({
    bookId,
    student,
    editable,
    onSaved,
}: {
    bookId: number;
    student: StudentGrade;
    editable: boolean;
    onSaved: () => Promise<void>;
}) {
    const [value, setValue] = useState(
        student.numeric_value ?? ''
    );
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    async function save() {
        setSaving(true);
        setError('');

        try {
            await api(
                `/gradebooks/${bookId}/grades/${student.enrollment_id}`,
                'PUT',
                {
                    numeric_value: value === '' ? null : Number(value),
                    text_value: null,
                    comment: null,
                }
            );

            await onSaved();
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Error.');
        } finally {
            setSaving(false);
        }
    }

    return (
        <tr className="border-b last:border-0">
            <td className="p-3">
                <div className="font-medium">
                    {student.first_name} {student.last_name}
                </div>
                <div className="text-xs text-slate-500">
                    {student.student_code}
                </div>
            </td>
            <td className="p-3">
                <div className="flex items-center gap-2">
                    <input
                        type="number"
                        step="0.001"
                        min="0"
                        max="9999.999"
                        className={`${field} max-w-32`}
                        value={value}
                        disabled={!editable || saving}
                        onChange={(e) => setValue(e.target.value)}
                    />
                    {editable && (
                        <Button
                            type="button"
                            size="sm"
                            disabled={saving || value === ''}
                            onClick={() => void save()}
                        >
                            Guardar
                        </Button>
                    )}
                </div>
                {error && (
                    <p role="alert" className="mt-1 text-xs text-red-600">
                        {error}
                    </p>
                )}
            </td>
        </tr>
    );
}
