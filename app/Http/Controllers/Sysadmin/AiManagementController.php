<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sysadmin\UpdateSchoolAiSettingsRequest;
use App\Models\AiRun;
use App\Models\School;
use App\Services\Ai\AiSettingsService;
use App\Services\Auditing\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiManagementController extends Controller
{
    public function __construct(
        private readonly AiSettingsService $settingsService,
        private readonly AuditLogger $auditLogger
    ) {
    }

    public function index(Request $request): View
    {
        $query = DB::table('schools as schools')
            ->leftJoin('ai_settings as ai', 'ai.school_id', '=', 'schools.id')
            ->leftJoin('school_licenses as licenses', function ($join): void {
                $join->on('licenses.school_id', '=', 'schools.id')
                    ->where('licenses.is_current', true);
            })
            ->leftJoin(
                'subscription_plans as plans',
                'plans.id',
                '=',
                'licenses.subscription_plan_id'
            )
            ->select([
                'schools.id',
                'schools.name',
                'schools.status',
                'schools.logo_path',
                'schools.primary_color',
                'licenses.status as license_status',
                'licenses.expires_at',
                'licenses.trial_ends_at',
                'plans.name as plan_name',
                'ai.id as ai_settings_id',
                'ai.enabled as ai_enabled',
                'ai.monthly_query_limit',
                'ai.default_model',
                'ai.allow_pro',
                'ai.usage_reset_at',
            ]);

        $search = trim((string) $request->query('q', ''));

        if ($search !== '') {
            $query->where(function ($inner) use ($search): void {
                $inner->where('schools.name', 'like', "%{$search}%")
                    ->orWhere('plans.name', 'like', "%{$search}%");
            });
        }

        $schoolStatus = $request->string('school_status')->toString();

        if (in_array($schoolStatus, ['active', 'suspended', 'cancelled'], true)) {
            $query->where('schools.status', $schoolStatus);
        }

        $aiState = $request->string('ai_state')->toString();

        if ($aiState === 'enabled') {
            $query->where(function ($inner): void {
                $inner->where('ai.enabled', true)
                    ->orWhereNull('ai.id');
            });
        }

        if ($aiState === 'disabled') {
            $query->where('ai.enabled', false);
        }

        $schools = $query
            ->orderBy('schools.name')
            ->paginate(25)
            ->withQueryString();

        $collection = $schools->getCollection()->map(
            function (object $school): object {
                $settings = $this->settingsService->forSchool((int) $school->id);
                $usage = $this->settingsService->usageThisMonth((int) $school->id);
                $limit = max(1, (int) $settings->monthly_query_limit);

                $committedCredits =
                    (int) (
                        $usage->credits
                        ?? $usage->runs
                        ?? 0
                    )
                    + (int) (
                        $usage->pending_credits
                        ?? 0
                    );

                $school->ai_settings = $settings;
                $school->ai_usage = $usage;
                $school->ai_committed_credits = $committedCredits;
                $school->ai_usage_percent = round(
                    ($committedCredits / $limit) * 100,
                    1
                );
                $school->effective_ai_enabled =
                    (bool) config('schoolpass_ai.enabled')
                    && trim((string) config('schoolpass_ai.deepseek.api_key')) !== ''
                    && (bool) $settings->enabled;

                return $school;
            }
        );

        $schools->setCollection($collection);

        return view('sysadmin.ai.index', [
            'schools' => $schools,
            'globalEnabled' => (bool) config('schoolpass_ai.enabled'),
            'apiKeyConfigured' =>
                trim((string) config('schoolpass_ai.deepseek.api_key')) !== '',
        ]);
    }

    public function show(School $school): View
    {
        $settings = $this->settingsService->forSchool($school->id);
        $usage = $this->settingsService->usageThisMonth($school->id);

        $recentRuns = AiRun::query()
            ->with([
                'user:id,name,email',
                'conversation:id,title',
            ])
            ->where('school_id', $school->id)
            ->latest('id')
            ->limit(20)
            ->get();

        $dailyUsage = DB::table('ai_runs')
            ->where('school_id', $school->id)
            ->where('status', 'success')
            ->whereBetween('created_at', [
                $usage->window_start,
                now()->endOfMonth(),
            ])
            ->selectRaw(
                'DATE(created_at) as day,
                 COUNT(*) as runs,
                 COALESCE(SUM(total_tokens), 0) as total_tokens,
                 COALESCE(SUM(estimated_cost_usd), 0) as estimated_cost_usd'
            )
            ->groupByRaw('DATE(created_at)')
            ->orderBy('day')
            ->get();

        return view('sysadmin.ai.school', [
            'school' => $school,
            'settings' => $settings,
            'usage' => $usage,
            'recentRuns' => $recentRuns,
            'dailyUsage' => $dailyUsage,
            'globalEnabled' => (bool) config('schoolpass_ai.enabled'),
            'apiKeyConfigured' =>
                trim((string) config('schoolpass_ai.deepseek.api_key')) !== '',
        ]);
    }

    public function update(
        UpdateSchoolAiSettingsRequest $request,
        School $school
    ): RedirectResponse {
        $data = $request->validated();

        /*
         * El chat siempre inicia en rápido. Sysadmin únicamente autoriza
         * que aparezca el toggle Pro por consulta.
         */
        $data['default_model'] = 'fast';

        $before = (array) $this->settingsService->forSchool($school->id);

        $after = $this->settingsService->saveForSchool(
            schoolId: $school->id,
            values: $data,
            actorId: $request->user()->id
        );

        $this->auditLogger->record(
            action: 'school_ai_settings_updated',
            schoolId: $school->id,
            actorId: $request->user()->id,
            actorType: 'superadmin',
            entityType: 'ai_settings',
            entityId: $after->id,
            oldValues: $before,
            newValues: (array) $after,
            request: $request,
        );

        return back()->with('status', 'Configuración de IA actualizada.');
    }

    public function resetUsage(
        Request $request,
        School $school
    ): RedirectResponse {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $beforeUsage = (array) $this->settingsService->usageThisMonth($school->id);

        $settings = $this->settingsService->resetUsage(
            schoolId: $school->id,
            actorId: $request->user()->id
        );

        $this->auditLogger->record(
            action: 'school_ai_usage_reset',
            schoolId: $school->id,
            actorId: $request->user()->id,
            actorType: 'superadmin',
            entityType: 'ai_settings',
            entityId: $settings->id,
            oldValues: ['usage' => $beforeUsage],
            newValues: [
                'usage_reset_at' => $settings->usage_reset_at,
                'reason' => $data['reason'] ?? null,
            ],
            request: $request,
        );

        return back()->with(
            'status',
            'El contador mensual de IA se reinició sin eliminar el historial.'
        );
    }
}
