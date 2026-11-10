import { useCallback, useEffect, useState } from 'react';
import {
    CheckCircle2,
    ClipboardCopy,
    ExternalLink,
    RefreshCw,
    ShieldOff,
} from 'lucide-react';

type PortalSettings = {
    school_name: string;
    url: string;
    enabled: boolean;
};

type UpdateResult = {
    enabled: boolean;
    message: string;
};

const ROOT = '/admin/schoolcore/academic/grade-portal';

const button =
    'inline-flex items-center justify-center gap-2 rounded-lg ' +
    'border border-slate-300 px-4 py-2 text-sm font-medium ' +
    'hover:bg-slate-100 disabled:opacity-40 ' +
    'dark:border-slate-700 dark:hover:bg-slate-800';

const primary =
    'inline-flex items-center justify-center gap-2 rounded-lg ' +
    'bg-blue-600 px-4 py-2 text-sm font-semibold text-white ' +
    'hover:bg-blue-700 disabled:opacity-40';

async function api<T>(url: string, body?: object): Promise<T> {
    const csrf = document.querySelector<HTMLMetaElement>(
        'meta[name="csrf-token"]',
    )?.content;

    if (body !== undefined && !csrf) {
        throw new Error('No se encontró el token CSRF.');
    }

    const response = await fetch(url, {
        method: body === undefined ? 'GET' : 'POST',
        credentials: 'same-origin',
        cache: 'no-store',
        headers: {
            Accept: 'application/json',
            ...(body !== undefined ? {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf!,
            } : {}),
        },
        ...(body !== undefined
            ? { body: JSON.stringify(body) }
            : {}),
    });

    const result = await response.json().catch(() => ({}));

    if (!response.ok) {
        const error = result as { message?: string };
        throw new Error(
            error.message ?? 'No se pudo completar la operación.',
        );
    }

    return result as T;
}

export default function AcademicGradePortalAdmin() {
    const [settings, setSettings] =
        useState<PortalSettings | null>(null);

    const [loading, setLoading] = useState(true);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');
    const [message, setMessage] = useState('');

    const load = useCallback(async () => {
        setLoading(true);
        setError('');

        try {
            const result = await api<PortalSettings>(ROOT);
            setSettings(result);
        } catch (cause) {
            setError(
                cause instanceof Error
                    ? cause.message
                    : 'No se pudo cargar la configuración.',
            );
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        void load();
    }, [load]);

    async function toggle() {
        if (!settings || busy) return;

        setBusy(true);
        setError('');
        setMessage('');

        try {
            const result = await api<UpdateResult>(
                `${ROOT}/settings`,
                { enabled: !settings.enabled },
            );

            setSettings(previous =>
                previous
                    ? { ...previous, enabled: result.enabled }
                    : previous,
            );

            setMessage(result.message);
        } catch (cause) {
            setError(
                cause instanceof Error
                    ? cause.message
                    : 'No se pudo actualizar el estado.',
            );
        } finally {
            setBusy(false);
        }
    }

    async function copyLink() {
        if (!settings) return;

        setError('');

        try {
            await navigator.clipboard.writeText(settings.url);
            setMessage('Enlace copiado al portapapeles.');
        } catch {
            setError(
                'No se pudo copiar automáticamente. ' +
                'Selecciona y copia el enlace manualmente.',
            );
        }
    }

    return (
        <section className="rounded-xl border border-slate-200 bg-white p-5 text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 className="text-lg font-semibold">
                        Consulta pública de calificaciones
                    </h2>

                    <p className="mt-1 text-sm text-slate-500">
                        Acceso de familias mediante CURP y
                        matrícula escolar. Solo calificaciones
                        publicadas por Dirección.
                    </p>
                </div>

                <button
                    type="button"
                    className={button}
                    onClick={() => void load()}
                    disabled={busy || loading}
                >
                    <RefreshCw size={16} />
                    Actualizar
                </button>
            </div>

            {error && (
                <div
                    role="alert"
                    className="mt-4 rounded-lg border border-red-800 bg-red-950 p-3 text-sm text-red-200"
                >
                    {error}
                </div>
            )}

            {message && (
                <div
                    role="status"
                    className="mt-4 rounded-lg border border-emerald-800 bg-emerald-950 p-3 text-sm text-emerald-200"
                >
                    {message}
                </div>
            )}

            {loading && !settings && (
                <p className="mt-4 text-sm text-slate-500">
                    Cargando configuración...
                </p>
            )}

            {settings && (
                <div className="mt-5 space-y-4">
                    <div className="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-slate-200 p-4 dark:border-slate-700">
                        <div>
                            <div className="flex items-center gap-2">
                                {settings.enabled ? (
                                    <CheckCircle2
                                        size={20}
                                        className="text-emerald-500"
                                    />
                                ) : (
                                    <ShieldOff
                                        size={20}
                                        className="text-slate-400"
                                    />
                                )}

                                <strong>
                                    {settings.enabled
                                        ? 'Consulta habilitada'
                                        : 'Consulta deshabilitada'}
                                </strong>
                            </div>

                            <p className="mt-2 text-xs text-slate-500">
                                {settings.enabled
                                    ? 'Las familias pueden consultar calificaciones publicadas.'
                                    : 'La consulta de calificaciones está cerrada para las familias.'}
                            </p>
                        </div>

                        <button
                            type="button"
                            onClick={() => void toggle()}
                            disabled={busy}
                            className={
                                settings.enabled ? button : primary
                            }
                        >
                            {settings.enabled
                                ? 'Desactivar consulta'
                                : 'Activar consulta'}
                        </button>
                    </div>

                    <div className="rounded-lg border border-slate-200 p-4 dark:border-slate-700">
                        <h3 className="font-semibold">
                            Enlace del portal escolar
                        </h3>

                        <p className="mt-1 text-xs text-slate-500">
                            Comparte este mismo enlace para todos
                            los servicios públicos de la escuela.
                        </p>

                        <div className="mt-3 flex flex-wrap gap-2">
                            <input
                                type="text"
                                readOnly
                                aria-label="Enlace del portal escolar"
                                value={settings.url}
                                onFocus={event =>
                                    event.currentTarget.select()
                                }
                                className="min-w-0 flex-1 rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950"
                            />

                            <button
                                className={button}
                                type="button"
                                onClick={() => void copyLink()}
                            >
                                <ClipboardCopy size={16} />
                                Copiar
                            </button>

                            <a
                                className={button}
                                href={settings.url}
                                target="_blank"
                                rel="noreferrer"
                            >
                                <ExternalLink size={16} />
                                Abrir
                            </a>
                        </div>
                    </div>

                    <p className="text-xs text-slate-500">
                        Las matrículas se administran desde
                        Inscripciones y expediente escolar.
                        Academic no emite credenciales individuales.
                    </p>
                </div>
            )}
        </section>
    );
}
