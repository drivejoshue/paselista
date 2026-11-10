import Swal from 'sweetalert2';
import AcademicGradePortalAdmin from './AcademicGradePortalAdmin';
import {
    useCallback,
    useEffect,
    useMemo,
    useState,
    type FormEvent,
} from 'react';

import {
    ArrowLeft,
    BookOpenCheck,
    CheckCircle2,
    ClipboardCheck,
    LockKeyhole,
    Plus,
    RefreshCw,
    Search,
    ShieldCheck,
    FileDown,
    X,
} from 'lucide-react';

type GradeStatus = 'draft' | 'submitted' | 'published';

type Progress = {
    total: number;
    recorded: number;
    pending: number;
    invalid: number;
    low: number;
    complete: boolean;
};

type Gradebook = Progress & {
    id: number;
    status: GradeStatus;
    group_name: string;
    subject_name: string;
    period_name: string;
    teacher_name: string | null;
    submitted_at: string | null;
    published_at: string | null;
};

type Level = {
    id: number;
    name: string;
};

type Period = {
    id: number;
    name: string;
    academic_level_id: number | null;
    sequence: number;
    status: string;
};

type IndexData = {
    cycle: { id: number; name: string } | null;
    levels: Level[];
    periods: Period[];
    books: Gradebook[];
    bulk: {
        submitted: number;
        eligible: number;
        can_publish: boolean;
    };
};

type StudentGrade = {
    enrollment_id: number;
    student_code: string;
    student_name: string;
    grade_id: number | null;
    numeric_value: string | null;
};

type AuditEntry = {
    id: number;
    student_grade_id: number;
    actor_name: string;
    before: { numeric_value?: string | number } | null;
    after: { numeric_value?: string | number } | null;
    reason: string;
    created_at: string;
};

type ReviewData = {
    book: {
        id: number;
        status: GradeStatus;
        group_name: string;
        subject_name: string;
        period_name: string;
        submitted_at: string | null;
        published_at: string | null;
    };
    progress: Progress;
    students: StudentGrade[];
    history: AuditEntry[];
};

const ROOT = '/admin/schoolcore/academic/grades';
const EXPORT_ROOT =
    '/admin/schoolcore/academic/grades/export';

const panel =
    'rounded-xl border border-slate-200 bg-white ' +
    'dark:border-slate-800 dark:bg-slate-900';

const button =
    'inline-flex items-center justify-center gap-2 rounded-lg ' +
    'border border-slate-300 bg-white px-4 py-2 text-sm font-medium ' +
    'hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 ' +
    'dark:border-slate-700 dark:bg-slate-900 dark:hover:bg-slate-800';

const primary =
    'inline-flex items-center justify-center gap-2 rounded-lg ' +
    'bg-blue-600 px-4 py-2 text-sm font-semibold text-white ' +
    'hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-40';

const field =
    'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 ' +
    'text-sm text-slate-900 outline-none focus:border-blue-500 ' +
    'dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100';

const labels: Record<GradeStatus, string> = {
    draft: 'Borrador',
    submitted: 'Enviada a Dirección',
    published: 'Publicada',
};

function errorMessage(value: unknown): string {
    if (!value || typeof value !== 'object') {
        return 'No se pudo completar la operación.';
    }

    const result = value as {
        message?: string;
        errors?: Record<string, string[]>;
    };

    return (
        Object.values(result.errors ?? {}).flat().join(' ') ||
        result.message ||
        'No se pudo completar la operación.'
    );
}

async function api<T>(
    url: string,
    payload?: Record<string, unknown>,
): Promise<T> {
    const csrf = document.querySelector<HTMLMetaElement>(
        'meta[name="csrf-token"]',
    )?.content;

    if (payload !== undefined && !csrf) {
        throw new Error('No se encontró el token CSRF.');
    }

    const response = await fetch(url, {
        method: payload === undefined ? 'GET' : 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            ...(payload !== undefined
                ? {
                      'Content-Type': 'application/json',
                      'X-CSRF-TOKEN': csrf!,
                  }
                : {}),
        },
        ...(payload !== undefined
            ? { body: JSON.stringify(payload) }
            : {}),
    });

    const result: unknown = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(errorMessage(result));
    }

    return result as T;
}

function validGrade(value: string): boolean {
    return /^(10(\.0)?|[0-9](\.[0-9])?)$/.test(value.trim());
}

function formatGrade(value: string | number | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return 'Pendiente';
    }

    const number = Number(value);

    if (
        !Number.isFinite(number) ||
        number < 0 ||
        number > 10 ||
        Math.abs(number * 10 - Math.round(number * 10)) > 0.00001
    ) {
        return `${value} (inválida)`;
    }

    return number.toFixed(1);
}

function StatusBadge({ status }: { status: GradeStatus }) {
    const colors: Record<GradeStatus, string> = {
        draft: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
        submitted: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
        published: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
    };

    return (
        <span className={`rounded-full px-3 py-1 text-xs font-semibold ${colors[status]}`}>
            {labels[status]}
        </span>
    );
}

function ProgressCards({ progress }: { progress: Progress }) {
    const items = [
        ['Alumnos', progress.total],
        ['Capturadas', progress.recorded],
        ['Pendientes', progress.pending],
        ['Menores a 6', progress.low],
        ['Inválidas', progress.invalid],
    ] as const;

    return (
        <div className="grid grid-cols-2 gap-3 md:grid-cols-5">
            {items.map(([label, value]) => (
                <div key={label} className={`${panel} p-4`}>
                    <p className="text-xs text-slate-500">{label}</p>
                    <p className={`mt-2 text-2xl font-bold tabular-nums ${
                        label === 'Menores a 6' && value > 0
                            ? 'text-red-600 dark:text-red-400'
                            : label === 'Pendientes' && value > 0
                              ? 'text-amber-600'
                              : ''
                    }`}>
                        {value}
                    </p>
                </div>
            ))}
        </div>
    );
}

function swalTheme() {
    const dark = document.documentElement.classList.contains('dark');

    return {
        background: dark ? '#0f172a' : '#ffffff',
        color: dark ? '#f8fafc' : '#0f172a',
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#64748b',
    };
}

export default function AcademicGrades() {
    const [data, setData] = useState<IndexData | null>(null);
    const [review, setReview] = useState<ReviewData | null>(null);

    const [selectedBook, setSelectedBook] = useState<number | null>(() => {
        const value = new URLSearchParams(window.location.search).get('book');
        return value && /^\d+$/.test(value) ? Number(value) : null;
    });

    const [loading, setLoading] = useState(true);
    const [busy, setBusy] = useState(false);
    const [exportingPdf, setExportingPdf] = useState(false);
    const [error, setError] = useState('');
    const [message, setMessage] = useState('');

    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] =
        useState<'all' | GradeStatus>('all');

    const [showPeriodForm, setShowPeriodForm] = useState(false);
    const [periodLevel, setPeriodLevel] = useState('');
    const [periodName, setPeriodName] = useState('');
    const [periodSequence, setPeriodSequence] = useState('1');

    const [correcting, setCorrecting] = useState<StudentGrade | null>(null);
    const [newGrade, setNewGrade] = useState('');
    const [reason, setReason] = useState('');
    const [correctionMode, setCorrectionMode] = useState(false);


type GradesTab = 'validation' | 'published' | 'portal';

const [tab, setTab] = useState<GradesTab>(() => {
    const value = new URLSearchParams(
        window.location.search
    ).get('tab');

    return value === 'published' || value === 'portal'
        ? value
        : 'validation';
});


const [groupFilter, setGroupFilter] = useState('all');
const [subjectFilter, setSubjectFilter] = useState('all');
const [teacherFilter, setTeacherFilter] = useState('all');

    const loadIndex = useCallback(async () => {
        setLoading(true);

        try {
            const result = await api<IndexData>(`${ROOT}/data`);
            setData(result);
            setError('');
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Error de consulta.');
        } finally {
            setLoading(false);
        }
    }, []);

    const loadReview = useCallback(async (id: number) => {
        try {
            const result = await api<ReviewData>(`${ROOT}/data/${id}`);
            setReview(result);
            setError('');
        } catch (e) {
            setReview(null);
            setError(e instanceof Error ? e.message : 'Error al revisar.');
        }
    }, []);

    useEffect(() => {
        void loadIndex();
    }, [loadIndex]);

    useEffect(() => {
        if (selectedBook !== null) {
            setReview(null);
            void loadReview(selectedBook);
        } else {
            setReview(null);
        }
    }, [selectedBook, loadReview]);

    const openBook = (id: number | null) => {
        setSelectedBook(id);
        setError('');
        setMessage('');
        setCorrecting(null);
        setCorrectionMode(false);

        const url = new URL(window.location.href);
        url.searchParams.set('academic', 'grades');

        if (id === null) {
            url.searchParams.delete('book');
        } else {
            url.searchParams.set('book', String(id));
        }

        window.history.replaceState({}, '', url);
    };


    function selectTab(next: GradesTab) {
    setTab(next);
    setSelectedBook(null);
    setError('');
    setMessage('');
    setCorrecting(null);
    setSearch('');

    const url = new URL(window.location.href);

    url.searchParams.set('academic', 'grades');
    url.searchParams.set('tab', next);
    url.searchParams.delete('book');

    window.history.replaceState({}, '', url);
}


const filteredBooks = useMemo(() => {
    const query = search.trim().toLocaleLowerCase('es-MX');

    return (data?.books ?? []).filter((book) => {
        if (tab === 'published') {
            if (book.status !== 'published') return false;

            if (
                groupFilter !== 'all' &&
                book.group_name !== groupFilter
            ) return false;

            if (
                subjectFilter !== 'all' &&
                book.subject_name !== subjectFilter
            ) return false;

            if (
                teacherFilter !== 'all' &&
                (book.teacher_name ?? '') !== teacherFilter
            ) return false;
        } else {
            // Los libros publicados tienen su propio apartado.
            if (book.status === 'published') return false;

            if (
                statusFilter !== 'all' &&
                book.status !== statusFilter
            ) return false;
        }

        return [
            book.group_name,
            book.subject_name,
            book.period_name,
            book.teacher_name ?? '',
        ].some((value) =>
            value.toLocaleLowerCase('es-MX').includes(query)
        );
    });
}, [
    data,
    tab,
    search,
    statusFilter,
    groupFilter,
    subjectFilter,
    teacherFilter,
]);

async function downloadVisiblePdf(): Promise<void> {
    if (exportingPdf || filteredBooks.length === 0) {
        return;
    }

    setExportingPdf(true);
    setError('');

    try {
        const csrf =
            document.querySelector<HTMLMetaElement>(
                'meta[name="csrf-token"]'
            )?.content;

        if (!csrf) {
            throw new Error(
                'No se encontró el token CSRF.'
            );
        }

        const response = await fetch(
            `${EXPORT_ROOT}/summary`,
            {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    Accept:
                        'application/pdf, application/json',
                },
                body: JSON.stringify({
                    book_ids: filteredBooks.map(
                        book => book.id
                    ),
                }),
            }
        );

        if (!response.ok) {
            const payload = await response.json()
                .catch(() => null) as {
                    message?: string;
                } | null;

            throw new Error(
                payload?.message ??
                `Error al exportar: HTTP ${response.status}`
            );
        }

        const blob = await response.blob();
        const url = URL.createObjectURL(blob);

        const anchor = document.createElement('a');

        anchor.href = url;
        anchor.download = 'capturas-academic.pdf';

        document.body.appendChild(anchor);
        anchor.click();
        anchor.remove();

        window.setTimeout(() => {
            URL.revokeObjectURL(url);
        }, 30000);

    } catch (cause) {
        setError(
            cause instanceof Error
                ? cause.message
                : 'No se pudo generar el PDF.'
        );
    } finally {
        setExportingPdf(false);
    }
}


    async function execute(
        url: string,
        payload: Record<string, unknown> = {},
    ): Promise<boolean> {
        setBusy(true);
        setError('');
        setMessage('');

        try {
            const result = await api<{ message: string }>(url, payload);

            await loadIndex();

            if (selectedBook !== null) {
                await loadReview(selectedBook);
            }

            setMessage(result.message || 'Operación completada.');
            return true;
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Error al guardar.');
            return false;
        } finally {
            setBusy(false);
        }
    }



async function publishBook(id: number) {
    if (busy) return;

    const book = data?.books.find((item) => item.id === id);

    if (
        !book ||
        book.status !== 'submitted' ||
        !book.complete
    ) return;

    const result = await Swal.fire({
        title: 'Validar y publicar',
        text: `${book.subject_name} · ${book.group_name}. Se publicarán ${book.recorded} calificaciones.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Validar y publicar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        ...swalTheme(),
    });

    if (!result.isConfirmed) return;

    const success = await execute(`${ROOT}/${id}/publish`);

    if (!success) return;

    // Verificar el estado real del servidor.
    try {
        const updated = await api<ReviewData>(`${ROOT}/data/${id}`);

        if (updated.book.status !== 'published') {
            setError('El servidor no confirmó la publicación.');
            return;
        }
    } catch {
        setError('No se pudo verificar la publicación. Actualiza la consulta.');
        return;
    }

    // Salir de la revisión y abrir el apartado de publicadas.
    selectTab('published');

    await Swal.fire({
        icon: 'success',
        title: 'Calificaciones publicadas',
        text: 'La captura fue validada y publicada correctamente.',
        timer: 2000,
        showConfirmButton: false,
        ...swalTheme(),
    });
}



async function publishAll() {
    if (!data?.bulk.can_publish || busy) return;

    const submittedIds = data.books
        .filter((book) => book.status === 'submitted')
        .map((book) => book.id);

    const result = await Swal.fire({
        title: 'Publicación masiva',
        text: `Se publicarán ${submittedIds.length} capturas enviadas y completas. Si alguna falla, no debe publicarse ninguna.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Publicar todas',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        ...swalTheme(),
    });

    if (!result.isConfirmed) return;

    const success = await execute(`${ROOT}/publish-all`);

    if (!success) return;

    try {
        const updated = await api<IndexData>(`${ROOT}/data`);

        const allPublished = submittedIds.every((id) =>
            updated.books.some(
                (book) => book.id === id && book.status === 'published'
            )
        );

        if (!allPublished) {
            setError('No se confirmó la publicación de todas las capturas.');
            return;
        }

        setData(updated);
    } catch {
        setError('No se pudo verificar la publicación masiva.');
        return;
    }

    selectTab('published');

    await Swal.fire({
        title: 'Publicación completada',
        text: `${submittedIds.length} capturas publicadas correctamente.`,
        icon: 'success',
        timer: 2200,
        showConfirmButton: false,
        ...swalTheme(),
    });
}


    async function createPeriod(e: FormEvent<HTMLFormElement>) {
        e.preventDefault();

        if (!data?.cycle) return;

        const success = await execute(`${ROOT}/periods`, {
            academic_cycle_id: data.cycle.id,
            academic_level_id: Number(periodLevel),
            name: periodName.trim(),
            sequence: Number(periodSequence),
        });

        if (success) {
            setShowPeriodForm(false);
            setPeriodName('');
            setPeriodLevel('');
            setPeriodSequence('1');
        }
    }

    function beginCorrection(student: StudentGrade) {
        setCorrecting(student);
        setNewGrade(formatGrade(student.numeric_value));
        setReason('');
    }

    async function saveCorrection(e: FormEvent<HTMLFormElement>) {
        e.preventDefault();

        if (
            !correcting?.grade_id ||
            selectedBook === null ||
            !validGrade(newGrade) ||
            reason.trim().length < 10
        ) {
            return;
        }

        const success = await execute(
            `${ROOT}/${selectedBook}/${correcting.grade_id}/correct`,
            {
                numeric_value: newGrade.trim(),
                reason: reason.trim(),
            },
        );

        if (success) setCorrecting(null);
    }

    const summary = {
        drafts: data?.books.filter((b) => b.status === 'draft').length ?? 0,
        submitted: data?.books.filter((b) => b.status === 'submitted').length ?? 0,
        published: data?.books.filter((b) => b.status === 'published').length ?? 0,
    };

const publishedBooks = (data?.books ?? []).filter(
    (book) => book.status === 'published'
);

const publishedGroups = [...new Set(
    publishedBooks.map((book) => book.group_name)
)].sort();

const publishedSubjects = [...new Set(
    publishedBooks.map((book) => book.subject_name)
)].sort();

const publishedTeachers = [...new Set(
    publishedBooks.map((book) => book.teacher_name ?? '')
)].filter(Boolean).sort();

    const canPublishSelected =
        review?.book.status === 'submitted' &&
        review.progress.complete;

    return (
        <section className="min-w-0 space-y-5 text-slate-900 dark:text-slate-100">

            {/* Encabezado */}

            <header className="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p className="text-xs font-semibold uppercase tracking-widest text-blue-500">
                        Academic / Evaluaciones
                    </p>

                    <h1 className="mt-1 text-2xl font-bold">
                        Calificaciones y validación
                    </h1>

                    <p className="mt-1 text-sm text-slate-500">
                        Captura docente, revisión y publicación.
                        {data?.cycle && ` Ciclo ${data.cycle.name}.`}
                    </p>
                </div>


<div className="flex flex-wrap items-center gap-2">
    {selectedBook === null && tab !== 'portal' && (
        <button
            type="button"
            className={button}
            disabled={
                busy ||
                loading ||
                exportingPdf ||
                filteredBooks.length === 0
            }
            onClick={() => void downloadVisiblePdf()}
        >
            <FileDown size={16} />

            {exportingPdf
                ? 'Generando PDF...'
                : `PDF de la lista (${filteredBooks.length})`}
        </button>
    )}

    <button
        type="button"
        className={button}
        disabled={busy || loading}
        onClick={() => {
            void loadIndex();

            if (selectedBook !== null) {
                void loadReview(selectedBook);
            }
        }}
    >
        <RefreshCw size={16} />
        Actualizar
    </button>
</div>

            </header>

            {error && (
                <div role="alert" className="rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300">
                    {error}
                </div>
            )}

            {message && (
                <div role="status" className="rounded-lg border border-emerald-300 bg-emerald-50 p-4 text-sm text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300">
                    {message}
                </div>
            )}

            {loading && !data && (
                <p className="py-8 text-sm text-slate-500">
                    Cargando calificaciones...
                </p>
            )}


{selectedBook === null && data && (
    <div className="flex flex-wrap gap-2 border-b border-slate-200 pb-3 dark:border-slate-800">
        <button
            type="button"
            onClick={() => selectTab('validation')}
            className={
                'rounded-lg px-4 py-2.5 text-sm font-semibold ' +
                (tab === 'validation'
                    ? 'bg-blue-600 text-white'
                    : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300')
            }
        >
            Capturas y validación
            {' · '}
            {summary.drafts + summary.submitted}
        </button>

        <button
            type="button"
            onClick={() => selectTab('published')}
            className={
                'rounded-lg px-4 py-2.5 text-sm font-semibold ' +
                (tab === 'published'
                    ? 'bg-blue-600 text-white'
                    : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300')
            }
        >
            Calificaciones publicadas
            {' · '}
            {summary.published}
        </button>


<button
    type="button"
    onClick={() => selectTab('portal')}
    className={
        'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold ' +
        (tab === 'portal'
            ? 'bg-blue-600 text-white'
            : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300')
    }
>
    <LockKeyhole size={16} />
    Accesos familiares
</button>

    </div>
)}



         {/* LISTADO DE DIRECCIÓN */}
{selectedBook === null && tab === 'portal' && (
    <AcademicGradePortalAdmin />
)}
{selectedBook === null && data && tab !== 'portal' && (
                <>
                    <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                        {[
                            ['Capturas', data.books.length, BookOpenCheck],
                            ['Borradores', summary.drafts, LockKeyhole],
                            ['Por validar', summary.submitted, ClipboardCheck],
                            ['Publicadas', summary.published, ShieldCheck],
                        ].map(([label, value, Icon]) => {
                            const IconComponent = Icon as typeof BookOpenCheck;

                            return (
                                <div key={String(label)} className={`${panel} p-4`}>
                                    <div className="flex items-center justify-between text-xs text-slate-500">
                                        {label}
                                        <IconComponent size={18} />
                                    </div>
                                    <p className="mt-2 text-2xl font-bold tabular-nums">
                                        {value as number}
                                    </p>
                                </div>
                            );
                        })}
                    </div>

                    {/* Periodos */}
{tab === 'validation' && (
                    <div className={`${panel} p-4`}>
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 className="font-semibold">Periodos de evaluación</h2>
                                <p className="mt-1 text-xs text-slate-500">
                                    Periodos habilitados para la captura docente.
                                </p>
                            </div>

                            <button
                                type="button"
                                className={button}
                                disabled={!data.cycle}
                                onClick={() => setShowPeriodForm(!showPeriodForm)}
                            >
                                <Plus size={16} />
                                Nuevo periodo
                            </button>
                        </div>

                        <div className="mt-3 flex flex-wrap gap-2">
                            {data.periods.map((period) => (
                                <span key={period.id} className="rounded-lg border border-slate-200 px-3 py-2 text-xs dark:border-slate-700">
                                    {period.name} · {
                                        data.levels.find((l) =>
                                            l.id === period.academic_level_id,
                                        )?.name ?? 'General'
                                    }
                                </span>
                            ))}
                        </div>

                        {showPeriodForm && (
                            <form onSubmit={(e) => void createPeriod(e)} className="mt-4 grid gap-3 border-t border-slate-200 pt-4 dark:border-slate-700 sm:grid-cols-4">
                                <select
                                    className={field}
                                    required
                                    value={periodLevel}
                                    onChange={(e) => setPeriodLevel(e.target.value)}
                                >
                                    <option value="">Selecciona nivel</option>
                                    {data.levels.map((level) => (
                                        <option key={level.id} value={level.id}>
                                            {level.name}
                                        </option>
                                    ))}
                                </select>

                                <input
                                    className={`${field} sm:col-span-2`}
                                    placeholder="Nombre del periodo"
                                    maxLength={100}
                                    required
                                    value={periodName}
                                    onChange={(e) => setPeriodName(e.target.value)}
                                />

                                <input
                                    className={field}
                                    type="number"
                                    min="1"
                                    max="20"
                                    required
                                    value={periodSequence}
                                    onChange={(e) => setPeriodSequence(e.target.value)}
                                />

                                <div className="sm:col-span-4">
                                    <button className={primary} disabled={busy}>
                                        Guardar periodo
                                    </button>
                                </div>
                            </form>
                        )}
                    </div>
                    )}

                    {/* Tabla de capturas */}

                    <div className={`${panel} overflow-hidden`}>
                        <div className="flex flex-wrap items-center justify-between gap-3 p-4">
                            <div>
                                <h2 className="font-semibold">
                                    Capturas por grupo y materia
                                </h2>
                                <p className="mt-1 text-xs text-slate-500">
                                    Solo se pueden publicar capturas enviadas y completas.
                                </p>
                            </div>

                            <button
                                type="button"
                                className={primary}
                                disabled={busy || !data.bulk.can_publish}
                                onClick={() => void publishAll()}
                            >
                                <ShieldCheck size={16} />
                                Validar todas ({data.bulk.submitted})
                            </button>
                        </div>

                        {data.bulk.submitted > 0 && !data.bulk.can_publish && (
                            <div className="mx-4 mb-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                                La validación masiva está bloqueada.
                                Hay {data.bulk.submitted - data.bulk.eligible} captura(s)
                                enviadas con notas incompletas o inválidas.
                            </div>
                        )}

                        <div className="flex flex-wrap gap-3 border-t border-slate-200 p-4 dark:border-slate-800">
                            <div className="relative min-w-48 flex-1">
                                <Search size={16} className="absolute left-3 top-3 text-slate-400" />
                                <input
                                    className={`${field} pl-9`}
                                    placeholder="Buscar materia, grupo o docente"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                />
                            </div>

                            <select
                                className={`${field} w-auto`}
                                value={statusFilter}
                                onChange={(e) =>
                                    setStatusFilter(
                                        e.target.value as 'all' | GradeStatus,
                                    )
                                }
                            >
                                <option value="all">Todos</option>
                                <option value="draft">Borradores</option>
                                <option value="submitted">Por validar</option>
                                <option value="published">Publicadas</option>
                            </select>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[800px] text-left text-sm">
                                <thead className="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800">
                                    <tr>
                                        <th className="px-4 py-3">Materia / Grupo</th>
                                        <th className="px-4 py-3">Docente / Periodo</th>
                                        <th className="px-4 py-3">Avance</th>
                                        <th className="px-4 py-3">Estado</th>
                                        <th className="px-4 py-3 text-right">Acción</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    {filteredBooks.map((book) => (
                                        <tr key={book.id} className="border-t border-slate-100 dark:border-slate-800">
                                            <td className="px-4 py-3">
                                                <strong>{book.subject_name}</strong>
                                                <p className="text-xs text-slate-500">
                                                    {book.group_name}
                                                </p>
                                            </td>

                                            <td className="px-4 py-3">
                                                {book.teacher_name ?? 'Sin docente'}
                                                <p className="text-xs text-slate-500">
                                                    {book.period_name}
                                                </p>
                                            </td>

                                            <td className="px-4 py-3">
                                                <strong>
                                                    {book.recorded}/{book.total}
                                                </strong>
                                                <p className="text-xs text-slate-500">
                                                    {book.pending} pendientes ·{' '}
                                                    <span className="text-red-600">
                                                        {book.low} menores a 6
                                                    </span>
                                                    {book.invalid > 0 &&
                                                        ` · ${book.invalid} inválidas`}
                                                </p>
                                            </td>

                                            <td className="px-4 py-3">
                                                <StatusBadge status={book.status} />
                                            </td>


<td className="px-4 py-3 text-right">
    <div className="flex flex-wrap items-center justify-end gap-2">
        {tab === 'validation' && book.status === 'draft' && (
            <span className="text-xs text-amber-500">
                Esperando envío docente
            </span>
        )}

        <button
            type="button"
            className={button}
            onClick={() => openBook(book.id)}
        >
            {tab === 'published'
                ? 'Ver calificaciones'
                : 'Revisar'}
        </button>


<a
    href={`${EXPORT_ROOT}/books/${book.id}`}
    target="_blank"
    rel="noopener noreferrer"
    className={button}
    title={`Exportar PDF de ${book.subject_name} - ${book.group_name}`}
>
    <FileDown size={16} />
    PDF
</a>


        {tab === 'validation' && book.status === 'submitted' && (
            <button
                type="button"
                className={primary}
                disabled={busy || !book.complete}
                title={
                    book.complete
                        ? 'Validar y publicar'
                        : 'La captura tiene notas pendientes o inválidas'
                }
                onClick={() => void publishBook(book.id)}
            >
                <ShieldCheck size={16} />
                Validar y publicar
            </button>
        )}
    </div>
</td>

                                        </tr>
                                    ))}

                                    {filteredBooks.length === 0 && (
                                        <tr>
                                            <td colSpan={5} className="p-8 text-center text-slate-500">
                                                No hay capturas para mostrar.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </>
            )}

            {/* REVISIÓN INDIVIDUAL */}

            {selectedBook !== null && (
                <>
                    <button
                        type="button"
                        className={button}
                        onClick={() => openBook(null)}
                    >
                        <ArrowLeft size={16} />
                        Volver a capturas
                    </button>

                    {!review && !error && (
                        <p className="text-sm text-slate-500">
                            Cargando revisión...
                        </p>
                    )}

                    {review && (
                        <>
                            <div className={`${panel} p-5`}>
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <h2 className="text-lg font-semibold">
                                            {review.book.subject_name} ·{' '}
                                            {review.book.group_name}
                                        </h2>
                                        <p className="mt-1 text-sm text-slate-500">
                                            {review.book.period_name}
                                        </p>
                                    </div>

                                    <StatusBadge status={review.book.status} />
                                </div>

                                <p className="mt-4 text-sm text-slate-500">
                                    {review.book.status === 'draft'
                                        ? 'El docente continúa capturando. Esta captura no puede publicarse.'
                                        : review.book.status === 'submitted'
                                          ? 'Captura enviada y bloqueada para el docente.'
                                          : 'Captura publicada. Solo Dirección puede corregir con motivo y auditoría.'}
                                </p>
                            </div>

                            <ProgressCards progress={review.progress} />


{review.book.status === 'published' && (
    <div className={`${panel} flex flex-wrap items-center justify-between gap-3 p-4`}>
        <div>
            <h3 className="text-sm font-semibold">
                Calificaciones publicadas
            </h3>
            <p className="mt-1 text-xs text-slate-500">
                La captura está cerrada. Las correcciones
                excepcionales requieren justificación
                y quedan registradas en auditoría.
            </p>
        </div>

        <button
            type="button"
            className={correctionMode ? button : primary}
            onClick={() => {
                setCorrecting(null);
                setCorrectionMode(!correctionMode);
            }}
        >
            {correctionMode
                ? 'Finalizar correcciones'
                : 'Habilitar corrección excepcional'}
        </button>
    </div>
)}


                            {review.book.status === 'submitted' && (
                                <div className={`${panel} flex flex-wrap items-center justify-between gap-3 p-4`}>
                                    <p className="text-sm text-slate-500">
                                        {canPublishSelected
                                            ? 'Todas las calificaciones están completas y son válidas.'
                                            : 'No se puede publicar mientras existan notas pendientes o inválidas.'}
                                    </p>

                                    <button
                                        type="button"
                                        className={primary}
                                        disabled={busy || !canPublishSelected}
                                        onClick={() => void publishBook(selectedBook)}
                                    >
                                        <CheckCircle2 size={16} />
                                        Validar y publicar
                                    </button>
                                </div>
                            )}

                            <div className={`${panel} overflow-hidden`}>
                                <div className="border-b border-slate-200 p-4 dark:border-slate-800">
                                    <h3 className="font-semibold">
                                        Calificaciones individuales
                                    </h3>
                                    <p className="mt-1 text-xs text-slate-500">
                                        Escala de 0.0 a 10.0.
                                    </p>
                                </div>

                                <div className="overflow-x-auto">
                                    <table className="w-full min-w-[560px] text-left text-sm">
                                        <thead className="bg-slate-50 text-xs text-slate-500 dark:bg-slate-800">
                                            <tr>
                                                <th className="px-4 py-3">Matrícula</th>
                                                <th className="px-4 py-3">Alumno</th>
                                                <th className="px-4 py-3">Calificación</th>
                                                <th className="px-4 py-3 text-right">Acción</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            {review.students.map((student) => {
                                                const value =
                                                    student.numeric_value === null
                                                        ? null
                                                        : Number(student.numeric_value);

                                                const invalid =
                                                    value !== null &&
                                                    (
                                                        !Number.isFinite(value) ||
                                                        value < 0 ||
                                                        value > 10 ||
                                                        Math.abs(value * 10 - Math.round(value * 10)) > 0.00001
                                                    );

                                                return (
                                                    <tr key={student.enrollment_id} className="border-t border-slate-100 dark:border-slate-800">
                                                        <td className="px-4 py-3 text-slate-500">
                                                            {student.student_code}
                                                        </td>

                                                        <td className="px-4 py-3 font-medium">
                                                            {student.student_name}
                                                        </td>

                                                        <td className="px-4 py-3">
                                                            {value === null ? (
                                                                <span className="text-amber-600">
                                                                    Pendiente
                                                                </span>
                                                            ) : (
                                                                <span className={
                                                                    `inline-flex rounded-md px-3 py-1 font-bold tabular-nums ${
                                                                        invalid || value < 6
                                                                            ? 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300'
                                                                            : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                                                    }`
                                                                }>
                                                                    {formatGrade(student.numeric_value)}
                                                                </span>
                                                            )}
                                                        </td>

                                                        <td className="px-4 py-3 text-right">
                                                            {review.book.status === 'published' &&
                                                                correctionMode &&
                                                                student.grade_id !== null && (
                                                                                    <button
                                                                        type="button"
                                                                        className={button}
                                                                        onClick={() => beginCorrection(student)}
                                                                    >
                                                                        Corregir
                                                                    </button>
                                                                )}
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {/* Auditoría administrativa */}

                            {review.book.status === 'published' && (
                                <div className={`${panel} p-4`}>
                                    <h3 className="font-semibold">
                                        Historial interno de correcciones
                                    </h3>
                                    <p className="mt-1 text-xs text-slate-500">
                                        Este historial no es visible para tutores.
                                    </p>

                                    <div className="mt-4 space-y-3">
                                        {review.history.length === 0 && (
                                            <p className="text-sm text-slate-500">
                                                No hay correcciones registradas.
                                            </p>
                                        )}

                                        {review.history.map((entry) => (
                                            <div key={entry.id} className="rounded-lg border border-slate-200 p-3 text-sm dark:border-slate-700">
                                                <p className="font-medium">
                                                    {review.students.find((student) =>
                                                        student.grade_id === entry.student_grade_id,
                                                    )?.student_name ?? 'Alumno'}
                                                </p>

                                                <p className="mt-1">
                                                    {formatGrade(entry.before?.numeric_value)}
                                                    {' → '}
                                                    {formatGrade(entry.after?.numeric_value)}
                                                </p>

                                                <p className="mt-1 text-xs text-slate-500">
                                                    {entry.actor_name} · {entry.created_at}
                                                </p>

                                                <p className="mt-2">
                                                    Motivo: {entry.reason}
                                                </p>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </>
                    )}
                </>
            )}

            {/* CORRECCIÓN AUDITADA */}

            {correcting && selectedBook !== null && (
                <div className="fixed inset-0 z-[80] flex items-center justify-center bg-black/60 p-4">
                    <form
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="correction-title"
                        onSubmit={(e) => void saveCorrection(e)}
                        className="w-full max-w-lg space-y-4 rounded-xl bg-white p-5 shadow-xl dark:bg-slate-900"
                    >
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <h3 id="correction-title" className="text-lg font-semibold">
                                    Corrección de calificación
                                </h3>
                                <p className="text-sm text-slate-500">
                                    {correcting.student_name}
                                </p>
                            </div>

                            <button
                                type="button"
                                aria-label="Cerrar"
                                disabled={busy}
                                onClick={() => setCorrecting(null)}
                            >
                                <X size={19} />
                            </button>
                        </div>

                        <p className="text-sm">
                            Calificación actual:{' '}
                            <strong>{formatGrade(correcting.numeric_value)}</strong>
                        </p>

                        <label className="block space-y-1 text-sm">
                            Nueva calificación
                            <input
                                className={field}
                                type="number"
                                min="0"
                                max="10"
                                step="0.1"
                                inputMode="decimal"
                                required
                                value={newGrade}
                                onChange={(e) => setNewGrade(e.target.value)}
                            />
                        </label>

                        <label className="block space-y-1 text-sm">
                            Motivo de la corrección
                            <textarea
                                className={field}
                                rows={3}
                                required
                                minLength={10}
                                maxLength={1000}
                                placeholder="Explica el motivo de la corrección"
                                value={reason}
                                onChange={(e) => setReason(e.target.value)}
                            />
                        </label>

                        <div className="flex justify-end gap-2">
                            <button
                                type="button"
                                className={button}
                                disabled={busy}
                                onClick={() => setCorrecting(null)}
                            >
                                Cancelar
                            </button>

                            <button
                                type="submit"
                                className={primary}
                                disabled={
                                    busy ||
                                    !validGrade(newGrade) ||
                                    reason.trim().length < 10 ||
                                    Number(newGrade) === Number(correcting.numeric_value)
                                }
                            >
                                Guardar corrección
                            </button>
                        </div>
                    </form>
                </div>
            )}
        </section>
    );
}
