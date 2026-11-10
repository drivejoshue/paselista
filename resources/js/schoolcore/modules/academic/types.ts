export const academicSectionIds = [
    'overview',
    'curriculum',
    'subjects',
    'teachers',
    'assignments',
    'periods',
    'grades',
    'report-cards',
    'reports',
    'help',
] as const;

export type AcademicSectionId = (typeof academicSectionIds)[number];

export type AcademicRecord = {
    id: string | number;
    name: string;
    description?: string | null;
    level?: string | null;
    grade?: string | null;
    group?: string | null;
    teacher?: string | null;
    subject?: string | null;
    period?: string | null;
    status?: string | null;
    updatedAt?: string | null;
};

/** Datos de lectura suministrados por Laravel. No se inventan registros desde React. */
export type AcademicBootstrap = {
    school: { id: number; name: string };
    cycle?: { id: number; name: string } | null;
    /** Solo el backend decide si Academic está habilitado. */
    enabled: boolean;
    records?: Partial<Record<AcademicSectionId, AcademicRecord[]>>;
    counts?: Partial<Record<AcademicSectionId, number>>;
    /** Acciones permitidas por el backend para el usuario actual. */
    permissions?: {
        manageCurriculum?: boolean;
        manageAssignments?: boolean;
        captureGrades?: boolean;
        publishGrades?: boolean;
        downloadReports?: boolean;
    };
};
