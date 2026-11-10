import { useState } from 'react';
import {
  ArrowLeft, BookOpen, ClipboardList, GraduationCap, LayoutDashboard,
  School, UsersRound, type LucideIcon,
} from 'lucide-react';

type AcademicTab = 'overview' | 'subjects' | 'teachers' | 'grades' | 'reports';
type Item = { id: AcademicTab; label: string; icon: LucideIcon; description: string };

const items: Item[] = [
  { id: 'overview', label: 'Resumen académico', icon: LayoutDashboard, description: 'Punto de entrada al módulo Academic.' },
  { id: 'subjects', label: 'Materias y planes', icon: BookOpen, description: 'Materias, programas y asignaciones por grado.' },
  { id: 'teachers', label: 'Docentes', icon: UsersRound, description: 'Profesores y sus asignaciones académicas.' },
  { id: 'grades', label: 'Calificaciones', icon: GraduationCap, description: 'Evaluaciones, capturas y boletas.' },
  { id: 'reports', label: 'Reportes académicos', icon: ClipboardList, description: 'Consulta y exportación de resultados académicos.' },
];

export default function AcademicWorkspace({
  schoolName,
  onBack,
}: {
  schoolName: string;
  onBack: () => void;
}) {
  const [tab, setTab] = useState<AcademicTab>('overview');
  const current = items.find((item) => item.id === tab) ?? items[0];
  const Icon = current.icon;

  return (
    <section className="space-y-5" aria-label="Módulo Academic">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <p className="text-xs font-semibold uppercase tracking-widest text-blue-600 dark:text-blue-400">SchoolCore / Academic</p>
          <h1 className="mt-1 text-2xl font-semibold">Gestión académica</h1>
          <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{schoolName}</p>
        </div>
        <button type="button" onClick={onBack} className="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-800">
          <ArrowLeft size={16} /> Volver a SchoolCore
        </button>
      </div>

      <div className="grid gap-4 lg:grid-cols-[240px_minmax(0,1fr)]">
        <aside className="self-start rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900" aria-label="Herramientas Academic">
          <p className="px-2 pb-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Herramientas Academic</p>
          <nav className="space-y-1">
            {items.map((item) => {
              const TabIcon = item.icon;
              const selected = tab === item.id;
              return (
                <button key={item.id} type="button" onClick={() => setTab(item.id)} aria-current={selected ? 'page' : undefined}
                  className={`flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm transition-colors ${selected ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'}`}>
                  <TabIcon size={18} /><span>{item.label}</span>
                </button>
              );
            })}
          </nav>
        </aside>

        <div className="min-w-0 space-y-4">
          <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
            <div className="mb-3 flex size-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950 dark:text-blue-300"><Icon size={22} /></div>
            <h2 className="text-xl font-semibold">{current.label}</h2>
            <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">{current.description}</p>
          </div>
          {tab === 'overview' ? (
            <div className="grid gap-3 sm:grid-cols-2">
              {items.filter((item) => item.id !== 'overview').map((item) => {
                const ItemIcon = item.icon;
                return (
                  <button key={item.id} type="button" onClick={() => setTab(item.id)} className="rounded-xl border border-slate-200 bg-white p-4 text-left hover:border-blue-500 hover:shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:hover:border-blue-500">
                    <ItemIcon size={21} className="text-blue-600" />
                    <h3 className="mt-3 font-semibold">{item.label}</h3>
                    <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">{item.description}</p>
                  </button>
                );
              })}
            </div>
          ) : (
            <div className="rounded-xl border border-dashed border-slate-300 p-6 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
              <div className="flex items-center gap-2 font-semibold text-slate-700 dark:text-slate-200"><School size={18} /> Área de {current.label.toLowerCase()}</div>
              <p className="mt-2">Navegación habilitada. Para consultar o modificar registros se conectarán aquí las pantallas y endpoints académicos existentes, con autorización del servidor. No se simulan ni guardan datos.</p>
            </div>
          )}
        </div>
      </div>
    </section>
  );
}
