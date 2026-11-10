import { useMemo, useState } from 'react';
import {
    AlertCircle, ArrowRight, BookOpenCheck, CalendarDays, ChevronRight,
    CircleHelp, Download, FileDown, FileText, FilterX, GraduationCap,
    Info, LibraryBig, Printer, Search, ShieldCheck, SlidersHorizontal,
    UsersRound, X,
} from 'lucide-react';
import { academicLabel, academicNavigation } from './navigation';
import type { AcademicBootstrap, AcademicRecord, AcademicSectionId } from './types';
import './academic-print.css';

export type AcademicModuleProps = {
    data: AcademicBootstrap;
    section: AcademicSectionId;
    onNavigate: (section: AcademicSectionId) => void;
    onBack: () => void;
};

const descriptions: Record<AcademicSectionId, string> = {
    overview: 'Estado de la operación académica y accesos rápidos a cada proceso.',
    curriculum: 'Planes oficiales o institucionales versionados por nivel y ciclo.',
    subjects: 'Campos formativos, disciplinas y asignaturas adicionales de la escuela.',
    teachers: 'Directorio académico de docentes compartido con el núcleo institucional.',
    assignments: 'Relaciones docente, grupo, materia y ciclo, sin duplicar los grupos escolares.',
    periods: 'Períodos, fechas de captura, validación y publicación de resultados.',
    grades: 'Captura y revisión de evaluaciones con trazabilidad por docente y período.',
    'report-cards': 'Boletas institucionales y documentos descargables para alumnos y tutores.',
    reports: 'Consulta por grupo, nivel, materia, período y ciclo escolar.',
    help: 'Guías de operación, instrucciones y referencias normativas por versión.',
};

const guidance: Partial<Record<AcademicSectionId, { title: string; body: string }>> = {
    curriculum: {
        title: 'Planes oficiales y propios',
        body: 'El programa SEP puede usarse como referencia, pero cada institución puede agregar materias, campos, niveles y planes propios. Cada ciclo debe conservar la versión del plan aplicada.',
    },
    subjects: {
        title: 'Las materias no son universales',
        body: 'La institución puede manejar asignaturas adicionales, diferentes por grado o nivel. No deben insertarse materias fijas para todas las escuelas.',
    },
    teachers: {
        title: 'Un solo directorio de personas',
        body: 'La ficha del docente se comparte con SchoolCore. Academic añade sus asignaciones curriculares, no duplica personas.',
    },
    assignments: {
        title: 'Permisos vinculados a la carga académica',
        body: 'Un docente solamente debe capturar evaluaciones de las materias y grupos que tenga formalmente asignados en el ciclo.',
    },
    periods: {
        title: 'Versiones y fechas',
        body: 'Cada calendario y esquema de evaluación pertenece a un ciclo y nivel; una norma nueva no debe recalcular resultados históricos.',
    },
    grades: {
        title: 'Captura, revisión y publicación',
        body: 'La captura es un borrador: debe existir un flujo de entrega, validación, autorización y publicación. Los tutores solo pueden consultar resultados publicados.',
    },
    'report-cards': {
        title: 'Documentos institucionales',
        body: 'Las boletas generadas por Academic deben tener una leyenda que identifique su carácter institucional. No se presentan como documentos oficiales certificados sin autorización.',
    },
};

const printableColumns: Array<{ label: string; value: (r: AcademicRecord) => string }> = [
    { label: 'Nombre', value: r => r.name },
    { label: 'Descripción', value: r => r.description ?? '' },
    { label: 'Nivel', value: r => r.level ?? '' },
    { label: 'Grado', value: r => r.grade ?? '' },
    { label: 'Grupo', value: r => r.group ?? '' },
    { label: 'Materia', value: r => r.subject ?? '' },
    { label: 'Docente', value: r => r.teacher ?? '' },
    { label: 'Período', value: r => r.period ?? '' },
    { label: 'Estado', value: r => r.status ?? '' },
];

function csvValue(value: string): string {
    // Previene inyección de fórmulas en lectores de hojas de cálculo.
    const safe = /^[\s]*[=+@\-]/.test(value) ? `'${value}` : value;
    return `"${safe.replace(/"/g, '""')}"`;
}

function exportCsv(rows: AcademicRecord[], section: AcademicSectionId): void {
    const text = [
        printableColumns.map(c => csvValue(c.label)).join(','),
        ...rows.map(row => printableColumns.map(c => csvValue(c.value(row))).join(',')),
    ].join('\r\n');

    const blob = new Blob(['\uFEFF', text], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `academic-${section}-${new Date().toISOString().slice(0, 10)}.csv`;
    document.body.append(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
}

/** Sin sidebar anidada: este módulo solamente produce el contenido central. */
export default function AcademicModule({
    data, section, onNavigate, onBack,
}: AcademicModuleProps) {
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('all');
    const [showHelp, setShowHelp] = useState(false);
    const [printMode, setPrintMode] = useState<'print' | 'pdf'>('print');

    const records = useMemo(
        () => data.records?.[section] ?? [],
        [data.records, section],
    );
    const statusOptions = useMemo(
        () => [...new Set(records.map(r => r.status?.trim()).filter((v): v is string => Boolean(v)))].sort(),
        [records],
    );
    const filtered = useMemo(() => {
        const needle = search.trim().toLocaleLowerCase('es-MX');
        return records.filter(row => {
            if (status !== 'all' && row.status !== status) return false;
            if (!needle) return true;
            return [row.name, row.description, row.level, row.grade, row.group,
                row.teacher, row.subject, row.period, row.status]
                .some(value => (value ?? '').toLocaleLowerCase('es-MX').includes(needle));
        });
    }, [records, search, status]);

    const canExport = data.permissions?.downloadReports === true && filtered.length > 0;

    const changeSection = (next: AcademicSectionId) => {
        setSearch('');
        setStatus('all');
        setShowHelp(false);
        onNavigate(next);
    };
    const print = (mode: 'print' | 'pdf') => {
        setPrintMode(mode);
        // Título y tabla imprimible permanecen en el DOM; la hoja CSS oculta las herramientas.
        requestAnimationFrame(() => window.print());
    };

    if (!data.enabled) {
        return (
            <section className="rounded-xl border border-amber-200 bg-amber-50 p-6 text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200">
                <ShieldCheck className="mb-2" size={24} />
                <h1 className="text-lg font-semibold">Academic no está habilitado</h1>
                <p className="mt-1 text-sm">Tu institución no cuenta con acceso a este módulo. Consulta al administrador.</p>
                <button type="button" onClick={onBack} className="mt-4 rounded-lg border border-current px-3 py-2 text-sm">Volver a SchoolCore</button>
            </section>
        );
    }

    return (
        <div className="academic-module min-w-0 space-y-4">
            <div className="academic-hide-print flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p className="text-[11px] font-semibold uppercase tracking-widest text-blue-600">SchoolCore / Academic</p>
                    <h1 className="mt-1 text-2xl font-semibold tracking-tight">{academicLabel(section)}</h1>
                    <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{descriptions[section]}</p>
                </div>
                <button type="button" onClick={onBack} className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm hover:border-blue-400 dark:border-slate-700 dark:bg-slate-900">
                    Volver a SchoolCore
                </button>
            </div>

            {/* Barra secundaria: debajo del header del ERP, solo para acciones de la vista. */}
            <div className="academic-hide-print sticky top-0 z-10 rounded-xl border border-slate-300 bg-slate-100/95 p-2 shadow-sm backdrop-blur dark:border-slate-700 dark:bg-slate-800/95">
                <div className="flex flex-wrap items-center gap-2">
                    <div className="mr-auto flex items-center gap-2 px-2 text-xs font-semibold text-slate-600 dark:text-slate-200">
                        <SlidersHorizontal size={16} /> HERRAMIENTAS DE VISTA
                    </div>
                    {section !== 'overview' && section !== 'help' && (
                        <>
                            <label className="relative min-w-40 flex-1 sm:max-w-64">
                                <span className="sr-only">Buscar en {academicLabel(section)}</span>
                                <Search size={15} className="pointer-events-none absolute left-2 top-1/2 -translate-y-1/2 text-slate-400" />
                                <input value={search} onChange={e => setSearch(e.target.value)} placeholder="Buscar en esta vista" className="h-9 w-full rounded-lg border border-slate-300 bg-white pl-8 pr-2 text-sm outline-none focus:border-blue-500 dark:border-slate-600 dark:bg-slate-900" />
                            </label>
                            {statusOptions.length > 0 && (
                                <select aria-label="Filtrar por estado" value={status} onChange={e => setStatus(e.target.value)} className="h-9 rounded-lg border border-slate-300 bg-white px-2 text-sm dark:border-slate-600 dark:bg-slate-900">
                                    <option value="all">Todos los estados</option>
                                    {statusOptions.map(value => <option key={value} value={value}>{value}</option>)}
                                </select>
                            )}
                            {(search || status !== 'all') && (
                                <button type="button" onClick={() => { setSearch(''); setStatus('all'); }} title="Limpiar filtros" className="flex h-9 items-center gap-1 rounded-lg px-2 text-xs hover:bg-white dark:hover:bg-slate-700"><FilterX size={16} /> Limpiar</button>
                            )}
                            <span className="px-2 text-xs tabular-nums text-slate-500 dark:text-slate-300">{filtered.length} registros</span>
                        </>
                    )}
                    <div className="hidden h-6 w-px bg-slate-300 sm:block dark:bg-slate-600" />
                    <button type="button" onClick={() => exportCsv(filtered, section)} disabled={!canExport} title={canExport ? 'Exportar registros visibles a CSV' : 'Necesitas registros y permiso de exportación'} className="academic-tool-button"><Download size={15} /> CSV</button>
                    <button type="button" onClick={() => print('print')} disabled={!canExport} className="academic-tool-button"><Printer size={15} /> Imprimir</button>
                    <button type="button" onClick={() => print('pdf')} disabled={!canExport} title="Usa Guardar como PDF en el diálogo de impresión" className="academic-tool-button"><FileDown size={15} /> PDF</button>
                    <button type="button" onClick={() => setShowHelp(v => !v)} aria-expanded={showHelp} className="academic-tool-button"><CircleHelp size={15} /> Ayuda</button>
                </div>
            </div>

            {showHelp && (
                <aside className="academic-hide-print relative rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm dark:border-blue-900 dark:bg-blue-950">
                    <button type="button" onClick={() => setShowHelp(false)} className="absolute right-3 top-3 rounded-md p-1 hover:bg-blue-100 dark:hover:bg-blue-900" aria-label="Cerrar ayuda"><X size={16} /></button>
                    <h2 className="font-semibold">{guidance[section]?.title ?? 'Ayuda de Academic'}</h2>
                    <p className="mt-1 max-w-3xl text-slate-600 dark:text-slate-300">{guidance[section]?.body ?? 'El menú principal de Academic permite navegar por las áreas. Los filtros y las exportaciones están disponibles según los registros de cada vista.'}</p>
                </aside>
            )}

            <div className="academic-print-heading hidden">
                <h1>Academic · {academicLabel(section)}</h1>
                <p>{data.school.name} · {data.cycle?.name ?? 'Sin ciclo activo'}</p>
                {printMode === 'pdf' && <p>Para PDF: seleccionar «Guardar como PDF» en el diálogo de impresión.</p>}
            </div>

            {section === 'overview' ? (
                <Overview data={data} onNavigate={changeSection} />
            ) : section === 'help' ? (
                <HelpCenter />
            ) : (
                <DataView section={section} data={data} records={filtered} total={records.length} />
            )}
        </div>
    );
}

function Overview({ data, onNavigate }: { data: AcademicBootstrap; onNavigate: (id: AcademicSectionId) => void }) {
    const sections: AcademicSectionId[] = ['curriculum', 'subjects', 'teachers', 'assignments', 'periods', 'grades', 'report-cards', 'reports'];
    return (
        <div className="space-y-4">
            <section className="rounded-xl border border-blue-200 bg-gradient-to-r from-slate-900 to-blue-800 p-5 text-white">
                <p className="text-xs uppercase tracking-[.2em] text-blue-200">GESTIÓN ACADÉMICA</p>
                <h2 className="mt-2 text-xl font-semibold">{data.school.name}</h2>
                <p className="mt-1 text-sm text-blue-100">Ciclo: {data.cycle?.name ?? 'Sin ciclo activo'}. Configuración curricular institucional flexible.</p>
            </section>
            <section className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                {sections.map(id => {
                    const nav = academicNavigation.find(n => n.id === id)!;
                    const Icon = nav.icon;
                    return (
                        <button type="button" onClick={() => onNavigate(id)} key={id} className="group flex min-h-32 flex-col rounded-xl border border-slate-200 bg-white p-4 text-left transition hover:border-blue-500 hover:shadow-sm focus-visible:outline-blue-500 dark:border-slate-700 dark:bg-slate-900">
                            <span className="mb-3 flex size-9 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950 dark:text-blue-300"><Icon size={19} /></span>
                            <strong className="text-sm">{nav.label}</strong>
                            <span className="mt-1 line-clamp-2 text-xs text-slate-500 dark:text-slate-400">{descriptions[id]}</span>
                            <span className="mt-auto flex items-center gap-1 pt-3 text-xs font-semibold text-blue-600">Abrir <ArrowRight size={13} /></span>
                        </button>
                    );
                })}
            </section>
        </div>
    );
}

function DataView({ section, data, records, total }: { section: AcademicSectionId; data: AcademicBootstrap; records: AcademicRecord[]; total: number }) {
    if (total === 0) {
        return (
            <section className="flex min-h-64 flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white px-5 py-9 text-center dark:border-slate-700 dark:bg-slate-900">
                <LibraryBig size={28} className="text-slate-400" />
                <h2 className="mt-3 font-semibold">Sin registros conectados</h2>
                <p className="mt-1 max-w-lg text-sm text-slate-500 dark:text-slate-400">
                    {academicLabel(section)} está preparado visualmente. Todavía debemos vincular los registros reales de Laravel para esta sección; no se han generado datos ficticios.
                </p>
                <div className="mt-4 flex items-start gap-2 rounded-lg bg-slate-100 p-3 text-left text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    <Info size={16} className="mt-px shrink-0" />
                    La configuración SEP es una base adaptable: los planes y materias propios de cada escuela deben persistirse por ciclo y nivel.
                </div>
            </section>
        );
    }
    return (
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <div className="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 p-4 dark:border-slate-800">
                <h2 className="font-semibold">{academicLabel(section)}</h2>
                <span className="text-xs text-slate-500">{records.length} de {total} registros · {data.cycle?.name ?? 'Ciclo no definido'}</span>
            </div>
            {records.length === 0 ? (
                <p className="p-6 text-center text-sm text-slate-500">Ningún registro coincide con los filtros.</p>
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead className="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800">
                            <tr><th className="px-4 py-3">Nombre</th><th className="px-4 py-3">Nivel / Grupo</th><th className="px-4 py-3">Materia / Docente</th><th className="px-4 py-3">Estado</th></tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                            {records.map(row => (
                                <tr key={row.id}>
                                    <td className="px-4 py-3"><strong className="block">{row.name}</strong><span className="text-xs text-slate-500">{row.description}</span></td>
                                    <td className="px-4 py-3">{[row.level, row.grade, row.group].filter(Boolean).join(' · ') || '—'}</td>
                                    <td className="px-4 py-3">{[row.subject, row.teacher].filter(Boolean).join(' · ') || '—'}</td>
                                    <td className="px-4 py-3">{row.status ?? '—'}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}

function HelpCenter() {
    const entries = [
        ['Planes configurables', 'Los planes SEP son una referencia versionada. Una escuela puede añadir o cambiar materias conforme a su oferta y autorización.'],
        ['Ciclo de evaluación', 'Captura → Entrega docente → Revisión → Autorización → Publicación. Los borradores no deben aparecer en la app de tutores.'],
        ['Boletas institucionales', 'Los documentos destinados a tutores incluyen leyenda de carácter institucional cuando corresponda; no deben afirmarse oficialmente certificados.'],
        ['Permisos', 'Laravel debe validar el contrato Academic, el rol y la asignación docente en cada endpoint, incluso si React oculta botones.'],
    ];
    return (
        <section className="grid gap-3 md:grid-cols-2">
            {entries.map(([title, body]) => (
                <article key={title} className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                    <div className="mb-2 flex items-center gap-2 text-blue-600"><CircleHelp size={18}/><h2 className="font-semibold text-slate-900 dark:text-slate-100">{title}</h2></div>
                    <p className="text-sm text-slate-600 dark:text-slate-400">{body}</p>
                </article>
            ))}
        </section>
    );
}
