import { createRoot } from 'react-dom/client';
import { useEffect, useState, type FormEvent } from 'react';
import '../../css/grade-portal.css';

type Grade = {
    subject: string;
    value: string;
    affects_average: boolean;
    published_at: string | null;
};

type Period = {
    name: string;
    average: string | null;
    grades: Grade[];
};

type Report = {
    school: string;
    student: string;
    student_code: string;
    cycle: string | null;
    periods: Period[];
};

const mount = document.getElementById('grade-portal-root');
const schoolName = mount?.dataset.schoolName ?? 'Escuela';
const base = `/consulta/${encodeURIComponent(mount?.dataset.schoolSlug ?? '')}`;


const schoolSlug = mount?.dataset.schoolSlug ?? '';

const portalHome = `/portal/${encodeURIComponent(schoolSlug)}`;
const gradesPage = `${portalHome}/calificaciones`;

const schoolLogo = mount?.dataset.schoolLogo ?? '';
const gradesEnabled = mount?.dataset.gradesEnabled === '1';

if (mount) {
    mount.style.setProperty(
        '--gp-primary',
        mount.dataset.schoolPrimary || '#2563eb',
    );

    mount.style.setProperty(
        '--gp-secondary',
        mount.dataset.schoolSecondary || '#0f172a',
    );
}

function SchoolHome() {
    return (
        <div className="gp-shell">
            <header className="gp-topbar">
                <div className="gp-brand">

{schoolLogo ? (
    <img
        className="gp-school-logo"
        src={schoolLogo}
        alt={`Logo de ${schoolName}`}
    />
) : (
    <div className="gp-logo">SC</div>
)}


                    <div>
                        <strong>{schoolName}</strong>
                        <span>Portal de servicios escolares</span>
                    </div>
                </div>
            </header>

            <main className="gp-card gp-home">
                <div className="gp-eyebrow">
                    Portal escolar
                </div>

                <h1>Bienvenido</h1>

                <p className="gp-muted">
                    Realiza trámites y consulta información
                    académica de manera segura, sin instalar
                    ninguna aplicación.
                </p>

                <div className="gp-services">

                    {gradesEnabled ? (
                        <a
                            className="gp-service"
                            href={gradesPage}
                        >
                            <strong>Calificaciones</strong>
                            <span>
                                Consulta mediante CURP y PIN
                                individual. Reporte en PDF.
                            </span>
                            <small>Acceder a consulta</small>
                        </a>
                    ) : (
                        <div className="gp-service gp-disabled">
                            <strong>Calificaciones</strong>
                            <span>
                                La escuela no tiene habilitada
                                la consulta pública.
                            </span>
                        </div>
                    )}

                    <div className="gp-service gp-disabled">
                        <strong>Preinscripciones</strong>
                        <span>
                            Solicitudes, documentación y
                            emisión de folio.
                        </span>
                        <small>Próximamente</small>
                    </div>

                    <div className="gp-service gp-disabled">
                        <strong>Seguimiento de solicitudes</strong>
                        <span>
                            Consultar el estado de una
                            preinscripción.
                        </span>
                        <small>Próximamente</small>
                    </div>

                    <div className="gp-service gp-disabled">
                        <strong>Aula virtual</strong>
                        <span>
                            Acceso a servicios de aprendizaje
                            en línea.
                        </span>
                        <small>Futuro</small>
                    </div>
                </div>
            </main>

            <div className="gp-bottom">
                SchoolCore · Servicios escolares
            </div>
        </div>
    );
}


async function api<T>(path: string, data?: object): Promise<T> {
    const csrf = document.querySelector<HTMLMetaElement>(
        'meta[name="csrf-token"]',
    )?.content ?? '';

    const response = await fetch(base + path, {
        method: data === undefined ? 'GET' : 'POST',
        credentials: 'same-origin',
        cache: 'no-store',
        headers: {
            Accept: 'application/json',
            ...(data === undefined ? {} : {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
            }),
        },
        ...(data === undefined ? {} : {
            body: JSON.stringify(data),
        }),
    });

    const value = await response.json().catch(() => ({})) as {
        message?: string;
    };

    if (!response.ok) {
        throw new Error(
            value.message || 'No se pudo completar la solicitud.',
        );
    }

    return value as T;
}

function Portal() {
    const [curp, setCurp] = useState('');
    const [studentCode, setStudentCode] = useState('');
    const [report, setReport] = useState<Report | null>(null);
    const [loading, setLoading] = useState(false);
    const [checking, setChecking] = useState(true);
    const [error, setError] = useState('');

    async function refresh() {
        const response = await api<Report>('/resultados');
        setReport(response);
    }

    useEffect(() => {
        let cancelled = false;

        api<Report>('/resultados')
            .then(value => {
                if (!cancelled) setReport(value);
            })
            .catch(() => {
                if (!cancelled) setReport(null);
            })
            .finally(() => {
                if (!cancelled) setChecking(false);
            });

        return () => {
            cancelled = true;
        };
    }, []);

    async function login(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setError('');
        setLoading(true);

        try {
            const result = await api<{
                message: string;
                csrf_token: string;
            }>('/acceder', {
                curp: curp.toUpperCase().trim(),
                 student_code: studentCode.trim(),
            });

            const meta = document.querySelector<HTMLMetaElement>(
                'meta[name="csrf-token"]',
            );

            if (meta) meta.content = result.csrf_token;

           setStudentCode('');
            await refresh();
        } catch (cause) {
            setError(
                cause instanceof Error
                    ? cause.message
                    : 'No se pudo consultar.',
            );
        } finally {
            setLoading(false);
        }
    }

    async function logout() {
        setLoading(true);

        try {
            const result = await api<{
                csrf_token: string;
            }>('/salir', {});

            const meta = document.querySelector<HTMLMetaElement>(
                'meta[name="csrf-token"]',
            );

            if (meta) meta.content = result.csrf_token;

            setReport(null);
            setCurp('');
          setStudentCode('');
            setError('');
        } catch (cause) {
            setError(
                cause instanceof Error
                    ? cause.message
                    : 'No se pudo cerrar la consulta.',
            );
        } finally {
            setLoading(false);
        }
    }

    return (
        <div className="gp-shell">
            <header className="gp-topbar">
                <div className="gp-brand">
                    <div className="gp-logo">SC</div>
                    <div>
                        <strong>{schoolName}</strong>
                        <span>Consulta académica para familias</span>
                    </div>
                </div>

                {report && (
                    <button
                        type="button"
                        onClick={() => void logout()}
                        disabled={loading}
                        className="gp-subtle gp-noprint"
                    >
                        Cerrar consulta
                    </button>
                )}
            </header>

            {checking ? (
                <main className="gp-card gp-loading" role="status">
                    Comprobando acceso…
                </main>
            ) : !report ? (
                <main className="gp-card gp-signin">
                    <div className="gp-eyebrow">
                        SchoolCore Academic
                    </div>

                    <h1>Consulta de calificaciones</h1>

                    <p className="gp-muted">
                     Consulta las calificaciones que tu escuela
haya publicado. Necesitas la CURP del alumno
y su matrícula escolar.
                    </p>

                    {error && (
                        <div role="alert" className="gp-error">
                            {error}
                        </div>
                    )}

                    <form
                        onSubmit={e => void login(e)}
                        className="gp-form"
                    >

<label>
    CURP del alumno

    <input
        type="text"
        name="school_student_curp"
        autoComplete="off"
        autoCapitalize="characters"
        spellCheck={false}
        maxLength={18}
        required
        value={curp}
        onChange={e =>
            setCurp(
                e.target.value
                    .toUpperCase()
                    .replace(/[^A-Z0-9]/g, '')
                    .slice(0, 18)
            )
        }
        placeholder="CURP de 18 caracteres"
    />
</label>

<label>
    Matrícula escolar

    <input
        type="text"
        name="school_student_code"
        autoComplete="off"
        maxLength={50}
        required
        value={studentCode}
        onChange={e =>
            setStudentCode(e.target.value)
        }
        placeholder="Matrícula del alumno"
    />
</label>

<button
    type="submit"
    className="gp-primary"
    disabled={
        loading ||
        curp.length !== 18 ||
        !studentCode.trim()
    }
>
    {loading
        ? 'Consultando...'
        : 'Consultar calificaciones'}
</button>

                    </form>

                    <p className="gp-hint">
                       ¿No conoces tu matrícula? Consúltala directamente
con la administración escolar. Por seguridad,
varios intentos incorrectos bloquean
temporalmente el acceso.
                    </p>
                </main>
            ) : (
                <main className="gp-report gp-card">
                    {error && (
                        <div
                            role="alert"
                            className="gp-error gp-noprint"
                        >
                            {error}
                        </div>
                    )}

                    <div className="gp-report-head">
                        <div>
                            <div className="gp-eyebrow">
                                Reporte académico individual
                            </div>

                            <h1>{report.student}</h1>

                            <p className="gp-muted">
                                Matrícula: {report.student_code}
                                {' · '}
                                Ciclo: {report.cycle ??
                                    'Sin datos de ciclo'}
                            </p>
                        </div>

                        <a
                            className="gp-primary gp-noprint"
                            href={base + '/pdf'}
                        >
                            Descargar PDF
                        </a>
                    </div>

                    {report.periods.length === 0 && (
                        <div className="gp-empty">
                            La escuela todavía no ha publicado
                            calificaciones para este alumno
                            en el ciclo activo.
                        </div>
                    )}

                    {report.periods.map((period, index) => (
                        <section
                            className="gp-period"
                            key={`${index}-${period.name}`}
                        >
                            <div className="gp-period-title">
                                <h2>{period.name}</h2>

                                {period.average !== null && (
                                    <span>
                                        Promedio: {period.average}
                                    </span>
                                )}
                            </div>

                            <table>
                                <thead>
                                    <tr>
                                        <th>Materia</th>
                                        <th>Calificación</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    {period.grades.map((row, i) => (
                                        <tr key={`${i}-${row.subject}`}>
                                            <td>{row.subject}</td>

                                            <td
                                                className={
                                                    Number(row.value) < 6
                                                        ? 'gp-low'
                                                        : 'gp-value'
                                                }
                                            >
                                                {row.value}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>

                            {period.average === null && (
                                <p className="gp-small">
                                    El promedio no está configurado
                                    para estas materias.
                                </p>
                            )}
                        </section>
                    ))}

                    <footer className="gp-foot">
                        Documento informativo generado desde
                        calificaciones publicadas por Dirección.
                        No sustituye una boleta oficial certificada.
                    </footer>
                </main>
            )}

            <div className="gp-bottom gp-noprint">
                Plataforma escolar · Acceso individual protegido
            </div>
        </div>
    );
}

if (mount) {
  const isGradesPage = window.location.pathname
    .replace(/\/+$/, '')
    .endsWith('/calificaciones');

createRoot(mount).render(
    isGradesPage ? <Portal /> : <SchoolHome />
);
}
