<?php

namespace App\Http\Controllers\Admin\Ai;

use App\Http\Controllers\Controller;
use App\Jobs\Ai\ProcessAiRunJob;
use App\Models\AiConversation;
use App\Models\AiRun;
use App\Services\Ai\AiConversationService;
use App\Services\Ai\AiRunEventLogger;
use App\Services\Ai\AiPeriodResolver;
use App\Services\Ai\AiScopeResolver;
use App\Services\Ai\AiSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AiController extends Controller
{
    public function __construct(
        private readonly AiSettingsService $settingsService,
        private readonly AiConversationService $conversationService,
        private readonly AiRunEventLogger $eventLogger,
        private readonly AiScopeResolver $scopeResolver,
        private readonly AiPeriodResolver $periodResolver
    ) {
    }

    public function index(
        Request $request
    ): View {
        $user = $request->user();
        $schoolId = $this->schoolId($request);

        $settings = $this
            ->settingsService
            ->forSchool($schoolId);

        $usage = $this
            ->settingsService
            ->usageThisMonth($schoolId);

        $activeCycle = DB::table('academic_cycles')
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->where('status', 'active')
            ->first();

        $groups = collect();
        $students = collect();

        if ($activeCycle) {
            $groups = DB::table('school_groups as sg')
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
                ->where(
                    'sg.academic_cycle_id',
                    $activeCycle->id
                )
                ->where('sg.status', 'active')
                ->select([
                    'sg.id',
                    'sg.name',
                    'al.name as level_name',
                    'al.sort_order',
                    'c.name as campus_name',
                ])
                ->orderBy('c.name')
                ->orderBy('al.sort_order')
                ->orderBy('sg.name')
                ->get();

            $students = DB::table(
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
                ->where(
                    'se.academic_cycle_id',
                    $activeCycle->id
                )
                ->where('se.status', 'active')
                ->where('s.status', 'active')
                ->select([
                    's.id',
                    's.student_code',
                    's.first_name',
                    's.last_name',
                    'sg.name as group_name',
                ])
                ->orderBy('s.last_name')
                ->orderBy('s.first_name')
                ->limit(1500)
                ->get();
        }

        $recentConversations = AiConversation::query()
            ->where('school_id', $schoolId)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->withCount('messages')
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->limit(
                (int) config(
                    'schoolpass_ai.limits.recent_conversations',
                    80
                )
            )
            ->get();

        $activeConversation = null;
        $activeMessages = collect();
        $processingRun = null;
        $lastRun = null;

        $conversationId = $request->integer(
            'conversation'
        );

        if ($conversationId > 0) {
            $activeConversation = AiConversation::query()
                ->where('id', $conversationId)
                ->where('school_id', $schoolId)
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->firstOrFail();

            $activeMessages = $activeConversation
                ->messages()
                ->get();

            $processingRun = AiRun::query()
                ->where(
                    'conversation_id',
                    $activeConversation->id
                )
                ->whereIn('status', [
                    'queued',
                    'processing',
                ])
                ->orderByDesc('id')
                ->first();

            $lastRun = AiRun::query()
                ->where(
                    'conversation_id',
                    $activeConversation->id
                )
                ->orderByDesc('id')
                ->first();
        }

        $initialScopeType = $activeConversation
            ? $activeConversation->scope_type
            : $request->string(
                'scope_type',
                'school'
            )->toString();

        if (! in_array(
            $initialScopeType,
            ['school', 'group', 'student'],
            true
        )) {
            $initialScopeType = 'school';
        }

        $initialScopeId = $activeConversation
            ? $activeConversation->scope_id
            : (
                $request->integer('scope_id')
                ?: null
            );

        return view('admin.ai.index', [
            'settings' => $settings,
            'usage' => $usage,
            'activeCycle' => $activeCycle,
            'groups' => $groups,
            'students' => $students,
            'recentConversations' =>
                $recentConversations,
            'activeConversation' =>
                $activeConversation,
            'activeMessages' => $activeMessages,
            'processingRun' => $processingRun,
            'initialScopeType' =>
                $initialScopeType,
            'initialScopeId' =>
                $initialScopeId,
            'initialQuestion' =>
                $request->string(
                    'question'
                )->toString(),
            'defaultFrom' => old(
                'period_from',
                $lastRun?->period_from
                    ? $lastRun->period_from
                        ->toDateString()
                    : now()
                        ->subDays(29)
                        ->toDateString()
            ),
            'defaultTo' => old(
                'period_to',
                $lastRun?->period_to
                    ? $lastRun->period_to
                        ->toDateString()
                    : now()->toDateString()
            ),
            'globalEnabled' => (bool) config(
                'schoolpass_ai.enabled'
            ),
            'apiKeyConfigured' => trim(
                (string) config(
                    'schoolpass_ai.deepseek.api_key'
                )
            ) !== '',
        ]);
    }

    public function analyze(
        Request $request
    ): JsonResponse|RedirectResponse {
        $user = $request->user();
        $schoolId = $this->schoolId($request);

        $settings = $this
            ->settingsService
            ->forSchool($schoolId);

        $validated = $request->validate([
            'conversation_id' => [
                'nullable',
                'integer',
            ],

            'scope_type' => [
                'required',
                Rule::in([
                    'school',
                    'group',
                    'student',
                ]),
            ],

            'scope_id' => [
                'nullable',
                'integer',
            ],

            'period_from' => [
                'required',
                'date',
            ],

            'period_to' => [
                'required',
                'date',
                'after_or_equal:period_from',
                'before_or_equal:today',
            ],

            'model_tier' => [
                'nullable',
                Rule::in([
                    'fast',
                    'pro',
                ]),
            ],

            'question' => [
                'required',
                'string',
                'max:'.(int) config(
                    'schoolpass_ai.limits.max_question_chars',
                    1800
                ),
            ],
        ]);

        $question = trim(
            $validated['question']
        );

        $requestedModelTier = (
            $validated['model_tier']
            ?? 'fast'
        ) === 'pro'
            ? 'pro'
            : 'fast';

        if (
            $requestedModelTier === 'pro'
            && ! (bool) $settings->allow_pro
        ) {
            throw ValidationException::withMessages([
                'model_tier' =>
                    'El análisis avanzado no está autorizado para esta escuela.',
            ]);
        }

        $quotaUnits = $this
            ->settingsService
            ->unitsForTier(
                $requestedModelTier
            );

        $this->assertAvailable(
            settings: $settings,
            schoolId: $schoolId,
            requestedUnits: $quotaUnits
        );

        $resolvedScope = $this
            ->scopeResolver
            ->resolve(
                schoolId: $schoolId,
                requestedType:
                    $validated['scope_type'],
                requestedId: ! empty(
                    $validated['scope_id']
                )
                    ? (int) $validated['scope_id']
                    : null,
                question: $question
            );

        $scopeType = $resolvedScope['type'];
        $scopeId = $resolvedScope['id'];

        if (
            in_array(
                $scopeType,
                ['group', 'student'],
                true
            )
            && ! $scopeId
        ) {
            throw ValidationException::withMessages([
                'scope_id' =>
                    'Selecciona el grupo o alumno que se analizará.',
            ]);
        }

        $this->assertScopeEnabled(
            $settings,
            $scopeType
        );

        $manualFrom = Carbon::parse(
            $validated['period_from']
        )->startOfDay();

        $manualTo = Carbon::parse(
            $validated['period_to']
        )->startOfDay();

        $resolvedPeriod = $this
            ->periodResolver
            ->resolve(
                schoolId: $schoolId,
                question: $question,
                requestedFrom: $manualFrom,
                requestedTo: $manualTo,
                maxRangeDays: (int)
                    $settings->max_range_days
            );

        $from = $resolvedPeriod['from'];
        $to = $resolvedPeriod['to'];

        $days = $from->diffInDays($to) + 1;

        if (
            $days
            > (int) $settings->max_range_days
        ) {
            throw ValidationException::withMessages([
                'period_to' => sprintf(
                    'El periodo máximo permitido es de %d días.',
                    (int) $settings->max_range_days
                ),
            ]);
        }

        [$conversation, $run] = DB::transaction(
            function () use (
                $user,
                $schoolId,
                $validated,
                $scopeType,
                $scopeId,
                $question,
                $from,
                $to,
                $requestedModelTier,
                $quotaUnits,
                $resolvedScope
            ): array {
                /*
                 * Serializa la reserva de créditos por escuela. Sin este
                 * bloqueo, dos usuarios podrían enviar consultas al mismo
                 * tiempo y superar la cuota antes de que aparezca el run.
                 */
                DB::table('schools')
                    ->where('id', $schoolId)
                    ->lockForUpdate()
                    ->value('id');

                $lockedSettings = $this
                    ->settingsService
                    ->forSchool($schoolId);

                if (
                    $requestedModelTier === 'pro'
                    && ! (bool) $lockedSettings->allow_pro
                ) {
                    throw ValidationException::withMessages([
                        'model_tier' =>
                            'El análisis avanzado no está autorizado para esta escuela.',
                    ]);
                }

                $this->assertAvailable(
                    settings: $lockedSettings,
                    schoolId: $schoolId,
                    requestedUnits: $quotaUnits
                );

                $this->assertScopeEnabled(
                    $lockedSettings,
                    $scopeType
                );

                $conversation = $this
                    ->conversationService
                    ->resolve(
                        user: $user,
                        conversationId: ! empty(
                            $validated['conversation_id']
                        )
                            ? (int) $validated['conversation_id']
                            : null,
                        scopeType: $scopeType,
                        scopeId: $scopeId,
                        question: $question
                    );

                $run = AiRun::query()->create([
                    'school_id' => $schoolId,
                    'user_id' => $user->id,
                    'conversation_id' =>
                        $conversation->id,
                    'previous_run_id' => null,
                    'request_type' => 'question',
                    'prompt_version' => config(
                        'schoolpass_ai.prompt_version'
                    ),
                    'scope_type' => $scopeType,
                    'scope_id' => $scopeId,
                    'period_from' => $from,
                    'period_to' => $to,
                    'question' => $question,
                    'provider' => 'deepseek',
                    'requested_model_tier' =>
                        $requestedModelTier,
                    'quota_units' => $quotaUnits,
                    'thinking_enabled' => false,
                    'status' => 'queued',
                ]);

                $this->conversationService->addMessage(
                    conversation: $conversation,
                    role: 'user',
                    content: $question,
                    aiRunId: $run->id,
                    status: 'completed'
                );

                $this->eventLogger->complete(
                    runId: $run->id,
                    stageKey: 'request_received',
                    label: 'Pregunta recibida',
                    detail: $resolvedScope[
                        'resolved_automatically'
                    ]
                        ? 'PaseLista identificó automáticamente el contexto solicitado.'
                        : 'La solicitud fue validada y asignada a la conversación.',
                    sortOrder: 10,
                    metadata: [
                        'scope_type' => $scopeType,
                        'scope_id' => $scopeId,
                        'resolved_automatically' =>
                            $resolvedScope[
                                'resolved_automatically'
                            ],
                        'requested_model_tier' =>
                            $requestedModelTier,
                        'quota_units' =>
                            $quotaUnits,
                    ]
                );

                $this->eventLogger->start(
                    runId: $run->id,
                    stageKey: 'queued',
                    label: 'En espera de procesamiento',
                    detail: $requestedModelTier === 'pro'
                        ? sprintf(
                            'Análisis avanzado solicitado. Reserva %d créditos.',
                            $quotaUnits
                        )
                        : sprintf(
                            'Análisis rápido solicitado. Reserva %d crédito.',
                            $quotaUnits
                        ),
                    sortOrder: 15
                );

                return [
                    $conversation,
                    $run,
                ];
            },
            3
        );

        ProcessAiRunJob::dispatch(
            $run->id
        )
            ->onConnection(
                (string) config(
                    'schoolpass_ai.queue.connection',
                    'database'
                )
            )
            ->onQueue(
                (string) config(
                    'schoolpass_ai.queue.name',
                    'default'
                )
            );

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' =>
                    'La consulta está siendo procesada.',
                'conversation' => [
                    'id' => $conversation->id,
                    'title' => $conversation->title,
                    'scope_type' => $scopeType,
                    'scope_id' => $scopeId,
                ],
                'run' => [
                    'id' => $run->id,
                    'status' => $run->status,
                    'requested_model_tier' =>
                        $run->requested_model_tier,
                    'quota_units' =>
                        (int) $run->quota_units,
                ],
                'resolved_scope' =>
                    $resolvedScope,

                'resolved_period' => [
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                    'detected' => (bool)
                        $resolvedPeriod['detected'],
                    'source' =>
                        $resolvedPeriod['source'],
                    'label' =>
                        $resolvedPeriod['label'],
                    'requested_days' => (int)
                        $resolvedPeriod['requested_days'],
                    'limited' => (bool)
                        $resolvedPeriod['limited'],
                ],
            ], 202);
        }

        return redirect()->route(
            'admin.ai.index',
            [
                'conversation' =>
                    $conversation->id,
                'run' => $run->id,
            ]
        );
    }

    public function show(
        Request $request,
        int $run
    ): RedirectResponse {
        $user = $request->user();
        $schoolId = $this->schoolId($request);

        $row = AiRun::query()
            ->where('id', $run)
            ->where('school_id', $schoolId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return redirect()->route(
            'admin.ai.index',
            [
                'conversation' =>
                    $row->conversation_id,
                'run' => $row->id,
            ]
        );
    }

    public function status(
        Request $request,
        int $run
    ): JsonResponse {
        $user = $request->user();
        $schoolId = $this->schoolId($request);

        $row = AiRun::query()
            ->where('id', $run)
            ->where('school_id', $schoolId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $settings = $this
            ->settingsService
            ->forSchool($schoolId);

        $usedCredits = $this
            ->settingsService
            ->creditsUsedThisMonth(
                $schoolId
            );

        $committedCredits = $this
            ->settingsService
            ->creditsCommittedThisMonth(
                $schoolId
            );

        $creditLimit = (int)
            $settings->monthly_query_limit;

        return response()->json([
            'ok' => true,
            'run' => [
                'id' => $row->id,
                'conversation_id' =>
                    $row->conversation_id,
                'status' => $row->status,
                'request_type' =>
                    $row->request_type,
                'requested_model_tier' =>
                    $row->requested_model_tier
                    ?: 'fast',
                'quota_units' =>
                    (int) (
                        $row->quota_units
                        ?: 1
                    ),
                'model' => $row->model,
                'completed_at' =>
                    $row->completed_at,

                'period_from' =>
                    $row->period_from?->toDateString(),

                'period_to' =>
                    $row->period_to?->toDateString(),

                'result' => $row->response_json,
                'error_message' =>
                    $row->status === 'error'
                        ? 'El análisis no pudo completarse. Revisa el historial o inténtalo nuevamente.'
                        : null,
            ],
            'quota' => [
                'used' => $committedCredits,
                'consumed' => $usedCredits,
                'committed' =>
                    $committedCredits,
                'remaining' => max(
                    0,
                    $creditLimit
                    - $committedCredits
                ),
                'limit' => $creditLimit,
                'fast_units' => $this
                    ->settingsService
                    ->unitsForTier('fast'),
                'pro_units' => $this
                    ->settingsService
                    ->unitsForTier('pro'),
            ],
            'events' => $this
                ->eventLogger
                ->timeline($run),
        ]);
    }

    private function assertAvailable(
        object $settings,
        int $schoolId,
        int $requestedUnits
    ): void {
        abort_unless(
            (bool) config(
                'schoolpass_ai.enabled'
            ),
            503,
            'PaseLista IA está desactivado globalmente.'
        );

        abort_unless(
            trim(
                (string) config(
                    'schoolpass_ai.deepseek.api_key'
                )
            ) !== '',
            503,
            'DEEPSEEK_API_KEY no está configurada.'
        );

        abort_unless(
            (bool) $settings->enabled,
            403,
            'PaseLista IA está desactivado para esta escuela.'
        );

        $committed = $this
            ->settingsService
            ->creditsCommittedThisMonth(
                $schoolId
            );

        $limit = (int)
            $settings->monthly_query_limit;

        abort_if(
            $committed + $requestedUnits
                > $limit,
            429,
            sprintf(
                'No hay créditos suficientes. Disponibles: %d; requeridos: %d.',
                max(
                    0,
                    $limit - $committed
                ),
                $requestedUnits
            )
        );
    }

    private function assertScopeEnabled(
        object $settings,
        string $scopeType
    ): void {
        $allowed = match ($scopeType) {
            'school' => (bool) $settings
                ->allow_school_analysis,
            'group' => (bool) $settings
                ->allow_group_analysis,
            'student' => (bool) $settings
                ->allow_student_analysis,
            default => false,
        };

        abort_unless(
            $allowed,
            403,
            'Este tipo de análisis está desactivado para la escuela.'
        );
    }

    private function schoolId(
        Request $request
    ): int {
        $schoolId = (int) (
            $request->user()?->school_id
            ?? 0
        );

        abort_unless(
            $schoolId > 0,
            403
        );

        return $schoolId;
    }
}
