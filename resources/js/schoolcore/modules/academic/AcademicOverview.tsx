import AcademicManagement from './AcademicManagement';
import AcademicWorkflow from './AcademicWorkflow';

import {
    BookOpen,
    CalendarRange,
    ClipboardCheck,
    GraduationCap,
} from 'lucide-react';

export type AcademicData = {
    cycle: {
        id: number;
        name: string;
    } | null;

    stats: {
        teachers: number;
        subjects: number;
        assignments: number;
        periods: number;
    };

    subjects: {
        id: number;
        name: string;
        code: string;
        kind: string;
    }[];
};

type Props = {
    data: AcademicData | null;
};

export default function AcademicOverview({ data }: Props) {
    if (!data) {
        return (
            <div className="rounded-xl border p-5 text-sm">
                No se pudo cargar la información académica.
            </div>
        );
    }

    const stats = [
        {
            label: 'Docentes',
            value: data.stats.teachers,
            icon: GraduationCap,
        },
        {
            label: 'Materias',
            value: data.stats.subjects,
            icon: BookOpen,
        },
        {
            label: 'Asignaciones',
            value: data.stats.assignments,
            icon: ClipboardCheck,
        },
        {
            label: 'Períodos',
            value: data.stats.periods,
            icon: CalendarRange,
        },
    ];

    return (
        <div className="space-y-5">
            <div>
                <div className="text-xs font-semibold uppercase tracking-wider text-blue-600">
                    SchoolCore / Academic
                </div>

                <h1 className="mt-1 text-2xl font-semibold tracking-tight">
                    Gestión académica
                </h1>

                <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    {data.cycle
                        ? `Ciclo escolar ${data.cycle.name}`
                        : 'No hay un ciclo escolar activo'}
                </p>
            </div>

            <div className="grid grid-cols-2 gap-3 xl:grid-cols-4">
                {stats.map((stat) => {
                    const Icon = stat.icon;

                    return (
                        <article
                            key={stat.label}
                            className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900"
                        >
                            <div className="flex items-center justify-between">
                                <span className="text-xs text-slate-500 dark:text-slate-400">
                                    {stat.label}
                                </span>
                                <Icon size={19} className="text-blue-500" />
                            </div>

                            <div className="mt-3 text-2xl font-semibold">
                                {stat.value}
                            </div>
                        </article>
                    );
                })}
            </div>

            <section className="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                <div className="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <h2 className="font-semibold">Materias registradas</h2>
                    <p className="text-xs text-slate-500">
                        Catálogo de la institución
                    </p>
                </div>

                {data.subjects.length === 0 ? (
                    <div className="px-5 py-10 text-center text-sm text-slate-500">
                        Todavía no hay materias registradas.
                    </div>
                ) : (
                    <div className="divide-y divide-slate-100 dark:divide-slate-800">
                        {data.subjects.map((subject) => (
                            <div
                                key={subject.id}
                                className="flex items-center justify-between gap-3 px-5 py-3"
                            >
                                <div>
                                    <div className="text-sm font-medium">
                                        {subject.name}
                                    </div>
                                    <div className="text-xs text-slate-500">
                                        {subject.code}
                                    </div>
                                </div>

                                <span className="rounded-md bg-slate-100 px-2 py-1 text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    {subject.kind === 'official'
                                        ? 'Oficial'
                                        : 'Complementaria'}
                                </span>
                            </div>
                        ))}
                    </div>
                )}
                       </section>

            {/* Administración de docentes, materias y períodos */}
            <AcademicManagement />

            {/* Planes, asignaciones y calificaciones */}
            <AcademicWorkflow />

        </div>
    );
}