<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\AiRun;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AiAuditController extends Controller
{
    public function index(Request $request): View
    {
        $query = AiRun::query()->with([
            'school:id,name',
            'user:id,name,email',
            'conversation:id,title',
        ]);

        $schoolId = $request->integer('school_id');

        if ($schoolId > 0) {
            $query->where('school_id', $schoolId);
        }

        $status = $request->string('status')->toString();

        if (in_array($status, ['queued', 'processing', 'success', 'error'], true)) {
            $query->where('status', $status);
        }

        $requestType = $request->string('request_type')->toString();

        if ($requestType !== '') {
            $query->where('request_type', $requestType);
        }

        $model = trim($request->string('model')->toString());

        if ($model !== '') {
            $query->where('model', $model);
        }

        $search = trim($request->string('q')->toString());

        if ($search !== '') {
            $query->where(function ($inner) use ($search): void {
                $inner->where('question', 'like', "%{$search}%")
                    ->orWhere('redacted_question', 'like', "%{$search}%")
                    ->orWhere('context_hash', 'like', "%{$search}%");
            });
        }

        $dateFrom = $request->string('date_from')->toString();

        if ($dateFrom !== '') {
            $query->where(
                'created_at',
                '>=',
                Carbon::parse($dateFrom)->startOfDay()
            );
        }

        $dateTo = $request->string('date_to')->toString();

        if ($dateTo !== '') {
            $query->where(
                'created_at',
                '<=',
                Carbon::parse($dateTo)->endOfDay()
            );
        }

        $summary = (clone $query)
            ->reorder()
            ->selectRaw(
                "COUNT(*) as total_runs,
                 SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_runs,
                 SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as error_runs,
                 COALESCE(
                    SUM(
                        CASE
                            WHEN status = 'success'
                            THEN quota_units
                            ELSE 0
                        END
                    ),
                    0
                 ) as total_credits,
                 COALESCE(
                    SUM(
                        CASE
                            WHEN status = 'success'
                            THEN total_tokens
                            ELSE 0
                        END
                    ),
                    0
                 ) as total_tokens,
                 COALESCE(
                    SUM(
                        CASE
                            WHEN status = 'success'
                            THEN estimated_cost_usd
                            ELSE 0
                        END
                    ),
                    0
                 ) as estimated_cost_usd,
                 COALESCE(
                    AVG(
                        CASE
                            WHEN status = 'success'
                            THEN duration_ms
                            ELSE NULL
                        END
                    ),
                    0
                 ) as average_duration_ms"
            )
            ->first();

        $runs = $query
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $schools = DB::table('schools')
            ->orderBy('name')
            ->get(['id', 'name']);

        $models = DB::table('ai_runs')
            ->whereNotNull('model')
            ->where('model', '<>', '')
            ->distinct()
            ->orderBy('model')
            ->pluck('model');

        return view('sysadmin.ai.audit.index', compact(
            'runs',
            'schools',
            'models',
            'summary'
        ));
    }

    public function show(int $run): View
    {
        $run = AiRun::query()
            ->with([
                'school:id,name,logo_path,primary_color,secondary_color',
                'user:id,name,email,role',
                'conversation:id,title,scope_type,scope_id',
                'events',
                'previousRun:id,status,created_at',
            ])
            ->findOrFail($run);

        return view('sysadmin.ai.audit.show', [
            'run' => $run,
            'result' => is_array($run->response_json)
                ? $run->response_json
                : [],
        ]);
    }
}
