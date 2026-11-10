import { useCallback, useEffect, useMemo, useState } from 'react';
import AcademicPlanManager from './AcademicPlanManager';
import {
    BookOpen, ClipboardList, GraduationCap, Link2, Plus, UsersRound, Layers3, CalendarDays,
    Search, Download, Printer, RefreshCw, CircleHelp, X,
} from 'lucide-react';

type Cycle = { id: number; name: string; status: string; is_active: number | boolean };
type Level = { id: number; name: string };
type Plan = { id: number; name: string; framework_code: string | null; version: number;
    status: string; academic_cycle_id: number; academic_level_id: number;
    cycle_name: string; level_name: string };
type Subject = { id: number; name: string; code: string; kind: string; status: string };
type PlanSubject = { id: number; curriculum_plan_id: number; subject_id: number;
    subject_name: string; grade_label: string | null; is_official: boolean | number;
    affects_average: boolean | number };
type Teacher = { id: number; user_id: number; name: string; email: string; username: string | null;
    employee_code: string | null; status: string };
type Summary = { plans: number; subjects: number; teachers: number; assignments: number; groups: number };
type Data = { canManageTeachers?: boolean; summary?: Summary; cycles: Cycle[]; levels: Level[]; plans: Plan[]; subjects: Subject[];
    links: PlanSubject[]; teachers: Teacher[] };
export type AcademicCatalogSection = 'overview' | 'plans' | 'subjects' | 'groups' | 'teachers' | 'grades';
type Props = { section?: AcademicCatalogSection; onSectionChange?: (section: AcademicCatalogSection) => void };

const ROOT = '/admin/schoolcore/academic/catalog';
const empty: Data = { cycles: [], levels: [], plans: [], subjects: [], links: [], teachers: [] };

function errorMessage(payload: unknown): string {
    if (typeof payload !== 'object' || payload === null) return 'No se pudo procesar la solicitud.';
    const v = payload as { message?: string; errors?: Record<string, string[]> };
    return Object.values(v.errors ?? {}).flat()[0] ?? v.message ?? 'No se pudo procesar la solicitud.';
}


function levelsGrades(level: string): string[] {
    const name = level.toLocaleLowerCase('es-MX');

    const count =
        name.includes('primaria') ||
        name.includes('bachillerato')
            ? 6
            : name.includes('secundaria') ||
              name.includes('preescolar')
                ? 3
                : 0;

    return Array.from(
        { length: count },
        (_, index) => String(index + 1),
    );
}

function gradeCaption(level: string, grade: string): string {
    if (grade === 'all') return 'Todos los grados';

    return level.toLocaleLowerCase('es-MX')
        .includes('bachillerato')
        ? `Semestre ${grade}`
        : `Grado ${grade}`;
}


export default function AcademicCatalog({ section = 'plans', onSectionChange }: Props) {
    const [data, setData] = useState<Data>(empty);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [message, setMessage] = useState<string | null>(null);
    const [search, setSearch] = useState('');
    const [form, setForm] = useState<'plan' | 'subject' | 'link' | 'teacher' | null>(null);
    const [teacherName, setTeacherName] = useState('');
    const [teacherEmail, setTeacherEmail] = useState('');
    const [teacherUsername, setTeacherUsername] = useState('');
    const [teacherPassword, setTeacherPassword] = useState('');
    const [teacherPasswordConfirmation, setTeacherPasswordConfirmation] = useState('');
    const [resetTeacher, setResetTeacher] = useState<Teacher | null>(null);
    const [employeeCode, setEmployeeCode] = useState('');
    const [planName, setPlanName] = useState('');
    const [cycle, setCycle] = useState('');
    const [level, setLevel] = useState('');
    const [framework, setFramework] = useState('sep_2022');
    const [subjectName, setSubjectName] = useState('');
    const [subjectCode, setSubjectCode] = useState('');
    const [subjectKind, setSubjectKind] = useState<'official' | 'custom'>('custom');
    const [subjectPlan, setSubjectPlan] = useState('');
    const [subjectGrade, setSubjectGrade] = useState('');
    const [subjectIsOfficial, setSubjectIsOfficial] = useState(false);
    const [subjectAffectsAverage, setSubjectAffectsAverage] = useState(false);
    const [chosenPlan, setChosenPlan] = useState('');
    const [chosenSubject, setChosenSubject] = useState('');
    const [grade, setGrade] = useState('');
    const [isOfficial, setIsOfficial] = useState(false);
    const [affectsAverage, setAffectsAverage] = useState(true);

const [editingPlanId, setEditingPlanId] = useState('');
const [editingGrade, setEditingGrade] = useState('1');
const [editingSubjectId, setEditingSubjectId] = useState('');
const [editingOfficial, setEditingOfficial] = useState(false);
const [editingAverage, setEditingAverage] = useState(true);


    const load = useCallback(async () => {
        setLoading(true);
        setError(null);
        try {
            const response = await fetch(ROOT, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
            const payload: unknown = await response.json();
            if (!response.ok) throw new Error(errorMessage(payload));
            setData(payload as Data);
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Error al consultar Academic.');
        } finally { setLoading(false); }
    }, []);
    useEffect(() => { void load(); }, [load]);

    const filtered = useMemo(() => {
        const q = search.trim().toLocaleLowerCase('es-MX');
        const match = (...values: (string | null | undefined)[]) =>
            values.some(v => (v ?? '').toLocaleLowerCase('es-MX').includes(q));
        if (section === 'overview') return [];
        if (section === 'plans') return data.plans.filter(v => match(v.name, v.level_name, v.cycle_name, v.framework_code));
        if (section === 'subjects') return data.subjects.filter(v => match(v.name, v.code, v.kind));
        return data.teachers.filter(v => match(v.name, v.username, v.email, v.employee_code));
    }, [data, search, section]);


const editingPlan =
    data.plans.find(
        plan => String(plan.id) === editingPlanId,
    ) ?? null;

const editingGrades = editingPlan
    ? levelsGrades(editingPlan.level_name)
    : [];

const editingLinks = editingPlan
    ? data.links.filter(link =>
        link.curriculum_plan_id === editingPlan.id &&
        (
            editingGrade === 'all'
                ? link.grade_label === null
                : link.grade_label === editingGrade
        )
    )
    : [];

const generalLinks =
    editingPlan && editingGrade !== 'all'
        ? data.links.filter(link =>
            link.curriculum_plan_id === editingPlan.id &&
            link.grade_label === null
        )
        : [];

const editingAvailableSubjects = data.subjects.filter(
    subject =>
        subject.status === 'active' &&
        ![...editingLinks, ...generalLinks].some(
            link => link.subject_id === subject.id,
        ),
);


    const csrf = () => document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
    const save = async (url: string, body: Record<string, unknown>): Promise<boolean> => {
        setSaving(true); setError(null); setMessage(null);
        try {
            if (!csrf()) throw new Error('Falta token CSRF en el layout.');
            const response = await fetch(url, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify(body),
            });
            const result: unknown = await response.json();
            if (!response.ok) throw new Error(errorMessage(result));
            setMessage((result as { message: string }).message ?? 'Guardado correctamente.');
            setForm(null); setTeacherPassword(''); setTeacherPasswordConfirmation(''); await load(); return true;
        } catch (e) { setError(e instanceof Error ? e.message : 'Error al guardar.'); return false; }
        finally { setSaving(false); }
    };

async function attachToGrade(): Promise<void> {
    if (!editingPlan || !editingSubjectId || saving) {
        return;
    }

    const validGrade =
        editingGrade === 'all' ||
        editingGrades.includes(editingGrade);

    const available = editingAvailableSubjects.some(
        subject =>
            String(subject.id) === editingSubjectId,
    );

    if (!validGrade || !available) {
        setError(
            'Selecciona una materia y un grado compatibles con el plan.',
        );
        return;
    }

    const ok = await save(
        `${ROOT}/plans/${editingPlan.id}/subjects`,
        {
            subject_id: Number(editingSubjectId),
            grade_label:
                editingGrade === 'all'
                    ? null
                    : editingGrade,
            is_official: editingOfficial,
            affects_average: editingAverage,
        },
    );

    if (ok) {
        setEditingSubjectId('');
    }
}


    const exportCsv = () => {
        const rows: string[][] = section === 'plans'
            ? [['Plan', 'Ciclo', 'Nivel', 'Marco', 'Estado'], ...data.plans.filter(v => filtered.some(f => f.id === v.id)).map(v => [v.name,v.cycle_name,v.level_name,v.framework_code ?? '',v.status])]
            : section === 'subjects'
            ? [['Materia', 'Código', 'Origen', 'Estado'], ...data.subjects.filter(v => filtered.some(f => f.id === v.id)).map(v => [v.name,v.code,v.kind,v.status])]
            : [['Docente', 'Usuario', 'Correo', 'Clave', 'Estado'], ...data.teachers.filter(v => filtered.some(f => f.id === v.id)).map(v => [v.name,v.username??'',v.email.endsWith('@accounts.invalid')?'':v.email,v.employee_code ?? '',v.status])];
        const csv = '\uFEFF' + rows.map(row => row.map(x => `"${String(x).replaceAll('"','""')}"`).join(',')).join('\r\n');
        const url = URL.createObjectURL(new Blob([csv], {type:'text/csv;charset=utf-8'}));
        const a = document.createElement('a'); a.href=url; a.download=`academic-${section}.csv`; a.click();
        URL.revokeObjectURL(url);
    };
    const input = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-blue-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100';
    const action = 'inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:bg-slate-800';
    const title = section === 'overview' ? 'Resumen académico' : section === 'plans' ? 'Planes y programas' : section === 'subjects' ? 'Materias' : 'Docentes';
if (section === 'plans') {
    return <AcademicPlanManager />;
}
    return <div className="space-y-4">
        <header className="flex flex-wrap items-center justify-between gap-3">
            <div><p className="text-xs font-semibold uppercase tracking-widest text-blue-600">Academic / Organización</p>
                <h1 className="mt-1 text-2xl font-semibold">{title}</h1>
                <p className="mt-1 text-sm text-slate-500">Configuración por escuela y ciclo escolar. No afecta las calificaciones publicadas.</p>
            </div>
        </header>
        <div className="flex flex-wrap items-center gap-2 rounded-xl border border-slate-300 bg-slate-100 p-2 dark:border-slate-700 dark:bg-slate-800" aria-label="Herramientas de la vista">
            {section !== 'overview' && <div className="flex min-w-[190px] flex-1 items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 dark:border-slate-600 dark:bg-slate-900">
                <Search size={16} className="text-slate-500" />
                <input aria-label="Buscar registros" placeholder="Buscar..." value={search} onChange={e=>setSearch(e.target.value)} className="w-full bg-transparent py-2 text-sm outline-none" />
            </div>}
            <button type="button" className={action} onClick={()=>void load()} disabled={loading}><RefreshCw size={15}/> Actualizar</button>
            <button type="button" className={action} onClick={exportCsv} disabled={section === 'overview' || !filtered.length}><Download size={15}/> CSV</button>
            <button type="button" className={action} onClick={()=>window.print()}><Printer size={15}/> Imprimir / PDF</button>
            {(section === 'plans' || section === 'subjects') && <button type="button" className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700" onClick={()=>setForm(section === 'plans' ? 'plan' : 'subject')}><Plus size={15}/> {section==='plans'?'Nuevo plan':'Nueva materia'}</button>}
            {section==='teachers' && data.canManageTeachers && <button type="button" className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700" onClick={()=>setForm('teacher')}><Plus size={15}/> Nuevo docente</button>}
            {section==='plans' && <button type="button" className={action} onClick={()=>setForm('link')}><Link2 size={15}/> Vincular materia</button>}
        </div>
        {error && <div role="alert" className="rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300">{error}</div>}
        {message && <div role="status" className="rounded-lg border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300">{message}</div>}

{section === 'plans' && (
    <section className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <div className="mb-4">
            <h2 className="font-semibold">
                Editor curricular por grado o semestre
            </h2>
            <p className="mt-1 text-sm text-slate-500">
                Selecciona un plan escolar, revisa las materias
                de cada grado y agrega las que correspondan.
                Las asignaciones docentes permanecen intactas.
            </p>
        </div>

        <label className="block max-w-xl text-sm font-medium">
            Plan de la escuela
            <select
                className={`${input} mt-1`}
                value={editingPlanId}
                onChange={event => {
                    setEditingPlanId(event.target.value);
                    setEditingGrade('1');
                    setEditingSubjectId('');
                }}
            >
                <option value="">
                    Seleccionar plan para configurar
                </option>

                {data.plans.map(plan => (
                    <option key={plan.id} value={plan.id}>
                        {plan.name} · {plan.level_name} · {plan.cycle_name}
                    </option>
                ))}
            </select>
        </label>

        {editingPlan && (
            <>
                <div
                    className="mt-4 flex flex-wrap gap-2"
                    role="group"
                    aria-label="Grados del plan"
                >
                    {editingGrades.map(grade => (
                        <button
                            key={grade}
                            type="button"
                            aria-pressed={editingGrade === grade}
                            onClick={() => {
                                setEditingGrade(grade);
                                setEditingSubjectId('');
                            }}
                            className={
                                editingGrade === grade
                                    ? 'rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white'
                                    : action
                            }
                        >
                            {gradeCaption(
                                editingPlan.level_name,
                                grade,
                            )}
                        </button>
                    ))}

                    <button
                        type="button"
                        aria-pressed={editingGrade === 'all'}
                        onClick={() => {
                            setEditingGrade('all');
                            setEditingSubjectId('');
                        }}
                        className={
                            editingGrade === 'all'
                                ? 'rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white'
                                : action
                        }
                    >
                        Todos los grados
                    </button>
                </div>

                {editingGrades.length === 0 && (
                    <p className="mt-3 text-sm text-amber-600">
                        Este nivel requiere definir su estructura
                        de periodos antes de configurar materias.
                    </p>
                )}

                {(editingGrades.length > 0 ||
                    editingGrade === 'all') && (
                    <div className="mt-4 grid gap-4 lg:grid-cols-2">

                        {/* MATERIAS DEL GRADO */}

                        <div className="rounded-lg border border-slate-200 p-4 dark:border-slate-700">
                            <h3 className="font-semibold">
                                Materias ·{' '}
                                {gradeCaption(
                                    editingPlan.level_name,
                                    editingGrade,
                                )}
                            </h3>

                            <p className="mt-1 text-xs text-slate-500">
                                {editingLinks.length} materias
                                vinculadas a este ámbito.
                            </p>

                            <div className="mt-3 divide-y divide-slate-200 dark:divide-slate-700">
                                {editingLinks.map(link => (
                                    <div
                                        key={link.id}
                                        className="flex items-center justify-between gap-2 py-2 text-sm"
                                    >
                                        <span>
                                            {link.subject_name}
                                        </span>

                                        <span className="text-xs text-slate-500">
                                            {Boolean(link.affects_average)
                                                ? 'Promedia'
                                                : 'No promedia'}
                                        </span>
                                    </div>
                                ))}

                                {editingLinks.length === 0 && (
                                    <p className="py-4 text-sm text-slate-500">
                                        Todavía no hay materias
                                        en este grado.
                                    </p>
                                )}
                            </div>

                            {generalLinks.length > 0 && (
                                <div className="mt-4 border-t border-slate-200 pt-3 dark:border-slate-700">
                                    <p className="text-xs font-semibold text-slate-500">
                                        Materias generales que también
                                        aplican a este grado
                                    </p>

                                    <p className="mt-2 text-sm">
                                        {generalLinks
                                            .map(link => link.subject_name)
                                            .join(' · ')}
                                    </p>
                                </div>
                            )}
                        </div>

                        {/* VINCULAR MATERIA */}

                        <div className="rounded-lg border border-slate-200 p-4 dark:border-slate-700">
                            <h3 className="font-semibold">
                                Agregar materia al plan
                            </h3>

                            <p className="mt-1 text-xs text-slate-500">
                                Ámbito:{' '}
                                {gradeCaption(
                                    editingPlan.level_name,
                                    editingGrade,
                                )}
                            </p>

                            <label className="mt-3 block text-sm">
                                Materia disponible

                                <select
                                    className={`${input} mt-1`}
                                    value={editingSubjectId}
                                    onChange={event =>
                                        setEditingSubjectId(
                                            event.target.value,
                                        )
                                    }
                                >
                                    <option value="">
                                        Seleccionar materia
                                    </option>

                                    {editingAvailableSubjects.map(
                                        subject => (
                                            <option
                                                key={subject.id}
                                                value={subject.id}
                                            >
                                                {subject.name}
                                            </option>
                                        ),
                                    )}
                                </select>
                            </label>

                            <label className="mt-3 flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    checked={editingAverage}
                                    onChange={event =>
                                        setEditingAverage(
                                            event.target.checked,
                                        )
                                    }
                                />
                                Participa en promedio institucional
                            </label>

                            <label className="mt-2 flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    checked={editingOfficial}
                                    onChange={event =>
                                        setEditingOfficial(
                                            event.target.checked,
                                        )
                                    }
                                />
                                Referenciada a contenido oficial
                            </label>

                            <p className="mt-2 text-xs text-slate-500">
                                Esta marca es informativa y no
                                certifica reconocimiento oficial SEP.
                            </p>

                            <button
                                type="button"
                                onClick={() => void attachToGrade()}
                                disabled={
                                    saving ||
                                    !editingSubjectId ||
                                    (
                                        editingGrades.length === 0 &&
                                        editingGrade !== 'all'
                                    )
                                }
                                className="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-40"
                            >
                                <Plus size={16} />

                                {saving
                                    ? 'Guardando...'
                                    : 'Agregar al grado'}
                            </button>
                        </div>
                    </div>
                )}
            </>
        )}
    </section>
)}

        {resetTeacher && <div className="fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/70 p-3" role="presentation">
            <form role="dialog" aria-modal="true" aria-label="Restablecer acceso docente" onSubmit={async e=>{e.preventDefault();const ok=await save(`${ROOT}/teachers/${resetTeacher.id}/password`,{username:teacherUsername,password:teacherPassword,password_confirmation:teacherPasswordConfirmation});if(ok){setResetTeacher(null);setTeacherPassword('');setTeacherPasswordConfirmation('');}}} className="w-full max-w-lg space-y-3 rounded-xl border border-slate-700 bg-slate-900 p-5 text-slate-100">
                <h2 className="text-lg font-semibold">Restablecer acceso: {resetTeacher.name}</h2>{error && <p role="alert" className="text-sm text-red-300">{error}</p>}
                <label className="block text-sm">Usuario de acceso<input className={input} minLength={4} required pattern="[a-zA-Z0-9._-]+" value={teacherUsername} onChange={e=>setTeacherUsername(e.target.value.toLowerCase())}/></label>
                <label className="block text-sm">Nueva contraseña inicial<input type="password" className={input} minLength={12} required autoComplete="new-password" value={teacherPassword} onChange={e=>setTeacherPassword(e.target.value)}/></label>
                <label className="block text-sm">Confirmar contraseña<input type="password" className={input} minLength={12} required autoComplete="new-password" value={teacherPasswordConfirmation} onChange={e=>setTeacherPasswordConfirmation(e.target.value)}/></label>
                <div className="flex justify-end gap-2"><button type="button" className={action} onClick={()=>setResetTeacher(null)}>Cancelar</button><button type="submit" className="rounded-md bg-blue-600 px-4 py-2 text-sm" disabled={saving}>Guardar credenciales</button></div>
            </form>
        </div>}
        {form && <div className="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/70 p-3 backdrop-blur-[2px]" role="presentation" onMouseDown={e=>{ if(e.target===e.currentTarget && !saving) setForm(null); }}>
        <form role="dialog" aria-modal="true" aria-label="Formulario de Academic" onSubmit={e=>{
            e.preventDefault();
            if(form==='plan') void save(`${ROOT}/plans`,{ name:planName, academic_cycle_id:Number(cycle), academic_level_id:Number(level), framework_code:framework||null });
            else if(form==='teacher') void save(`${ROOT}/teachers`, {name:teacherName, username:teacherUsername, email:teacherEmail||null, employee_code:employeeCode||null, password:teacherPassword, password_confirmation:teacherPasswordConfirmation});
            else if(form==='subject') void save(`${ROOT}/subjects`,{name:subjectName,code:subjectCode,kind:subjectKind, curriculum_plan_id:subjectPlan?Number(subjectPlan):null, grade_label:subjectPlan?subjectGrade||null:null, is_official:subjectIsOfficial, affects_average:subjectAffectsAverage});

else if (form === 'link') {
    if (!chosenPlan || !chosenSubject || !grade) {
        setError('Selecciona plan, materia y grado.');
        return;
    }

    void save(`${ROOT}/plans/${chosenPlan}/subjects`, {
        subject_id: Number(chosenSubject),
        grade_label: grade === 'all' ? null : grade,
        is_official: isOfficial,
        affects_average: affectsAverage,
    });
}

        }} className="max-h-[88vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
            <div className="mb-3 flex items-center justify-between"><h2 className="font-semibold">{form==='plan'?'Registrar plan curricular':form==='subject'?'Registrar materia':form==='teacher'?'Registrar docente y crear cuenta':'Vincular materia al plan'}</h2><button type="button" aria-label="Cerrar modal" className="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" onClick={()=>setForm(null)} disabled={saving}><X size={18}/></button></div>
            <div className="grid gap-3 md:grid-cols-2">
                {form==='plan' && <>
                    <label className="text-sm">Nombre del plan<input className={input} required maxLength={160} value={planName} onChange={e=>setPlanName(e.target.value)} placeholder="Plan institucional 2026-2027" /></label>
                    <label className="text-sm">Ciclo escolar<select className={input} required value={cycle} onChange={e=>setCycle(e.target.value)}><option value="">Seleccionar</option>{data.cycles.map(v=><option key={v.id} value={v.id}>{v.name}</option>)}</select></label>
                    <label className="text-sm">Nivel<select className={input} required value={level} onChange={e=>setLevel(e.target.value)}><option value="">Seleccionar</option>{data.levels.map(v=><option key={v.id} value={v.id}>{v.name}</option>)}</select></label>
                    <label className="text-sm">Marco curricular<select className={input} value={framework} onChange={e=>setFramework(e.target.value)}><option value="sep_2022">SEP / NEM 2022 (referencia)</option><option value="custom">Personalizado</option><option value="mixed">Mixto SEP + propio</option></select></label>
                </>}
                {form==='subject' && <>
                    <label className="text-sm">Nombre de la materia<input className={input} required maxLength={160} value={subjectName} onChange={e=>setSubjectName(e.target.value)} placeholder="Educación financiera" /></label>
                    <label className="text-sm">Código técnico<input className={input} required maxLength={80} pattern="[a-z0-9][a-z0-9_-]*" value={subjectCode} onChange={e=>setSubjectCode(e.target.value.toLowerCase().replaceAll(' ','_'))} placeholder="educacion_financiera" /></label>
                    <label className="text-sm">Origen<select className={input} value={subjectKind} onChange={e=>setSubjectKind(e.target.value as 'official'|'custom')}><option value="custom">Propia de la escuela</option><option value="official">Referenciada a marco oficial</option></select></label>
                    <label className="text-sm">Agregar inmediatamente a un plan (opcional)<select className={input} value={subjectPlan} onChange={e=>setSubjectPlan(e.target.value)}><option value="">Solo registrar en el catálogo escolar</option>{data.plans.map(p=><option key={p.id} value={p.id}>{p.name} · {p.level_name}</option>)}</select></label>
                    {subjectPlan && <>
                        <label className="text-sm">Grado (opcional)<input className={input} maxLength={100} value={subjectGrade} onChange={e=>setSubjectGrade(e.target.value)} placeholder="1, 2, 3..." /></label>
                        <div className="col-span-full flex flex-wrap gap-5 rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                            <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={subjectIsOfficial} onChange={e=>setSubjectIsOfficial(e.target.checked)} /> Referencia oficial en este plan</label>
                            <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={subjectAffectsAverage} onChange={e=>setSubjectAffectsAverage(e.target.checked)} /> Participa en promedio institucional</label>
                        </div>
                    </>}
                    <p className="col-span-full text-xs text-slate-500">Las materias propias quedan disponibles para todos los planes de esta escuela. No se publican automáticamente en el catálogo maestro global.</p>
                </>}
                {form==='teacher' && <>
                    <label className="text-sm">Nombre completo<input className={input} required maxLength={150} value={teacherName} onChange={e=>setTeacherName(e.target.value)} placeholder="Nombre y apellidos" /></label>
                    <label className="text-sm">Correo real (opcional)<input className={input} type="email" maxLength={255} value={teacherEmail} onChange={e=>setTeacherEmail(e.target.value)} placeholder="docente@escuela.mx" /></label>
                    <label className="text-sm">Clave de empleado (opcional)<input className={input} maxLength={60} value={employeeCode} onChange={e=>setEmployeeCode(e.target.value)} /></label>
                    <label className="text-sm">Usuario de acceso<input className={input} required minLength={4} maxLength={60} pattern="[a-zA-Z0-9._-]+" autoComplete="off" value={teacherUsername} onChange={e=>setTeacherUsername(e.target.value.toLowerCase())} placeholder="docente001" /></label>
                    <label className="text-sm">Contraseña inicial<input className={input} type="password" required minLength={12} maxLength={128} autoComplete="new-password" value={teacherPassword} onChange={e=>setTeacherPassword(e.target.value)} /></label>
                    <label className="text-sm">Confirmar contraseña<input className={input} type="password" required minLength={12} maxLength={128} autoComplete="new-password" value={teacherPasswordConfirmation} onChange={e=>setTeacherPasswordConfirmation(e.target.value)} /></label>
                    <p className="col-span-full text-xs text-slate-500">Dirección entrega las credenciales por un canal privado. No se envía correo. El cambio de contraseña es obligatorio al primer ingreso.</p>
                </>}
                {form==='link' && <>
                    <label className="text-sm">Plan<select className={input} required value={chosenPlan} onChange={e=>setChosenPlan(e.target.value)}><option value="">Seleccionar</option>{data.plans.map(v=><option key={v.id} value={v.id}>{v.name} · {v.level_name}</option>)}</select></label>
                    <label className="text-sm">Materia<select className={input} required value={chosenSubject} onChange={e=>setChosenSubject(e.target.value)}><option value="">Seleccionar</option>{data.subjects.filter(s=>s.status==='active').map(v=><option key={v.id} value={v.id}>{v.name}</option>)}</select></label>

<label className="text-sm">
    Grado o semestre del plan

    <select
        className={input}
        required
        value={grade}
        onChange={event => setGrade(event.target.value)}
    >
        <option value="">Seleccionar grado</option>

        {levelsGrades(
            data.plans.find(
                plan => String(plan.id) === chosenPlan,
            )?.level_name ?? '',
        ).map(g => (
            <option key={g} value={g}>
                {gradeCaption(
                    data.plans.find(
                        plan => String(plan.id) === chosenPlan,
                    )?.level_name ?? '',
                    g,
                )}
            </option>
        ))}

        <option value="all">Todos los grados</option>
    </select>
</label>
                    <div className="space-y-2"><label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={isOfficial} onChange={e=>setIsOfficial(e.target.checked)}/> Marcada como contenido oficial del plan</label><label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={affectsAverage} onChange={e=>setAffectsAverage(e.target.checked)}/> Participa en promedio institucional</label></div>
                </>}
            </div>
            <div className="mt-5 flex justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-700">
                <button type="button" className={action} onClick={()=>setForm(null)} disabled={saving}>Cancelar</button>
                <button type="submit" disabled={saving} className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">{saving?'Guardando...':'Guardar'}</button>
            </div>
        </form></div>}
        {section === 'overview' && <>
            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                {([
                    ['Planes',data.summary?.plans ?? data.plans.length,BookOpen],
                    ['Materias',data.summary?.subjects ?? data.subjects.filter(x=>x.status==='active').length,ClipboardList],
                    ['Docentes',data.summary?.teachers ?? data.teachers.filter(x=>x.status==='active').length,GraduationCap],
                    ['Asignaciones',data.summary?.assignments ?? 0,Layers3],
                    ['Grupos',data.summary?.groups ?? 0,UsersRound],
                ] as const).map(([label,value,Icon])=><div key={label} className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900"><div className="flex items-center justify-between"><span className="text-xs text-slate-500">{label}</span><Icon size={17} className="text-blue-600"/></div><div className="mt-3 text-2xl font-semibold">{loading?'—':value}</div></div>)}
            </div>
            <div className="grid gap-3 lg:grid-cols-2">
                <section className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900"><div className="mb-3 flex items-center justify-between"><h2 className="font-semibold">Planes curriculares</h2><button className={action} onClick={()=>onSectionChange?.('plans')}>Abrir planes</button></div>{data.plans.slice(0,5).map(p=><div className="border-t border-slate-100 py-2 text-sm dark:border-slate-800" key={p.id}>{p.name}<span className="ml-2 text-xs text-slate-500">{p.level_name} · {p.cycle_name}</span></div>)}{!data.plans.length && <p className="text-sm text-slate-500">Sin planes registrados.</p>}</section>
                <section className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900"><div className="mb-3 flex items-center justify-between"><h2 className="font-semibold">Docentes</h2><button className={action} onClick={()=>onSectionChange?.('teachers')}>Administrar docentes</button></div>{data.teachers.slice(0,5).map(t=><div className="border-t border-slate-100 py-2 text-sm dark:border-slate-800" key={t.id}>{t.name}<span className="ml-2 text-xs text-slate-500">{t.email}</span></div>)}{!data.teachers.length && <p className="text-sm text-slate-500">Sin perfiles docentes. Regístralos desde Docentes.</p>}</section>
            </div>
            <div className="rounded-lg border border-blue-200 bg-blue-50 p-3 text-xs text-blue-900 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-200">Academic administra planes, materias y docentes. Alumnos, inscripciones y familias permanecen en SchoolCore. La captura de calificaciones se desarrollará en el portal docente.</div>
        </>}
        {section !== 'overview' && <div className="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <div className="flex items-center justify-between border-b border-slate-200 px-4 py-3 dark:border-slate-800"><h2 className="font-semibold">{title}</h2><span className="text-xs text-slate-500">{filtered.length} registros</span></div>
            {loading ? <p className="p-6 text-sm text-slate-500">Consultando datos...</p> : filtered.length === 0 ? <p className="p-6 text-sm text-slate-500">Sin registros. {section==='teachers'?'Todavía no hay docentes registrados. Dirección puede utilizar Nuevo docente.':'Puedes registrar el primero desde la barra de herramientas.'}</p> :
                <div className="divide-y divide-slate-100 dark:divide-slate-800">{section==='plans' ? data.plans.filter(v=>filtered.some(f=>f.id===v.id)).map(v=><div key={v.id} className="p-4"><div className="flex flex-wrap items-center justify-between gap-2"><div><strong>{v.name}</strong><p className="text-xs text-slate-500">{v.level_name} · {v.cycle_name} · {v.framework_code??'Sin marco'} · v{v.version}</p></div><span className="rounded-full bg-slate-100 px-2 py-1 text-xs text-slate-700 dark:bg-slate-800 dark:text-slate-200">{v.status}</span></div><div className="mt-3 flex flex-wrap gap-2">{data.links.filter(x=>x.curriculum_plan_id===v.id).map(x=><span key={x.id} className="inline-flex items-center rounded-md border border-slate-200 bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">{x.subject_name}{x.grade_label?` · ${x.grade_label}`:''}</span>)}{!data.links.some(x=>x.curriculum_plan_id===v.id)&&<span className="text-xs text-slate-400">Sin materias vinculadas</span>}</div></div>) : section==='subjects' ? data.subjects.filter(v=>filtered.some(f=>f.id===v.id)).map(v=><div key={v.id} className="flex items-center justify-between p-4"><div><strong>{v.name}</strong><p className="text-xs text-slate-500">{v.code} · {v.kind==='custom'?'Propia':'Referenciada a oficial'}</p></div><span className="text-xs text-slate-500">{v.status}</span></div>) : data.teachers.filter(v=>filtered.some(f=>f.id===v.id)).map(v=><div key={v.id} className="flex items-center justify-between p-4"><div><strong>{v.name}</strong><p className="text-xs text-slate-500">Usuario: {v.username??'Por definir'} · {v.email.endsWith('@accounts.invalid')?'Sin correo real':v.email} · {v.employee_code??'Sin clave'}</p></div><div className="flex items-center gap-3"><span className="text-xs text-slate-500">{v.status}</span><button type="button" className={action} onClick={()=>{setResetTeacher(v);setTeacherUsername(v.username??'');setTeacherPassword('');setTeacherPasswordConfirmation('');}}>Restablecer acceso</button></div></div> )}</div>}
        </div>}
        <div className="flex items-start gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300"><CircleHelp size={16} className="shrink-0"/><span>Los planes SEP son una referencia adaptable. Cada escuela agrega materias propias y define su asignación por grado. Marcar una materia como oficial no certifica su validez ante la autoridad. El alta docente crea una cuenta y perfil académico vinculados; el acceso al portal docente se habilitará en una fase posterior.</span></div>
    </div>;
}
