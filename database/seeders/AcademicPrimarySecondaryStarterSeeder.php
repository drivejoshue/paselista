<?php
declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class AcademicPrimarySecondaryStarterSeeder extends Seeder
{
    public function run(): void
    {
        // Escuela demo. No ejecutar sobre otras escuelas
        // sin convertirlo antes en un comando parametrizado.
        $schoolId = 1;

        DB::transaction(function () use ($schoolId): void {
            $cycle = DB::table('academic_cycles')
                ->where('school_id', $schoolId)
                ->where('is_active', 1)
                ->first(['id']);

            if (!$cycle) {
                throw new RuntimeException(
                    'La escuela no tiene ciclo académico activo.'
                );
            }

            $levels = DB::table('academic_levels')
                ->where('school_id', $schoolId)
                ->whereIn('name', ['Primaria', 'Secundaria'])
                ->where('status', 'active')
                ->get()
                ->keyBy('name');

            foreach (['Primaria', 'Secundaria'] as $name) {
                if (!$levels->has($name)) {
                    throw new RuntimeException(
                        "Falta el nivel académico {$name}."
                    );
                }
            }

            $primaryPlanId = $this->plan(
                $schoolId,
                (int) $cycle->id,
                (int) $levels['Primaria']->id,
                'Primaria NEM 2022 - Ciclo '.$cycle->id
            );

            $secondaryPlanId = $this->plan(
                $schoolId,
                (int) $cycle->id,
                (int) $levels['Secundaria']->id,
                'Secundaria NEM 2022 - Ciclo '.$cycle->id
            );

            $fields = [
                'LENGUAJES' =>
                    'Lenguajes',
                'SABERES' =>
                    'Saberes y Pensamiento Científico',
                'ETICA' =>
                    'Ética, Naturaleza y Sociedades',
                'HUMANO' =>
                    'De lo Humano y lo Comunitario',
            ];

            foreach ($fields as $code => $name) {
                $subjectId = $this->subject(
                    $schoolId,
                    'NEM-CAMPO-'.$code,
                    $name,
                    'official'
                );

                foreach (range(1, 6) as $grade) {
                    $this->attach(
                        $schoolId,
                        $primaryPlanId,
                        $subjectId,
                        (string) $grade,
                        $grade
                    );
                }
            }

            // Organización disciplinar de referencia
            // para Secundaria, Fase 6.
            $disciplines = [
                ['ESPANOL', 'Español', [1, 2, 3]],
                ['INGLES', 'Inglés', [1, 2, 3]],
                ['ARTES', 'Artes', [1, 2, 3]],
                ['MATEMATICAS', 'Matemáticas', [1, 2, 3]],
                ['BIOLOGIA', 'Biología', [1]],
                ['FISICA', 'Física', [2]],
                ['QUIMICA', 'Química', [3]],
                ['GEOGRAFIA', 'Geografía', [1]],
                ['HISTORIA', 'Historia', [1, 2, 3]],
                ['CIVICA', 'Formación Cívica y Ética', [1, 2, 3]],
                ['TECNOLOGIA', 'Tecnología', [1, 2, 3]],
                ['ED_FISICA', 'Educación Física', [1, 2, 3]],
                ['TUTORIA', 'Tutoría / Educación socioemocional', [1, 2, 3]],
            ];

            $order = 0;

            foreach ($disciplines as [$code, $name, $grades]) {
                $subjectId = $this->subject(
                    $schoolId,
                    'NEM-SEC-'.$code,
                    $name,
                    'official'
                );

                foreach ($grades as $grade) {
                    $this->attach(
                        $schoolId,
                        $secondaryPlanId,
                        $subjectId,
                        (string) $grade,
                        ++$order
                    );
                }
            }
        });

        $this->command?->info(
            'Referencias curriculares de Primaria y Secundaria verificadas.'
        );
    }

    private function plan(
        int $schoolId,
        int $cycleId,
        int $levelId,
        string $name
    ): int {
        $query = DB::table('academic_curriculum_plans')
            ->where('school_id', $schoolId)
            ->where('academic_cycle_id', $cycleId)
            ->where('academic_level_id', $levelId)
            ->where('framework_code', 'NEM_2022');

        $existing = $query->first(['id']);

        if ($existing) {
            return (int) $existing->id;
        }

        return (int) DB::table('academic_curriculum_plans')
            ->insertGetId([
                'school_id' => $schoolId,
                'academic_cycle_id' => $cycleId,
                'academic_level_id' => $levelId,
                'name' => $name,
                'framework_code' => 'NEM_2022',
                'version' => 1,
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function subject(
        int $schoolId,
        string $code,
        string $name,
        string $kind
    ): int {
        $existing = DB::table('academic_subjects')
            ->where('school_id', $schoolId)
            ->where('code', $code)
            ->first(['id']);

        if ($existing) {
            return (int) $existing->id;
        }

        return (int) DB::table('academic_subjects')
            ->insertGetId([
                'school_id' => $schoolId,
                'code' => $code,
                'name' => $name,
                'kind' => $kind,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function attach(
        int $schoolId,
        int $planId,
        int $subjectId,
        string $grade,
        int $order
    ): void {
        $exists = DB::table('academic_curriculum_plan_subjects')
            ->where('school_id', $schoolId)
            ->where('curriculum_plan_id', $planId)
            ->where('subject_id', $subjectId)
            ->where('grade_label', $grade)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('academic_curriculum_plan_subjects')
            ->insert([
                'school_id' => $schoolId,
                'curriculum_plan_id' => $planId,
                'subject_id' => $subjectId,
                'grade_label' => $grade,
                'is_official' => 1,
                'affects_average' => 0,
                'sort_order' => $order,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
