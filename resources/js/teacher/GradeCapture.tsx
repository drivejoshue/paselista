import Swal from 'sweetalert2';
import { useMemo, useState, type FormEvent } from 'react';

import {
    ArrowLeft,
    CheckCircle2,
    FileDown,
    Lock,
    Minus,
    Plus,
    Save,
    Send,
} from 'lucide-react';

import type { TeacherBoot } from './TeacherApp';

const valid = (value: string): boolean =>
    /^(10(?:\.0)?|[0-9](?:\.[0-9])?)$/.test(value.trim());

const normalized = (value: string): string =>
    valid(value) ? String(Number(value)) : value.trim();

const frame =
    'rounded-xl border border-slate-200 bg-white p-5 ' +
    'dark:border-slate-800 dark:bg-slate-900';

const button =
    'inline-flex items-center justify-center gap-2 rounded-lg ' +
    'bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white ' +
    'hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-40';

const secondary =
    'inline-flex items-center justify-center gap-2 rounded-lg ' +
    'border border-slate-300 px-4 py-2.5 text-sm font-semibold ' +
    'hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800';

function alertTheme() {
    const dark = document.documentElement.classList.contains('dark');

    return {
        background: dark ? '#0f172a' : '#ffffff',
        color: dark ? '#f8fafc' : '#0f172a',
        confirmButtonColor: '#2563eb',
    };
}

function errorMessage(payload: unknown): string {
    if (!payload || typeof payload !== 'object') {
        return 'No se pudo guardar la captura.';
    }

    const response = payload as {
        message?: string;
        errors?: Record<string, string[]>;
    };

    return (
        Object.values(response.errors ?? {}).flat().join(' ') ||
        response.message ||
        'No se pudo guardar la captura.'
    );
}

export default function GradeCapture({
    boot,
}: {
    boot: TeacherBoot;
}) {
    const a = boot.a;
    const p = boot.p;

    const students = boot.students ?? [];
    const status = boot.book?.status ?? 'draft';

    const decimals = boot.grade_input_decimals ?? true;
    const editable = status === 'draft';

    const url = a && p
        ? `/teacher/grades/${a.id}/${p.id}`
        : '';

    /*
     * Fotografía de las calificaciones originales.
     * Sirve para detectar qué cambió el docente.
     */
    const initial = useMemo<Record<string, string>>(
        () =>
            Object.fromEntries(
                students.map((student): [string, string] => {
                    const raw = student.numeric_value;

                    if (
                        raw === null ||
                        raw === undefined ||
                        raw === ''
                    ) {
                        return [
                            String(student.enrollment_id),
                            '',
                        ];
                    }

                    const number = Number(raw);

                    return [
                        String(student.enrollment_id),
                        decimals
                            ? number.toFixed(1)
                            : String(number),
                    ];
                }),
            ),
        [students, decimals],
    );

    const [values, setValues] =
        useState<Record<string, string>>(() => initial);

    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');

    /*
     * Únicamente las matrículas modificadas.
     * No enviamos todas las notas del formulario.
     */
    const dirtyKeys = useMemo(
        () =>
            Object.keys(initial).filter(
                (key) =>
                    normalized(values[key] ?? '') !==
                    normalized(initial[key] ?? ''),
            ),
        [initial, values],
    );

    const changed = dirtyKeys.length > 0;

    const pending = students.filter(
        (student) =>
            (
                values[String(student.enrollment_id)] ?? ''
            ).trim() === '',
    ).length;

    const invalid = students.filter((student) => {
        const value = (
            values[String(student.enrollment_id)] ?? ''
        ).trim();

        return value !== '' && !valid(value);
    }).length;

    const recorded =
        students.length - pending - invalid;

    const low = students.filter((student) => {
        const value =
            values[String(student.enrollment_id)] ?? '';

        return valid(value) && Number(value) < 6;
    }).length;

    const complete =
        students.length > 0 &&
        pending === 0 &&
        invalid === 0;

    /*
     * Edición manual.
     */
    function changeValue(key: string, input: string) {
        if (!editable || busy) return;

        const value = input.trim().replace(',', '.');

        const pattern = decimals
            ? /^(?:|(?:[0-9]|10)(?:\.[0-9]?)?)$/
            : /^(?:|[0-9]|10)$/;

        if (
            !pattern.test(value) ||
            (
                value !== '' &&
                Number(value) > 10
            )
        ) {
            return;
        }

        setValues((current) => ({
            ...current,
            [key]: value,
        }));
    }

    /*
     * Normalizar la nota cuando termina la edición.
     */
    function finishEditing(key: string) {
        if (busy) return;

        setValues((current) => {
            const raw = (current[key] ?? '').trim();

            if (raw === '') return current;

            const number = Number(raw);

            if (
                !Number.isFinite(number) ||
                number < 0 ||
                number > 10
            ) {
                return current;
            }

            return {
                ...current,
                [key]: decimals
                    ? number.toFixed(1)
                    : String(number),
            };
        });
    }

    /*
     * Ajuste mediante botones + y -.
     */
    function adjust(key: string, direction: -1 | 1) {
        if (!editable || busy) return;

        setValues((current) => {
            const raw = (current[key] ?? '').trim();

            if (raw === '') {
                return {
                    ...current,
                    [key]: direction > 0
                        ? (decimals ? '1.0' : '1')
                        : (decimals ? '0.0' : '0'),
                };
            }

            const number = Number(raw);

            if (!Number.isFinite(number)) {
                return current;
            }

            let next: number;

            if (decimals) {
                // Evita errores de precisión al sumar décimas.
                next = Math.max(
                    0,
                    Math.min(
                        100,
                        Math.round(number * 10) + direction,
                    ),
                ) / 10;
            } else {
                // Un valor anterior de 6.5 pasa a 7 o 6.
                next = direction > 0
                    ? Math.floor(number) + 1
                    : Math.ceil(number) - 1;

                next = Math.max(
                    0,
                    Math.min(10, next),
                );
            }

            return {
                ...current,
                [key]: decimals
                    ? next.toFixed(1)
                    : String(next),
            };
        });
    }

    /*
     * GUARDADO PARCIAL CON DETECCIÓN DE CONFLICTOS
     */
    async function saveGrades(
        e: FormEvent<HTMLFormElement>,
    ) {
        e.preventDefault();

        if (
            !editable ||
            !changed ||
            invalid > 0 ||
            busy ||
            !url
        ) {
            return;
        }

        const csrf =
            boot.csrf ||
            document.querySelector<HTMLMetaElement>(
                'meta[name="csrf-token"]',
            )?.content;

        if (!csrf) {
            setError(
                'No se encontró el token CSRF. Actualiza la página.',
            );
            return;
        }

        /*
         * Enviar solamente las notas modificadas.
         * Incluir el valor que se vio al abrir la captura.
         */
        const grades: Record<
            string,
            string | null
        > = {};

        const original_grades: Record<
            string,
            string | null
        > = {};

        for (const key of dirtyKeys) {
            const current =
                (values[key] ?? '').trim();

            const original =
                (initial[key] ?? '').trim();

            grades[key] = current === ''
                ? null
                : normalized(current);

            original_grades[key] = original === ''
                ? null
                : normalized(original);
        }

        setBusy(true);
        setError('');

        try {
            const response = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    grades,
                    original_grades,
                }),
            });

            const payload: unknown =
                await response.json().catch(() => null);

            /*
             * Otro docente modificó una nota que
             * nosotros intentamos guardar.
             */
            if (response.status === 409) {
                const result = await Swal.fire({
                    title: 'La captura cambió',
                    text: errorMessage(payload),
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Actualizar captura',
                    cancelButtonText: 'Conservar mi pantalla',
                    reverseButtons: true,
                    ...alertTheme(),
                });

                if (result.isConfirmed) {
                    window.location.reload();
                }

                return;
            }

            if (!response.ok) {
                throw new Error(errorMessage(payload));
            }

            await Swal.fire({
                title: 'Borrador guardado',
                text:
                    'Se actualizaron únicamente las notas modificadas.',
                icon: 'success',
                timer: 1100,
                showConfirmButton: false,
                ...alertTheme(),
            });

            // Recargar la fotografía oficial.
            window.location.reload();
        } catch (cause) {
            const message = cause instanceof Error
                ? cause.message
                : 'Error al guardar.';

            setError(message);

            void Swal.fire({
                title: 'No se pudo guardar',
                text: message,
                icon: 'error',
                ...alertTheme(),
            });
        } finally {
            setBusy(false);
        }
    }

    /*
     * Envío definitivo.
     */
    async function send(
        e: FormEvent<HTMLFormElement>,
    ) {
        e.preventDefault();

        if (
            changed ||
            !complete ||
            !editable ||
            !boot.book ||
            busy
        ) {
            return;
        }

        const form = e.currentTarget;

        const result = await Swal.fire({
            title: 'Enviar calificaciones',
            text:
                `Se enviarán ${students.length} calificaciones ` +
                'a Dirección. La captura quedará bloqueada ' +
                'para todos los docentes asignados.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Confirmar envío',
            cancelButtonText: 'Seguir revisando',
            reverseButtons: true,
            focusCancel: true,
            ...alertTheme(),
        });

        if (result.isConfirmed) {
            HTMLFormElement.prototype.submit.call(form);
        }
    }

    if (!a || !p) {
        return (
            <p className="text-red-600">
                No se encontró la asignación académica.
            </p>
        );
    }

    return (
        <div className="space-y-5">
            {/* ENCABEZADO */}

            <div className="flex flex-wrap items-center justify-between gap-3">
                <a
                    href="/teacher/grades"
                    className="inline-flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400"
                >
                    <ArrowLeft size={16} />
                    Mis calificaciones
                </a>

                {!!boot.book && (
                    <a
                        href={`${url}/pdf`}
                        className={secondary}
                    >
                        <FileDown size={17} />
                        Exportar PDF
                    </a>
                )}
            </div>

            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-bold">
                        {a.subject_name}
                    </h1>

                    <p className="mt-1 text-sm text-slate-500">
                        {a.group_name} · {p.name}
                    </p>
                </div>

                <span
                    className={
                        'rounded-full px-3 py-1 text-xs font-semibold ' +
                        (
                            status === 'published'
                                ? 'bg-emerald-100 text-emerald-700'
                                : status === 'submitted'
                                  ? 'bg-blue-100 text-blue-700'
                                  : 'bg-slate-200 text-slate-700'
                        )
                    }
                >
                    {status === 'published'
                        ? 'Publicadas'
                        : status === 'submitted'
                          ? 'Enviadas a Dirección'
                          : 'Borrador'}
                </span>
            </div>

            {/* INDICADORES */}

            <div className="grid grid-cols-2 gap-3 sm:grid-cols-5">
                {([
                    ['Alumnos', students.length],
                    ['Capturadas', recorded],
                    ['Pendientes', pending],
                    ['Menores a 6', low],
                    ['Inválidas', invalid],
                ] as const).map(([label, value]) => (
                    <div className={frame} key={label}>
                        <p className="text-xs text-slate-500">
                            {label}
                        </p>

                        <p
                            className={
                                'mt-2 text-2xl font-bold tabular-nums ' +
                                (
                                    label === 'Menores a 6' && low > 0
                                        ? 'text-red-600'
                                        : ''
                                )
                            }
                        >
                            {value}
                        </p>
                    </div>
                ))}
            </div>

            {/* PROGRESO */}

            <div className={frame}>
                <div className="mb-3 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 className="font-semibold">
                            Avance de captura
                        </h2>

                        <p className="mt-1 text-xs text-slate-500">
                            Modo:{' '}
                            {decimals
                                ? 'Décimas (0.1)'
                                : 'Enteros (1)'}
                            {' · '}

                            <a
                                href="/teacher/dashboard?section=profile"
                                className="text-blue-600 underline"
                            >
                                Cambiar en Mi perfil
                            </a>
                        </p>
                    </div>

                    <strong className="text-sm tabular-nums">
                        {recorded}/{students.length}
                    </strong>
                </div>

                <div className="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                    <div
                        className="h-full rounded-full bg-blue-600"
                        style={{
                            width: `${
                                students.length
                                    ? (recorded / students.length) * 100
                                    : 0
                            }%`,
                        }}
                    />
                </div>

                {changed && (
                    <p className="mt-3 text-sm font-medium text-amber-600">
                        {dirtyKeys.length} calificación(es)
                        modificada(s) sin guardar.
                    </p>
                )}

                {!editable && (
                    <p className="mt-3 flex items-center gap-2 text-sm text-blue-600">
                        <Lock size={16} />
                        Captura bloqueada para edición.
                    </p>
                )}
            </div>

            {error && (
                <p
                    role="alert"
                    className="rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300"
                >
                    {error}
                </p>
            )}

            {/* TABLA DE CALIFICACIONES */}

            <form onSubmit={(e) => void saveGrades(e)}>
                <div className={`${frame} overflow-x-auto`}>
                    <table className="w-full min-w-[580px] text-left text-sm">
                        <thead className="text-xs uppercase text-slate-500">
                            <tr>
                                <th className="pb-3">
                                    Matrícula
                                </th>
                                <th className="pb-3">
                                    Alumno
                                </th>
                                <th className="pb-3 text-center">
                                    Calificación (0–10)
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            {students.map((student) => {
                                const key = String(
                                    student.enrollment_id,
                                );

                                const value = values[key] ?? '';
                                const number = Number(value);

                                const isLow =
                                    valid(value) && number < 6;

                                const isInvalid =
                                    value !== '' && !valid(value);

                                const atMin =
                                    value !== '' &&
                                    Number.isFinite(number) &&
                                    number <= 0;

                                const atMax =
                                    value !== '' &&
                                    Number.isFinite(number) &&
                                    number >= 10;

                                const dirty =
                                    dirtyKeys.includes(key);

                                return (
                                    <tr
                                        key={key}
                                        className="border-t border-slate-100 dark:border-slate-800"
                                    >
                                        <td className="py-3 text-slate-500">
                                            {student.student_code}
                                        </td>

                                        <td className="py-3 font-medium">
                                            {student.last_name}{' '}
                                            {student.first_name}

                                            {dirty && (
                                                <span className="ml-2 text-xs font-medium text-amber-600">
                                                    Sin guardar
                                                </span>
                                            )}
                                        </td>

                                        <td className="py-3">
                                            <div className="flex items-center justify-center gap-1.5">
                                                <button
                                                    type="button"
                                                    aria-label={`Disminuir calificación de ${student.first_name} ${student.last_name}`}
                                                    title={`Restar ${decimals ? '0.1' : '1'}`}
                                                    disabled={
                                                        !editable ||
                                                        busy ||
                                                        atMin
                                                    }
                                                    onClick={() =>
                                                        adjust(key, -1)
                                                    }
                                                    className="grid h-10 w-10 shrink-0 place-items-center rounded-lg border border-slate-300 hover:bg-slate-100 disabled:opacity-30 dark:border-slate-700 dark:hover:bg-slate-800"
                                                >
                                                    <Minus size={17} />
                                                </button>

                                                <input
                                                    type="text"
                                                    inputMode={
                                                        decimals
                                                            ? 'decimal'
                                                            : 'numeric'
                                                    }
                                                    autoComplete="off"
                                                    maxLength={4}
                                                    disabled={
                                                        !editable || busy
                                                    }
                                                    aria-label={`Calificación de ${student.first_name} ${student.last_name}`}
                                                    value={value}
                                                    onChange={(e) =>
                                                        changeValue(
                                                            key,
                                                            e.target.value,
                                                        )
                                                    }
                                                    onBlur={() =>
                                                        finishEditing(key)
                                                    }
                                                    className={
                                                        'h-10 w-20 rounded-lg border text-center font-semibold tabular-nums outline-none ' +
                                                        (
                                                            isInvalid
                                                                ? 'border-amber-500 bg-amber-50 text-amber-700'
                                                                : isLow
                                                                  ? 'border-red-500 bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300'
                                                                  : 'border-slate-300 bg-white dark:border-slate-700 dark:bg-slate-950'
                                                        ) +
                                                        ' disabled:opacity-70'
                                                    }
                                                />

                                                <button
                                                    type="button"
                                                    aria-label={`Aumentar calificación de ${student.first_name} ${student.last_name}`}
                                                    title={`Sumar ${decimals ? '0.1' : '1'}`}
                                                    disabled={
                                                        !editable ||
                                                        busy ||
                                                        atMax
                                                    }
                                                    onClick={() =>
                                                        adjust(key, 1)
                                                    }
                                                    className="grid h-10 w-10 shrink-0 place-items-center rounded-lg border border-slate-300 hover:bg-slate-100 disabled:opacity-30 dark:border-slate-700 dark:hover:bg-slate-800"
                                                >
                                                    <Plus size={17} />
                                                </button>
                                            </div>

                                            {isInvalid && (
                                                <p className="mt-1 text-center text-xs text-amber-600">
                                                    Nota inválida
                                                </p>
                                            )}
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>

                {editable && (
                    <button
                        type="submit"
                        className={`${button} mt-4`}
                        disabled={
                            busy ||
                            invalid > 0 ||
                            !changed
                        }
                    >
                        <Save size={17} />

                        {busy
                            ? 'Guardando...'
                            : `Guardar cambios (${dirtyKeys.length})`}
                    </button>
                )}
            </form>

            {/* ENVÍO DEFINITIVO */}

            {editable && !!boot.book && (
                <form
                    action={`${url}/submit`}
                    method="POST"
                    onSubmit={(e) => void send(e)}
                    className={`${frame} flex flex-wrap items-center justify-between gap-4`}
                >
                    <input
                        type="hidden"
                        name="_token"
                        value={boot.csrf}
                    />

                    <input
                        type="hidden"
                        name="confirm_submission"
                        value="1"
                    />

                    <div>
                        <h2 className="font-semibold">
                            Enviar a Dirección
                        </h2>

                        <p className="mt-1 text-sm text-slate-500">
                            Se requieren todas las notas guardadas.
                            Una vez enviada, la captura queda
                            bloqueada para todos los docentes.
                        </p>
                    </div>

                    <button
                        className={button}
                        type="submit"
                        disabled={
                            busy ||
                            !complete ||
                            changed
                        }
                    >
                        <Send size={17} />
                        Confirmar envío
                    </button>
                </form>
            )}

            {status === 'submitted' && (
                <div className={`${frame} flex items-center gap-2 text-blue-600`}>
                    <CheckCircle2 size={18} />
                    Enviada a Dirección. Pendiente de validación.
                </div>
            )}

            {status === 'published' && (
                <div className={`${frame} flex items-center gap-2 text-emerald-600`}>
                    <CheckCircle2 size={18} />
                    Calificaciones publicadas.
                </div>
            )}
        </div>
    );
}
