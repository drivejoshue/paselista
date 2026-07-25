<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AiScopeResolver
{
    public function resolve(
        int $schoolId,
        string $requestedType,
        ?int $requestedId,
        string $question
    ): array {
        if (
            in_array(
                $requestedType,
                ['student', 'group'],
                true
            )
            && $requestedId
        ) {
            return [
                'type' => $requestedType,
                'id' => $requestedId,
                'resolved_automatically' => false,
                'label' => null,
            ];
        }

        if ($requestedType !== 'school') {
            return [
                'type' => $requestedType,
                'id' => $requestedId,
                'resolved_automatically' => false,
                'label' => null,
            ];
        }

        $cycleId = DB::table('academic_cycles')
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->where('status', 'active')
            ->value('id');

        if (! $cycleId) {
            return [
                'type' => 'school',
                'id' => null,
                'resolved_automatically' => false,
                'label' => null,
            ];
        }

        $normalizedQuestion = $this->normalize(
            $question
        );

        $studentMatches = DB::table(
            'student_enrollments as se'
        )
            ->join(
                'students as s',
                's.id',
                '=',
                'se.student_id'
            )
            ->leftJoin(
                'school_groups as sg',
                'sg.id',
                '=',
                'se.school_group_id'
            )
            ->where('se.school_id', $schoolId)
            ->where('se.academic_cycle_id', $cycleId)
            ->where('se.status', 'active')
            ->where('s.status', 'active')
            ->get([
                's.id',
                's.student_code',
                's.first_name',
                's.last_name',
                'sg.name as group_name',
            ])
            ->filter(
                function (object $student) use (
                    $normalizedQuestion
                ): bool {
                    $fullName = $this->normalize(
                        trim(
                            $student->first_name
                            .' '
                            .$student->last_name
                        )
                    );

                    $reverseName = $this->normalize(
                        trim(
                            $student->last_name
                            .' '
                            .$student->first_name
                        )
                    );

                    $code = $this->normalize(
                        (string) $student->student_code
                    );

                    return (
                        mb_strlen($fullName) >= 5
                        && str_contains(
                            $normalizedQuestion,
                            $fullName
                        )
                    ) || (
                        mb_strlen($reverseName) >= 5
                        && str_contains(
                            $normalizedQuestion,
                            $reverseName
                        )
                    ) || (
                        $code !== ''
                        && str_contains(
                            $normalizedQuestion,
                            $code
                        )
                    );
                }
            )
            ->values();

        if ($studentMatches->count() === 1) {
            $student = $studentMatches->first();

            return [
                'type' => 'student',
                'id' => (int) $student->id,
                'resolved_automatically' => true,
                'label' => trim(
                    $student->first_name
                    .' '
                    .$student->last_name
                ),
            ];
        }

        if ($studentMatches->count() > 1) {
            $options = $studentMatches
                ->take(5)
                ->map(
                    fn (object $student): string =>
                        trim(
                            $student->first_name
                            .' '
                            .$student->last_name
                        )
                        .' · '
                        .($student->group_name ?? 'Sin grupo')
                )
                ->implode('; ');

            throw ValidationException::withMessages([
                'scope_id' =>
                    'Encontré varios alumnos posibles: '
                    .$options
                    .'. Selecciona el alumno en Contexto.',
            ]);
        }

        $groupMatches = DB::table(
            'school_groups as sg'
        )
            ->leftJoin(
                'academic_levels as al',
                'al.id',
                '=',
                'sg.academic_level_id'
            )
            ->leftJoin(
                'campuses as c',
                'c.id',
                '=',
                'sg.campus_id'
            )
            ->where('sg.school_id', $schoolId)
            ->where('sg.academic_cycle_id', $cycleId)
            ->where('sg.status', 'active')
            ->get([
                'sg.id',
                'sg.name',
                'al.name as level_name',
                'c.name as campus_name',
            ])
            ->filter(
                function (object $group) use (
                    $normalizedQuestion
                ): bool {
                    $name = $this->normalize(
                        (string) $group->name
                    );

                    $explicit = $this->normalize(
                        'grupo '.$group->name
                    );

                    $fullLabel = $this->normalize(
                        trim(
                            ($group->level_name ?? '')
                            .' '
                            .$group->name
                        )
                    );

                    return (
                        mb_strlen($explicit) >= 7
                        && str_contains(
                            $normalizedQuestion,
                            $explicit
                        )
                    ) || (
                        mb_strlen($fullLabel) >= 7
                        && str_contains(
                            $normalizedQuestion,
                            $fullLabel
                        )
                    ) || (
                        mb_strlen($name) >= 5
                        && str_contains(
                            $normalizedQuestion,
                            $name
                        )
                    );
                }
            )
            ->values();

        if ($groupMatches->count() === 1) {
            $group = $groupMatches->first();

            return [
                'type' => 'group',
                'id' => (int) $group->id,
                'resolved_automatically' => true,
                'label' => trim(
                    ($group->level_name ?? '')
                    .' · '
                    .$group->name,
                    ' ·'
                ),
            ];
        }

        return [
            'type' => 'school',
            'id' => null,
            'resolved_automatically' => false,
            'label' => null,
        ];
    }

    private function normalize(
        string $value
    ): string {
        return trim(
            preg_replace(
                '/\s+/u',
                ' ',
                Str::lower(
                    Str::ascii($value)
                )
            )
        );
    }
}
