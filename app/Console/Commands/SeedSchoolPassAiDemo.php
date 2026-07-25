<?php

namespace App\Console\Commands;

use App\Services\Ai\Demo\AiDemoSeederService;
use Illuminate\Console\Command;
use Throwable;

class SeedSchoolPassAiDemo extends Command
{
    protected $signature = 'schoolpass:ai-demo
        {--school=1 : ID de la escuela}
        {--students-per-group=15 : Alumnos ficticios por grupo}
        {--days=75 : Días de historial}
        {--clear : Elimina los datos ficticios}
        {--force : Omite la confirmación interactiva}';

    protected $description =
        'Genera o elimina alumnos y asistencias ficticias para probar SchoolPass IA.';

    public function handle(AiDemoSeederService $service): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Este comando solo puede ejecutarse en local o testing.');
            return self::FAILURE;
        }

        $schoolId = max(1, (int) $this->option('school'));

        try {
            if ($this->option('clear')) {
                if (
                    ! $this->option('force')
                    && ! $this->confirm(
                        'Se eliminarán todos los alumnos IADEMO de la escuela '
                        .$schoolId
                        .'. ¿Continuar?'
                    )
                ) {
                    return self::SUCCESS;
                }

                $result = $service->clear($schoolId);
                $this->info('Datos ficticios eliminados.');
                $this->table(
                    ['Alumnos', 'Asistencias', 'Accesos'],
                    [[
                        $result['students'],
                        $result['attendance_rows'],
                        $result['access_rows'],
                    ]]
                );

                return self::SUCCESS;
            }

            $studentsPerGroup = min(
                60,
                max(1, (int) $this->option('students-per-group'))
            );

            $days = min(180, max(7, (int) $this->option('days')));

            if (
                ! $this->option('force')
                && ! $this->confirm(sprintf(
                    'Se crearán %d alumnos por grupo y %d días de datos ficticios en la escuela %d. ¿Continuar?',
                    $studentsPerGroup,
                    $days,
                    $schoolId
                ))
            ) {
                return self::SUCCESS;
            }

            $this->info('Generando datos ficticios...');

            $result = $service->seed(
                schoolId: $schoolId,
                studentsPerGroup: $studentsPerGroup,
                days: $days
            );

            $this->info('Datos ficticios generados.');
            $this->table(
                ['Grupos', 'Alumnos', 'Asistencias', 'Accesos', 'Desde', 'Hasta'],
                [[
                    $result['groups'],
                    $result['students'],
                    $result['attendance_rows'],
                    $result['access_rows'],
                    $result['period_from'],
                    $result['period_to'],
                ]]
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            report($exception);
            return self::FAILURE;
        }
    }
}
