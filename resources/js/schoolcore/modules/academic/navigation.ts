import {
    BookOpen, BookOpenCheck, CalendarRange, ChartColumn,
    ClipboardPenLine, FileText, GraduationCap, LayoutDashboard,
    LibraryBig, UserRoundCheck,
    type LucideIcon,
} from 'lucide-react';
import type { AcademicSectionId } from './types';

export type AcademicNavigationItem = {
    id: AcademicSectionId;
    label: string;
    icon: LucideIcon;
    group: 'general' | 'configuration' | 'operation' | 'output' | 'support';
};

/** Fuente única de los accesos para la sidebar principal del ERP. */
export const academicNavigation: AcademicNavigationItem[] = [
    { id: 'overview', label: 'Resumen académico', icon: LayoutDashboard, group: 'general' },
    { id: 'curriculum', label: 'Planes y programas', icon: BookOpenCheck, group: 'configuration' },
    { id: 'subjects', label: 'Materias y campos', icon: LibraryBig, group: 'configuration' },
    { id: 'teachers', label: 'Docentes', icon: GraduationCap, group: 'configuration' },
    { id: 'assignments', label: 'Asignaciones docentes', icon: UserRoundCheck, group: 'operation' },
    { id: 'periods', label: 'Períodos de evaluación', icon: CalendarRange, group: 'operation' },
    { id: 'grades', label: 'Calificaciones', icon: ClipboardPenLine, group: 'operation' },
    { id: 'report-cards', label: 'Boletas y documentos', icon: FileText, group: 'output' },
    { id: 'reports', label: 'Reportes académicos', icon: ChartColumn, group: 'output' },
    { id: 'help', label: 'Ayuda y normativa', icon: BookOpen, group: 'support' },
];

export const academicGroups: Record<AcademicNavigationItem['group'], string> = {
    general: 'GENERAL',
    configuration: 'CONFIGURACIÓN ACADÉMICA',
    operation: 'OPERACIÓN',
    output: 'DOCUMENTOS Y REPORTES',
    support: 'SOPORTE',
};

export function academicLabel(id: AcademicSectionId): string {
    return academicNavigation.find(item => item.id === id)?.label ?? 'Academic';
}
