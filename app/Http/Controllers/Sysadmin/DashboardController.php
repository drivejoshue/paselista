<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Services\Ai\AiSettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AiSettingsService $aiSettingsService
    ) {
    }

    public function index(): View
    {
        $today = now()->toDateString();
        $nextThirtyDays = now()->addDays(30)->toDateString();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $metrics = [
            'active_schools' => DB::table('schools')
                ->where('status', 'active')
                ->count(),

            'trial_licenses' => DB::table('school_licenses')
                ->where('is_current', true)
                ->where('status', 'trial')
                ->count(),

            'expiring_soon' => DB::table('school_licenses')
                ->where('is_current', true)
                ->whereIn('status', ['active', 'grace'])
                ->whereBetween('expires_at', [$today, $nextThirtyDays])
                ->count(),

            'expired_licenses' => DB::table('school_licenses')
                ->where('is_current', true)
                ->where(function ($query) use ($today): void {
                    $query->where('status', 'expired')
                        ->orWhere(function ($inner) use ($today): void {
                            $inner->whereIn('status', ['active', 'trial', 'grace'])
                                ->whereNotNull('expires_at')
                                ->whereDate('expires_at', '<', $today);
                        });
                })
                ->count(),

            'students' => DB::table('students')
                ->where('status', 'active')
                ->count(),

            'devices' => DB::table('access_devices')
                ->where('status', 'active')
                ->count(),
        ];

        $mrr = (float) DB::table('school_licenses')
            ->where('is_current', true)
            ->whereIn('status', ['active', 'grace'])
            ->selectRaw(
                "COALESCE(SUM(CASE
                    WHEN billing_cycle = 'monthly' THEN contract_price
                    WHEN billing_cycle = 'annual' THEN contract_price / 12
                    WHEN billing_cycle = 'custom' THEN contract_price
                    ELSE 0
                END), 0) AS mrr"
            )
            ->value('mrr');

        $metrics['mrr'] = $mrr;
        $metrics['arr'] = $mrr * 12;

        $aiAggregate = DB::table('ai_runs')
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->selectRaw(
                "SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_runs,
                 SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as error_runs,
                 COALESCE(SUM(CASE WHEN status = 'success' THEN quota_units ELSE 0 END), 0) as total_credits,
                 COALESCE(SUM(CASE WHEN status = 'success' THEN total_tokens ELSE 0 END), 0) as total_tokens,
                 COALESCE(SUM(CASE WHEN status = 'success' THEN estimated_cost_usd ELSE 0 END), 0) as estimated_cost_usd,
                 COALESCE(AVG(CASE WHEN status = 'success' THEN duration_ms ELSE NULL END), 0) as average_duration_ms"
            )
            ->first();

        $metrics['ai_runs_month'] = (int) ($aiAggregate->success_runs ?? 0);
        $metrics['ai_errors_month'] = (int) ($aiAggregate->error_runs ?? 0);
        $metrics['ai_credits_month'] = (int) ($aiAggregate->total_credits ?? 0);
        $metrics['ai_tokens_month'] = (int) ($aiAggregate->total_tokens ?? 0);
        $metrics['ai_cost_month'] = (float) ($aiAggregate->estimated_cost_usd ?? 0);
        $metrics['ai_average_duration_ms'] = (float) ($aiAggregate->average_duration_ms ?? 0);

        $globalAiEnabled = (bool) config('schoolpass_ai.enabled');
        $apiKeyConfigured =
            trim((string) config('schoolpass_ai.deepseek.api_key')) !== '';

        if (! $globalAiEnabled || ! $apiKeyConfigured) {
            $metrics['ai_enabled_schools'] = 0;
        } else {
            $defaultEnabled = (bool) config(
                'schoolpass_ai.defaults.school_enabled',
                true
            );

            $enabledQuery = DB::table('schools as schools')
                ->leftJoin('ai_settings as ai', 'ai.school_id', '=', 'schools.id')
                ->where('schools.status', 'active');

            if ($defaultEnabled) {
                $enabledQuery->where(function ($query): void {
                    $query->where('ai.enabled', true)
                        ->orWhereNull('ai.id');
                });
            } else {
                $enabledQuery->where('ai.enabled', true);
            }

            $metrics['ai_enabled_schools'] =
                $enabledQuery->count('schools.id');
        }

        $planDistribution = DB::table('subscription_plans as plans')
            ->leftJoin('school_licenses as licenses', function ($join): void {
                $join->on('licenses.subscription_plan_id', '=', 'plans.id')
                    ->where('licenses.is_current', true);
            })
            ->select([
                'plans.id',
                'plans.name',
                'plans.code',
                DB::raw('COUNT(licenses.id) AS total'),
            ])
            ->groupBy('plans.id', 'plans.name', 'plans.code', 'plans.sort_order')
            ->orderBy('plans.sort_order')
            ->get();

        $schoolsNearLimit = DB::table('schools as schools')
            ->join('school_licenses as licenses', function ($join): void {
                $join->on('licenses.school_id', '=', 'schools.id')
                    ->where('licenses.is_current', true);
            })
            ->leftJoin(
                'subscription_plans as plans',
                'plans.id',
                '=',
                'licenses.subscription_plan_id'
            )
            ->whereNotNull('licenses.student_limit')
            ->select([
                'schools.id',
                'schools.name',
                'licenses.status as license_status',
                'licenses.student_limit',
                'plans.name as plan_name',
            ])
            ->selectSub(function ($query): void {
                $query->from('students')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('students.school_id', 'schools.id')
                    ->where('students.status', 'active');
            }, 'students_used')
            ->get()
            ->map(function ($school) {
                $school->usage_percent = $school->student_limit > 0
                    ? round(($school->students_used / $school->student_limit) * 100, 1)
                    : 0;

                return $school;
            })
            ->filter(fn ($school): bool => $school->usage_percent >= 80)
            ->sortByDesc('usage_percent')
            ->take(10)
            ->values();

        $activeSchools = DB::table('schools')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        $schoolsNearAiLimit = $activeSchools
            ->map(function (object $school): object {
                $settings = $this->aiSettingsService->forSchool((int) $school->id);
                $usage = $this->aiSettingsService->usageThisMonth((int) $school->id);
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

                $school->used = $committedCredits;
                $school->limit = $limit;
                $school->usage_percent = round(
                    ($committedCredits / $limit) * 100,
                    1
                );
                $school->cost = $usage->estimated_cost_usd;

                return $school;
            })
            ->filter(fn (object $school): bool => $school->usage_percent >= 80)
            ->sortByDesc('usage_percent')
            ->take(10)
            ->values();

        $topAiSchools = DB::table('ai_runs as runs')
            ->join('schools', 'schools.id', '=', 'runs.school_id')
            ->where('runs.status', 'success')
            ->whereBetween('runs.created_at', [$monthStart, $monthEnd])
            ->select([
                'schools.id',
                'schools.name',
                DB::raw('COUNT(runs.id) as runs'),
                DB::raw('COALESCE(SUM(runs.total_tokens), 0) as total_tokens'),
                DB::raw('COALESCE(SUM(runs.estimated_cost_usd), 0) as estimated_cost_usd'),
            ])
            ->groupBy('schools.id', 'schools.name')
            ->orderByDesc('runs')
            ->limit(10)
            ->get();

        $recentAiErrors = DB::table('ai_runs as runs')
            ->join('schools', 'schools.id', '=', 'runs.school_id')
            ->leftJoin('users', 'users.id', '=', 'runs.user_id')
            ->where('runs.status', 'error')
            ->select([
                'runs.id',
                'runs.question',
                'runs.model',
                'runs.error_message',
                'runs.created_at',
                'schools.id as school_id',
                'schools.name as school_name',
                'users.name as user_name',
            ])
            ->latest('runs.id')
            ->limit(8)
            ->get();

        $recentEvents = DB::table('school_license_events as events')
            ->join('schools', 'schools.id', '=', 'events.school_id')
            ->leftJoin('users', 'users.id', '=', 'events.performed_by')
            ->select([
                'events.id',
                'events.event_type',
                'events.previous_status',
                'events.new_status',
                'events.created_at',
                'schools.id as school_id',
                'schools.name as school_name',
                'users.name as performed_by_name',
            ])
            ->latest('events.created_at')
            ->limit(12)
            ->get();

        return view('sysadmin.dashboard', compact(
            'metrics',
            'planDistribution',
            'schoolsNearLimit',
            'schoolsNearAiLimit',
            'topAiSchools',
            'recentAiErrors',
            'recentEvents',
            'globalAiEnabled',
            'apiKeyConfigured',
        ));
    }
}
