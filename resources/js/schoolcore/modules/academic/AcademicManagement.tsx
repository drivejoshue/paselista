import { useCallback, useEffect, useState } from 'react';
import {
    BookOpen,
    CalendarDays,
    GraduationCap,
    LoaderCircle,
    Plus,
    RefreshCw,
} from 'lucide-react';

import { Button } from '@/components/ui/button';

type Teacher = {
    id: number;
    user_id: number;
    name: string;
    employee_code: string | null;
    status: string;
};

type Subject = {
    id: number;
    name: string;
    code: string;
    kind: string;
    status: string;
};

type Period = {
    id: number;
    name: string;
    sequence: number;
    starts_on: string | null;
    ends_on: string | null;
    status: string;
};

type AcademicData = {
    cycle: {
        id: number;
        name: string;
    } | null;
    teachers: Teacher[];
    subjects: Subject[];
    periods: Period[];
};

type Tab = 'teachers' | 'subjects' | 'periods';

const fieldClass =
    'w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm ' +
    'outline-none focus:border-blue-500 dark:border-slate-700 ' +
    'dark:bg-slate-900 dark:text-slate-100';

async function api<T>(
    path: string,
    method: 'GET' | 'POST' = 'GET',
    payload?: object
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
        ...(payload ? { body: JSON.stringify(payload) } : {}),
    });

    const result: {
        message?: string;
        errors?: Record<string, string[]>;
    } = await response.json().catch(() => ({}));

    if (!response.ok) {
        const errors = result.errors
            ? Object.values(result.errors).flat()
            : [];

        throw new Error(
            errors.join(' ') ||
            result.message ||
            `Error HTTP ${response.status}`
        );
    }

    return result as T;
}

export default function AcademicManagement() {
    const [tab, setTab] = useState<Tab>('teachers');
    const [data, setData] = useState<AcademicData | null>(null);

    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    const [userId, setUserId] = useState('');
    const [employeeCode, setEmployeeCode] = useState('');

    const [subjectName, setSubjectName] = useState('');
    const [subjectCode, setSubjectCode] = useState('');
    const [subjectKind, setSubjectKind] = useState('custom');

    const [periodName, setPeriodName] = useState('');
    const [periodSequence, setPeriodSequence] = useState('1');
    const [periodStart, setPeriodStart] = useState('');
    const [periodEnd, setPeriodEnd] = useState('');

    const load = useCallback(async () => {
        setLoading(true);

        try {
            const result = await api<AcademicData>('/data');

            setData(result);
            setError(null);
        } catch (e) {
            setError(
                e instanceof Error
                    ? e.message
                    : 'No se pudo cargar Academic.'
            );
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        void load();
    }, [load]);

    async function save(
        action: () => Promise<unknown>,
        message: string
    ): Promise<void> {
        setSaving(true);
        setError(null);
        setSuccess(null);

        try {
            await action();
            await load();

            setSuccess(message);
        } catch (e) {
            setError(
                e instanceof Error
                    ? e.message
                    : 'No se pudo guardar.'
            );
        } finally {
            setSaving(false);
        }
    }

    const tabs: {
        id: Tab;
        label: string;
        icon: typeof GraduationCap;
    }[] = [
        {
            id: 'teachers',
            label: 'Docentes',
            icon: GraduationCap,
        },
        {
            id: 'subjects',
            label: 'Materias',
            icon: BookOpen,
        },
        {
            id: 'periods',
            label: 'Períodos',
            icon: CalendarDays,
        },
    ];

    return (
        <section className="space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 className="text-xl font-semibold">
                        Administración académica
                    </h2>

                    <p className="mt-1 text-sm text-slate-500">
                        Docentes, materias y períodos de evaluación
                    </p>
                </div>

                <Button
                    type="button"
                    variant="outline"
                    disabled={loading}
                    onClick={() => void load()}
                >
                    <RefreshCw size={16} />
                    Actualizar
                </Button>
            </div>

            <div className="flex gap-2 overflow-x-auto border-b border-slate-200 pb-3 dark:border-slate-800">
                {tabs.map(({ id, label, icon: Icon }) => (
                    <Button
                        key={id}
                        type="button"
                        variant={tab === id ? 'default' : 'outline'}
                        onClick={() => setTab(id)}
                    >
                        <Icon size={16} />
                        {label}
                    </Button>
                ))}
            </div>

            {error && (
                <div
                    role="alert"
                    className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300"
                >
                    {error}
                </div>
            )}

            {success && (
                <div
                    role="status"
                    className="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300"
                >
                    {success}
                </div>
            )}

            {loading ? (
                <div className="flex items-center gap-2 py-8 text-sm text-slate-500">
                    <LoaderCircle
                        size={18}
                        className="animate-spin"
                    />
                    Cargando información...
                </div>
            ) : (
                <>
                    {tab === 'teachers' && (
                        <div className="space-y-4">
                            <form
                                className="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900 md:grid-cols-[1fr_1fr_auto]"
                                onSubmit={(e) => {
                                    e.preventDefault();

                                    void save(
                                        () => api('/teachers', 'POST', {
                                            user_id: Number(userId),
                                            employee_code:
                                                employeeCode.trim() || null,
                                        }),
                                        'Docente registrado correctamente.'
                                    );
                                }}
                            >
                                <div>
                                    <label className="mb-1 block text-xs font-medium">
                                        ID del usuario docente
                                    </label>

                                    <input
                                        type="number"
                                        min="1"
                                        required
                                        className={fieldClass}
                                        value={userId}
                                        onChange={(e) =>
                                            setUserId(e.target.value)
                                        }
                                    />
                                </div>

                                <div>
                                    <label className="mb-1 block text-xs font-medium">
                                        Clave de empleado
                                    </label>

                                    <input
                                        className={fieldClass}
                                        maxLength={60}
                                        value={employeeCode}
                                        onChange={(e) =>
                                            setEmployeeCode(e.target.value)
                                        }
                                    />
                                </div>

                                <div className="flex items-end">
                                    <Button
                                        type="submit"
                                        disabled={saving}
                                    >
                                        <Plus size={16} />
                                        Registrar
                                    </Button>
                                </div>
                            </form>

                            <DataTable
                                headers={['Docente', 'Clave', 'Estado']}
                                rows={(data?.teachers ?? []).map((teacher) => [
                                    teacher.name,
                                    teacher.employee_code ?? '—',
                                    teacher.status === 'active'
                                        ? 'Activo'
                                        : 'Inactivo',
                                ])}
                                empty="No hay docentes registrados."
                            />
                        </div>
                    )}

                    {tab === 'subjects' && (
                        <div className="space-y-4">
                            <form
                                className="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900 md:grid-cols-[1fr_150px_170px_auto]"
                                onSubmit={(e) => {
                                    e.preventDefault();

                                    void save(
                                        () => api('/subjects', 'POST', {
                                            name: subjectName.trim(),
                                            code: subjectCode.trim(),
                                            kind: subjectKind,
                                        }),
                                        'Materia registrada correctamente.'
                                    );
                                }}
                            >
                                <div>
                                    <label className="mb-1 block text-xs font-medium">
                                        Nombre de la materia
                                    </label>

                                    <input
                                        required
                                        className={fieldClass}
                                        maxLength={160}
                                        value={subjectName}
                                        onChange={(e) =>
                                            setSubjectName(e.target.value)
                                        }
                                    />
                                </div>

                                <div>
                                    <label className="mb-1 block text-xs font-medium">
                                        Código
                                    </label>

                                    <input
                                        required
                                        className={fieldClass}
                                        maxLength={80}
                                        value={subjectCode}
                                        onChange={(e) =>
                                            setSubjectCode(e.target.value)
                                        }
                                    />
                                </div>

                                <div>
                                    <label className="mb-1 block text-xs font-medium">
                                        Clasificación
                                    </label>

                                    <select
                                        className={fieldClass}
                                        value={subjectKind}
                                        onChange={(e) =>
                                            setSubjectKind(e.target.value)
                                        }
                                    >
                                        <option value="custom">
                                            Complementaria
                                        </option>
                                        <option value="official">
                                            Oficial
                                        </option>
                                    </select>
                                </div>

                                <div className="flex items-end">
                                    <Button
                                        type="submit"
                                        disabled={saving}
                                    >
                                        <Plus size={16} />
                                        Agregar
                                    </Button>
                                </div>
                            </form>

                            <DataTable
                                headers={['Materia', 'Código', 'Tipo', 'Estado']}
                                rows={(data?.subjects ?? []).map((subject) => [
                                    subject.name,
                                    subject.code,
                                    subject.kind === 'official'
                                        ? 'Oficial'
                                        : 'Complementaria',
                                    subject.status === 'active'
                                        ? 'Activa'
                                        : 'Inactiva',
                                ])}
                                empty="No existen materias registradas."
                            />
                        </div>
                    )}

                    {tab === 'periods' && (
                        <div className="space-y-4">
                            <form
                                className="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900 md:grid-cols-2 xl:grid-cols-[1fr_90px_150px_150px_auto]"
                                onSubmit={(e) => {
                                    e.preventDefault();

                                    void save(
                                        () => api('/periods', 'POST', {
                                            name: periodName.trim(),
                                            sequence: Number(periodSequence),
                                            starts_on: periodStart || null,
                                            ends_on: periodEnd || null,
                                        }),
                                        'Período creado correctamente.'
                                    );
                                }}
                            >
                                <div>
                                    <label className="mb-1 block text-xs font-medium">
                                        Nombre del período
                                    </label>

                                    <input
                                        required
                                        className={fieldClass}
                                        maxLength={100}
                                        value={periodName}
                                        onChange={(e) =>
                                            setPeriodName(e.target.value)
                                        }
                                    />
                                </div>

                                <div>
                                    <label className="mb-1 block text-xs font-medium">
                                        Orden
                                    </label>

                                    <input
                                        required
                                        type="number"
                                        min="1"
                                        className={fieldClass}
                                        value={periodSequence}
                                        onChange={(e) =>
                                            setPeriodSequence(e.target.value)
                                        }
                                    />
                                </div>

                                <div>
                                    <label className="mb-1 block text-xs font-medium">
                                        Inicio
                                    </label>

                                    <input
                                        type="date"
                                        className={fieldClass}
                                        value={periodStart}
                                        onChange={(e) =>
                                            setPeriodStart(e.target.value)
                                        }
                                    />
                                </div>

                                <div>
                                    <label className="mb-1 block text-xs font-medium">
                                        Fin
                                    </label>

                                    <input
                                        type="date"
                                        className={fieldClass}
                                        value={periodEnd}
                                        onChange={(e) =>
                                            setPeriodEnd(e.target.value)
                                        }
                                    />
                                </div>

                                <div className="flex items-end">
                                    <Button
                                        type="submit"
                                        disabled={saving || !data?.cycle}
                                    >
                                        <Plus size={16} />
                                        Crear
                                    </Button>
                                </div>
                            </form>

                            <DataTable
                                headers={['Orden', 'Período', 'Inicio', 'Fin', 'Estado']}
                                rows={(data?.periods ?? []).map((period) => [
                                    String(period.sequence),
                                    period.name,
                                    period.starts_on ?? '—',
                                    period.ends_on ?? '—',
                                    period.status,
                                ])}
                                empty="No hay períodos para el ciclo actual."
                            />
                        </div>
                    )}
                </>
            )}
        </section>
    );
}

function DataTable({
    headers,
    rows,
    empty,
}: {
    headers: string[];
    rows: string[][];
    empty: string;
}) {
    return (
        <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <table className="w-full text-left text-sm">
                <thead className="bg-slate-50 text-xs text-slate-500 dark:bg-slate-800">
                    <tr>
                        {headers.map((header) => (
                            <th
                                key={header}
                                className="whitespace-nowrap px-4 py-3 font-medium"
                            >
                                {header}
                            </th>
                        ))}
                    </tr>
                </thead>

                <tbody>
                    {rows.length === 0 ? (
                        <tr>
                            <td
                                colSpan={headers.length}
                                className="px-4 py-10 text-center text-slate-500"
                            >
                                {empty}
                            </td>
                        </tr>
                    ) : (
                        rows.map((row, rowIndex) => (
                            <tr
                                key={rowIndex}
                                className="border-t border-slate-100 dark:border-slate-800"
                            >
                                {row.map((cell, cellIndex) => (
                                    <td
                                        key={cellIndex}
                                        className="px-4 py-3"
                                    >
                                        {cell}
                                    </td>
                                ))}
                            </tr>
                        ))
                    )}
                </tbody>
            </table>
        </div>
    );
}
