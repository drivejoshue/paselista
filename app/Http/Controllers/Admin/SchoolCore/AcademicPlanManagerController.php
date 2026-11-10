<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\SchoolCore;

use App\Http\Controllers\Controller;
use App\Services\SchoolCore\ModuleAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class AcademicPlanManagerController extends Controller
{
    public function __construct(
        private readonly ModuleAccessService $modules
    ) {}

    private function school(Request $request): int
    {
        $user = $request->user();
        $school = (int) ($user?->school_id ?? 0);

        abort_if($school < 1, 403);
        abort_unless(
            in_array(
                (string) $user?->role,
                ['director', 'school_admin'],
                true
            ),
            403
        );
        abort_unless(
            $this->modules->canAccess($school, 'academic'),
            403
        );

        return $school;
    }

    /**
     * Biblioteca de referencias compartida.
     * No depende de un tenant ni modifica planes escolares.
     */
    private function templates(): array
    {
        $secondary = [
            ['Español', 'NEM-SEC-ESPANOL', [1, 2, 3]],
            ['Inglés', 'NEM-SEC-INGLES', [1, 2, 3]],
            ['Artes', 'NEM-SEC-ARTES', [1, 2, 3]],
            ['Matemáticas', 'NEM-SEC-MATEMATICAS', [1, 2, 3]],
            ['Biología', 'NEM-SEC-BIOLOGIA', [1]],
            ['Física', 'NEM-SEC-FISICA', [2]],
            ['Química', 'NEM-SEC-QUIMICA', [3]],
            ['Geografía', 'NEM-SEC-GEOGRAFIA', [1]],
            ['Historia', 'NEM-SEC-HISTORIA', [1, 2, 3]],
            ['Formación Cívica y Ética', 'NEM-SEC-CIVICA', [1, 2, 3]],
            ['Tecnología', 'NEM-SEC-TECNOLOGIA', [1, 2, 3]],
            ['Educación Física', 'NEM-SEC-ED_FISICA', [1, 2, 3]],
            [
                'Tutoría / Educación socioemocional',
                'NEM-SEC-TUTORIA',
                [1, 2, 3],
            ],
        ];

        $primary = [
            [
                'Lenguajes',
                'NEM-CAMPO-LENGUAJES',
                [1, 2, 3, 4, 5, 6],
            ],
            [
                'Saberes y Pensamiento Científico',
                'NEM-CAMPO-SABERES',
                [1, 2, 3, 4, 5, 6],
            ],
            [
                'Ética, Naturaleza y Sociedades',
                'NEM-CAMPO-ETICA',
                [1, 2, 3, 4, 5, 6],
            ],
            [
                'De lo Humano y lo Comunitario',
                'NEM-CAMPO-HUMANO',
                [1, 2, 3, 4, 5, 6],
            ],
        ];

        return [
            [
                'id' => 'sep-sec-2022',
                'name' => 'Secundaria NEM 2022 · Fase 6',
                'level' => 'secundaria',
                'framework' => 'NEM_2022',
                'source' => 'https://educacionbasica.sep.gob.mx/programas-de-estudio-para-la-educacion-preescolar-primaria-y-secundaria-programas-sinteticos-de-las-fases-2-a-6-2/',
                'note' => 'Distribución operativa de referencia; verificar el programa analítico del plantel. No sustituye documentos SEP.',
                'subjects' => $secondary,
            ],
            [
                'id' => 'sep-prim-2022',
                'name' => 'Primaria NEM 2022 · Fases 3–5',
                'level' => 'primaria',
                'framework' => 'NEM_2022',
                'source' => 'https://educacionbasica.sep.gob.mx/programas-de-estudio-para-la-educacion-preescolar-primaria-y-secundaria-programas-sinteticos-de-las-fases-2-a-6-2/',
                'note' => 'Campos formativos, no asignaturas individuales. No promedian automáticamente.',
                'subjects' => $primary,
            ],
            [
                'id' => 'dgb-2026',
                'name' => 'Bachillerato general · Modelo 2026',
                'level' => 'bachillerato',
                'framework' => 'MCCEMS_2026',
                'source' => 'https://dgb.sep.gob.mx/mapas-curriculares',
                'note' => 'Solo estructura base. La distribución depende del servicio/modalidad y debe configurarse desde el mapa autorizado.',
                'subjects' => [],
            ],
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $school = $this->school($request);

        $cycles = DB::table('academic_cycles')
            ->where('school_id', $school)
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get(['id', 'name', 'is_active']);

        $levels = DB::table('academic_levels')
            ->where('school_id', $school)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get(['id', 'name']);

        $plans = DB::table('academic_curriculum_plans as p')
            ->join(
                'academic_levels as l',
                'l.id',
                '=',
                'p.academic_level_id'
            )
            ->join(
                'academic_cycles as c',
                'c.id',
                '=',
                'p.academic_cycle_id'
            )
            ->where('p.school_id', $school)
            ->where('l.school_id', $school)
            ->where('c.school_id', $school)
            ->orderByDesc('p.id')
            ->get([
                'p.*',
                'l.name as level_name',
                'c.name as cycle_name',
            ]);

        $ids = $plans->pluck('id')->all();

        $links = count($ids)
            ? DB::table('academic_curriculum_plan_subjects as ps')
                ->join(
                    'academic_subjects as s',
                    's.id',
                    '=',
                    'ps.subject_id'
                )
                ->where('ps.school_id', $school)
                ->where('s.school_id', $school)
                ->whereIn('ps.curriculum_plan_id', $ids)
                ->orderBy('ps.sort_order')
                ->orderBy('ps.id')
                ->get([
                    'ps.*',
                    's.name as subject_name',
                    's.code as subject_code',
                ])
            : collect();

        $subjects = DB::table('academic_subjects')
            ->where('school_id', $school)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'kind']);

        $groups = DB::table('school_groups')
            ->where('school_id', $school)
            ->where('status', 'active')
            ->whereIn('academic_cycle_id', $cycles->pluck('id'))
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'grade_label',
                'academic_cycle_id',
                'academic_level_id',
            ]);

        $bindings = DB::table('academic_group_curriculum_plans')
            ->where('school_id', $school)
            ->get([
                'school_group_id',
                'curriculum_plan_id',
            ]);

        $usage = DB::table('academic_teaching_assignments as a')
            ->join(
                'academic_curriculum_plan_subjects as ps',
                'ps.id',
                '=',
                'a.plan_subject_id'
            )
            ->where('a.school_id', $school)
            ->where('ps.school_id', $school)
            ->groupBy('ps.curriculum_plan_id')
            ->selectRaw(
                'ps.curriculum_plan_id, COUNT(*) AS total'
            )
            ->pluck('total', 'curriculum_plan_id');

        $lockedLinks = DB::table('academic_teaching_assignments')
            ->where('school_id', $school)
            ->pluck('plan_subject_id')
            ->unique()
            ->values();

        return response()->json(
            compact(
                'cycles',
                'levels',
                'plans',
                'links',
                'subjects',
                'groups',
                'bindings',
                'usage'
            ) + [
                'locked_links' => $lockedLinks,
                'templates' => array_map(
                    fn ($t) => array_diff_key(
                        $t,
                        ['subjects' => true]
                    ),
                    $this->templates()
                ),
            ]
        );
    }

    private function lockPlan(int $school, int $id): object
    {
        return DB::table('academic_curriculum_plans')
            ->where('school_id', $school)
            ->where('id', $id)
            ->lockForUpdate()
            ->first() ?? abort(404, 'Plan no encontrado.');
    }

    private function revision(object $plan, array $data): void
    {
        abort_if(
            (int) $plan->revision !==
            (int) ($data['revision'] ?? 0),
            409,
            'El plan cambió en otra sesión. Recarga antes de continuar.'
        );
    }

    private function references(int $school, int $planId): bool
    {
        return DB::table('academic_teaching_assignments as a')
            ->join(
                'academic_curriculum_plan_subjects as ps',
                'ps.id',
                '=',
                'a.plan_subject_id'
            )
            ->where('a.school_id', $school)
            ->where('ps.school_id', $school)
            ->where('ps.curriculum_plan_id', $planId)
            ->exists();
    }

    private function lockedLink(int $school, int $linkId): bool
    {
        return DB::table('academic_teaching_assignments')
            ->where('school_id', $school)
            ->where('plan_subject_id', $linkId)
            ->exists();
    }

    private function createPlan(
        int $school,
        array $data,
        ?object $source = null
    ): int {
        abort_unless(
            DB::table('academic_cycles')
                ->where('school_id', $school)
                ->where('id', $data['academic_cycle_id'])
                ->exists(),
            422,
            'Ciclo inválido.'
        );

        $level = DB::table('academic_levels')
            ->where('school_id', $school)
            ->where('id', $data['academic_level_id'])
            ->where('status', 'active')
            ->first();

        abort_unless($level, 422, 'Nivel inválido.');

        $template = null;

        if (!empty($data['template_id'])) {
            foreach ($this->templates() as $candidate) {
                if ($candidate['id'] === $data['template_id']) {
                    $template = $candidate;
                    break;
                }
            }

            abort_unless(
                $template,
                422,
                'Plantilla no disponible.'
            );

            abort_unless(
                str_contains(
                    mb_strtolower($level->name),
                    $template['level']
                ),
                422,
                'El nivel de la escuela no corresponde a la plantilla.'
            );
        }

        $version = $source
            ? (int) $source->version + 1
            : 1;

        $id = (int) DB::table('academic_curriculum_plans')
            ->insertGetId([
                'school_id' => $school,
                'academic_cycle_id' => $data['academic_cycle_id'],
                'academic_level_id' => $level->id,
                'name' => trim($data['name']),
                'framework_code' =>
                    $template['framework'] ??
                    ($data['framework_code'] ?? null),
                'version' => $version,
                'revision' => 1,
                'parent_plan_id' => $source?->id,
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        if ($source) {
            $rows = DB::table('academic_curriculum_plan_subjects')
                ->where('school_id', $school)
                ->where('curriculum_plan_id', $source->id)
                ->orderBy('sort_order')
                ->get();

            foreach ($rows as $row) {
                DB::table('academic_curriculum_plan_subjects')
                    ->insert([
                        'school_id' => $school,
                        'curriculum_plan_id' => $id,
                        'subject_id' => $row->subject_id,
                        'grade_label' => $row->grade_label,
                        'is_official' => $row->is_official,
                        'affects_average' => $row->affects_average,
                        'sort_order' => $row->sort_order,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
        } elseif ($template) {
            $order = 0;

            foreach ($template['subjects'] as [
                $name,
                $code,
                $grades,
            ]) {
                $subjectId = DB::table('academic_subjects')
                    ->where('school_id', $school)
                    ->where('code', $code)
                    ->value('id');

                if (!$subjectId) {
                    $subjectId = DB::table('academic_subjects')
                        ->insertGetId([
                            'school_id' => $school,
                            'name' => $name,
                            'code' => $code,
                            'kind' => 'official',
                            'status' => 'active',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }

                foreach ($grades as $grade) {
                    DB::table('academic_curriculum_plan_subjects')
                        ->insert([
                            'school_id' => $school,
                            'curriculum_plan_id' => $id,
                            'subject_id' => $subjectId,
                            'grade_label' => (string) $grade,
                            'is_official' => 1,
                            'affects_average' => 0,
                            'sort_order' => ++$order,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }
        }

        return $id;
    }

    public function manage(Request $request): JsonResponse
    {
        $school = $this->school($request);

        $base = $request->validate([
            'action' => [
                'required',
                Rule::in([
                    'create',
                    'save',
                    'activate',
                    'clone',
                    'archive',
                    'delete',
                    'apply',
                    'subject_update',
                    'subject_disable',
                ]),
            ],
            'plan_id' => ['nullable', 'integer', 'min:1'],
            'subject_id' => ['nullable', 'integer', 'min:1'],
            'subject_name' => ['nullable', 'string', 'max:160'],
            'subject_code' => [
                'nullable',
                'string',
                'max:80',
                'regex:/^[a-z0-9][a-z0-9_-]*$/',
            ],
            'revision' => ['nullable', 'integer', 'min:1'],
            'name' => ['nullable', 'string', 'max:160'],
            'academic_cycle_id' => ['nullable', 'integer', 'min:1'],
            'academic_level_id' => ['nullable', 'integer', 'min:1'],
            'framework_code' => ['nullable', 'string', 'max:80'],
            'template_id' => ['nullable', 'string', 'max:60'],
            'links' => ['nullable', 'array', 'max:500'],
            'links.*.id' => ['nullable', 'integer', 'min:1'],
            'links.*.subject_id' => [
                'required_with:links',
                'integer',
                'min:1',
            ],
            'links.*.grade_label' => [
                'nullable',
                'string',
                'max:100',
            ],
            'links.*.is_official' => [
                'required_with:links',
                'boolean',
            ],
            'links.*.affects_average' => [
                'required_with:links',
                'boolean',
            ],
            'group_ids' => ['nullable', 'array', 'max:150'],
            'group_ids.*' => [
                'integer',
                'min:1',
                'distinct',
            ],
        ]);

        $action = $base['action'];
        $id = (int) ($base['plan_id'] ?? 0);

        $result = DB::transaction(function () use (
            $school,
            $base,
            $action,
            $id
        ): array {
            /*
             * Gestión del catálogo escolar de materias.
             */
            if (in_array(
                $action,
                ['subject_update', 'subject_disable'],
                true
            )) {
                $sid = (int) ($base['subject_id'] ?? 0);

                $subject = DB::table('academic_subjects')
                    ->where('school_id', $school)
                    ->where('id', $sid)
                    ->lockForUpdate()
                    ->first() ?? abort(
                        404,
                        'Materia no encontrada.'
                    );

                $linkIds = DB::table(
                    'academic_curriculum_plan_subjects'
                )
                    ->where('school_id', $school)
                    ->where('subject_id', $sid)
                    ->pluck('id');

                abort_if(
                    DB::table('academic_teaching_assignments')
                        ->where('school_id', $school)
                        ->whereIn('plan_subject_id', $linkIds)
                        ->exists(),
                    409,
                    'La materia tiene asignaciones. Conserva su identidad y crea una nueva materia si necesitas modificarla.'
                );

                if ($action === 'subject_disable') {
                    abort_if(
                        $linkIds->isNotEmpty(),
                        409,
                        'Desvincula la materia de todos los planes antes de desactivarla.'
                    );

                    DB::table('academic_subjects')
                        ->where('school_id', $school)
                        ->where('id', $sid)
                        ->update([
                            'status' => 'inactive',
                            'updated_at' => now(),
                        ]);

                    return [
                        'id' => null,
                        'message' =>
                            'Materia desactivada del catálogo escolar.',
                    ];
                }

                abort_unless(
                    !empty($base['subject_name']) &&
                    !empty($base['subject_code']),
                    422,
                    'Nombre y código obligatorios.'
                );

                $code = strtolower(
                    trim($base['subject_code'])
                );

                abort_if(
                    DB::table('academic_subjects')
                        ->where('school_id', $school)
                        ->where('code', $code)
                        ->where('id', '!=', $sid)
                        ->exists(),
                    422,
                    'Código de materia duplicado.'
                );

                DB::table('academic_subjects')
                    ->where('school_id', $school)
                    ->where('id', $sid)
                    ->update([
                        'name' => trim($base['subject_name']),
                        'code' => $code,
                        'updated_at' => now(),
                    ]);

                return [
                    'id' => null,
                    'message' =>
                        'Materia actualizada en el catálogo.',
                ];
            }

            /*
             * Crear desde cero o adoptar una plantilla.
             */
            if ($action === 'create') {
                abort_unless(
                    !empty($base['name']) &&
                    !empty($base['academic_cycle_id']) &&
                    !empty($base['academic_level_id']),
                    422,
                    'Nombre, ciclo y nivel obligatorios.'
                );

                $newId = $this->createPlan(
                    $school,
                    $base
                );

                return [
                    'id' => $newId,
                    'message' => 'Plan creado en borrador.',
                ];
            }

            $plan = $this->lockPlan($school, $id);
            $this->revision($plan, $base);

            /*
             * Nueva versión independiente.
             */
            if ($action === 'clone') {
                $name = trim(
                    (string) (
                        $base['name'] ??
                        $plan->name .
                        ' · v' .
                        ($plan->version + 1)
                    )
                );

                $newId = $this->createPlan(
                    $school,
                    [
                        'name' => $name,
                        'academic_cycle_id' =>
                            $plan->academic_cycle_id,
                        'academic_level_id' =>
                            $plan->academic_level_id,
                        'framework_code' =>
                            $plan->framework_code,
                    ],
                    $plan
                );

                return [
                    'id' => $newId,
                    'message' =>
                        'Nueva versión creada en borrador.',
                ];
            }

            /*
             * Guardar composición completa del borrador.
             */
            if ($action === 'save') {
                abort_unless(
                    $plan->status === 'draft',
                    409,
                    'El plan ya está cerrado. Duplica para editar.'
                );

                abort_unless(
                    array_key_exists('links', $base),
                    422,
                    'Debes enviar la composición completa.'
                );

                $incoming = $base['links'];

                $existing = DB::table(
                    'academic_curriculum_plan_subjects'
                )
                    ->where('school_id', $school)
                    ->where('curriculum_plan_id', $id)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $seen = [];
                $keep = [];

                foreach ($incoming as $index => $item) {
                    $subject = DB::table('academic_subjects')
                        ->where('school_id', $school)
                        ->where('id', $item['subject_id'])
                        ->where('status', 'active')
                        ->first();

                    abort_unless(
                        $subject,
                        422,
                        'Materia no disponible.'
                    );

                    $grade = $item['grade_label'] ?? null;

                    if ($grade === '') {
                        $grade = null;
                    }

                    $key = $item['subject_id'] .
                        '|' .
                        ($grade ?? '*');

                    abort_if(
                        isset($seen[$key]),
                        422,
                        'Una materia aparece dos veces en el mismo grado.'
                    );

                    $seen[$key] = true;

                    $collision = $item['subject_id'] .
                        '|*';

                    if (
                        $grade === null &&
                        count(array_filter(
                            array_keys($seen),
                            fn ($k) => str_starts_with(
                                $k,
                                $item['subject_id'] . '|'
                            )
                        )) > 1
                    ) {
                        abort(
                            422,
                            'No combines una materia general con esa misma materia por grado.'
                        );
                    }

                    if (
                        $grade !== null &&
                        isset($seen[$collision])
                    ) {
                        abort(
                            422,
                            'La materia ya está configurada para todos los grados.'
                        );
                    }

                    $rowId = (int) ($item['id'] ?? 0);

                    $values = [
                        'subject_id' => $item['subject_id'],
                        'grade_label' => $grade,
                        'is_official' =>
                            (bool) $item['is_official'],
                        'affects_average' =>
                            (bool) $item['affects_average'],
                        'sort_order' => $index + 1,
                        'updated_at' => now(),
                    ];

                    if ($rowId > 0) {
                        $old = $existing->get($rowId);

                        abort_unless(
                            $old,
                            422,
                            'Vínculo ajeno al plan.'
                        );

                        $keep[] = $rowId;

                        $changed =
                            (int) $old->subject_id !==
                                (int) $values['subject_id'] ||
                            ($old->grade_label ?? null) !==
                                $grade ||
                            (int) $old->is_official !==
                                (int) $values['is_official'] ||
                            (int) $old->affects_average !==
                                (int) $values['affects_average'];

                        abort_if(
                            $changed &&
                            $this->lockedLink($school, $rowId),
                            409,
                            'Una materia tiene docentes o calificaciones. Duplica el plan para modificarla.'
                        );

                        DB::table(
                            'academic_curriculum_plan_subjects'
                        )
                            ->where('id', $rowId)
                            ->where('school_id', $school)
                            ->update($values);
                    } else {
                        DB::table(
                            'academic_curriculum_plan_subjects'
                        )->insert(
                            $values + [
                                'school_id' => $school,
                                'curriculum_plan_id' => $id,
                                'created_at' => now(),
                            ]
                        );
                    }
                }

                foreach ($existing as $old) {
                    if (in_array(
                        (int) $old->id,
                        $keep,
                        true
                    )) {
                        continue;
                    }

                    abort_if(
                        $this->lockedLink(
                            $school,
                            (int) $old->id
                        ),
                        409,
                        'No puedes quitar una materia que ya tiene asignaciones. Duplica el plan.'
                    );

                    DB::table(
                        'academic_curriculum_plan_subjects'
                    )
                        ->where('id', $old->id)
                        ->where('school_id', $school)
                        ->delete();
                }

                DB::table('academic_curriculum_plans')
                    ->where('id', $id)
                    ->where('school_id', $school)
                    ->update([
                        'name' => trim(
                            (string) (
                                $base['name'] ??
                                $plan->name
                            )
                        ),
                        'framework_code' =>
                            $base['framework_code'] ??
                            $plan->framework_code,
                        'revision' => $plan->revision + 1,
                        'updated_at' => now(),
                    ]);

                return [
                    'id' => $id,
                    'message' =>
                        'Composición del plan guardada.',
                ];
            }

            /*
             * Activar y cerrar el borrador.
             */
            if ($action === 'activate') {
                abort_unless(
                    $plan->status === 'draft',
                    409,
                    'Solo se activa un borrador.'
                );

                $count = DB::table(
                    'academic_curriculum_plan_subjects'
                )
                    ->where('school_id', $school)
                    ->where('curriculum_plan_id', $id)
                    ->count();

                abort_if(
                    $count === 0,
                    422,
                    'Agrega al menos una materia antes de activar.'
                );

                DB::table('academic_curriculum_plans')
                    ->where('id', $id)
                    ->where('school_id', $school)
                    ->update([
                        'status' => 'active',
                        'revision' => $plan->revision + 1,
                        'updated_at' => now(),
                    ]);

                return [
                    'id' => $id,
                    'message' =>
                        'Plan activado; su composición quedó cerrada.',
                ];
            }

            /*
             * Aplicación formal a grupos.
             */
            if ($action === 'apply') {
                abort_unless(
                    $plan->status === 'active',
                    409,
                    'Activa el plan antes de aplicarlo.'
                );

                $groupIds = $base['group_ids'] ?? [];

                abort_if(
                    !$groupIds,
                    422,
                    'Selecciona grupos.'
                );

                $groups = DB::table('school_groups')
                    ->where('school_id', $school)
                    ->whereIn('id', $groupIds)
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->get();

                abort_unless(
                    $groups->count() === count($groupIds),
                    422,
                    'Hay grupos inválidos.'
                );

                $validLinkIds = DB::table(
                    'academic_curriculum_plan_subjects'
                )
                    ->where('school_id', $school)
                    ->where('curriculum_plan_id', $id)
                    ->pluck('id')
                    ->all();

                foreach ($groups as $group) {
                    abort_unless(
                        (int) $group->academic_cycle_id ===
                            (int) $plan->academic_cycle_id &&
                        (int) $group->academic_level_id ===
                            (int) $plan->academic_level_id,
                        422,
                        'Grupo de otro nivel/ciclo.'
                    );

                    abort_unless(
                        DB::table(
                            'academic_curriculum_plan_subjects'
                        )
                            ->where('school_id', $school)
                            ->where('curriculum_plan_id', $id)
                            ->where(function ($q) use ($group) {
                                $q->whereNull('grade_label')
                                    ->orWhere(
                                        'grade_label',
                                        $group->grade_label
                                    );
                            })
                            ->exists(),
                        422,
                        'El plan no contiene materias para ' .
                            $group->name
                    );

                    abort_if(
                        DB::table('academic_teaching_assignments')
                            ->where('school_id', $school)
                            ->where(
                                'school_group_id',
                                $group->id
                            )
                            ->where(
                                'academic_cycle_id',
                                $group->academic_cycle_id
                            )
                            ->where('status', 'active')
                            ->whereNotIn(
                                'plan_subject_id',
                                $validLinkIds
                            )
                            ->exists(),
                        409,
                        'El grupo ' .
                            $group->name .
                            ' conserva asignaciones de otro plan.'
                    );

                    DB::table(
                        'academic_group_curriculum_plans'
                    )->updateOrInsert(
                        [
                            'school_group_id' => $group->id,
                        ],
                        [
                            'school_id' => $school,
                            'academic_cycle_id' =>
                                $group->academic_cycle_id,
                            'curriculum_plan_id' => $id,
                            'assigned_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }

                return [
                    'id' => $id,
                    'message' =>
                        'Plan aplicado a ' .
                        count($groupIds) .
                        ' grupo(s).',
                ];
            }

            /*
             * Archivo o eliminación segura.
             */
            if (
                $action === 'archive' ||
                $action === 'delete'
            ) {
                abort_if(
                    $this->references($school, $id),
                    409,
                    'Este plan tiene asignaciones docentes o evaluaciones. No se puede retirar.'
                );

                abort_if(
                    DB::table(
                        'academic_group_curriculum_plans'
                    )
                        ->where('school_id', $school)
                        ->where('curriculum_plan_id', $id)
                        ->exists(),
                    409,
                    'Hay grupos usando este plan. Primero reasigna sus planes.'
                );

                if ($action === 'delete') {
                    abort_unless(
                        $plan->status === 'draft',
                        409,
                        'Solo se elimina un borrador no utilizado.'
                    );

                    DB::table('academic_curriculum_plans')
                        ->where('id', $id)
                        ->where('school_id', $school)
                        ->delete();

                    return [
                        'id' => null,
                        'message' => 'Borrador eliminado.',
                    ];
                }

                DB::table('academic_curriculum_plans')
                    ->where('id', $id)
                    ->where('school_id', $school)
                    ->update([
                        'status' => 'archived',
                        'revision' => $plan->revision + 1,
                        'updated_at' => now(),
                    ]);

                return [
                    'id' => $id,
                    'message' => 'Plan archivado.',
                ];
            }

            abort(422, 'Acción inválida.');
        }, 3);

        return response()->json($result);
    }
}
