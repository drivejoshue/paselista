<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Attendance\AttendancePeriodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Services\Attendance\DirectionLiveSnapshotService;

class DirectionLiveController extends Controller
{
    public function __construct(
      private readonly DirectionLiveSnapshotService $liveSnapshot
    ) {
    }

    public function index(Request $request): View
{
    $schoolId = $this->schoolId($request);
    $filters = $this->validatedFilters($request, $schoolId);

    $school = DB::table('schools')
        ->where('id', $schoolId)
        ->firstOrFail();

    $activeCycle = $this->activeCycle($schoolId);

    return view('admin.direction-live.direction-live', [
        'school' => $school,
        'filters' => $filters,

        'campuses' => $this->campuses(
            schoolId: $schoolId,
            cycleId: $activeCycle?->id
        ),

        'levels' => $this->levels(
            schoolId: $schoolId,
            cycleId: $activeCycle?->id
        ),

        'groups' => $this->groups(
            schoolId: $schoolId,
            cycleId: $activeCycle?->id,
            campusId: $filters['campus_id'],
            levelId: $filters['level_id']
        ),
    ]);
}

   public function data(
    Request $request
): JsonResponse {
    $schoolId = $this->schoolId($request);

    $filters = $this->validatedFilters(
        $request,
        $schoolId
    );

    return response()
        ->json(
            $this->liveSnapshot->snapshot(
                schoolId: $schoolId,
                filters: $filters
            )
        )
        ->header(
            'Cache-Control',
            'no-store, no-cache, must-revalidate, max-age=0'
        );
}

 

   

    private function activeCycle(int $schoolId): ?object
    {
        return DB::table('academic_cycles')
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->where('is_active', true)
            ->first();
    }

    private function campuses(int $schoolId, ?int $cycleId): Collection
    {
        if (! $cycleId) {
            return collect();
        }

        return DB::table('campuses as c')
            ->join('school_groups as sg', 'sg.campus_id', '=', 'c.id')
            ->where('c.school_id', $schoolId)
            ->where('c.status', 'active')
            ->where('sg.school_id', $schoolId)
            ->where('sg.academic_cycle_id', $cycleId)
            ->where('sg.status', 'active')
            ->select(['c.id', 'c.name'])
            ->distinct()
            ->orderBy('c.name')
            ->get();
    }

    private function levels(int $schoolId, ?int $cycleId): Collection
    {
        if (! $cycleId) {
            return collect();
        }

        return DB::table('academic_levels as al')
            ->join('school_groups as sg', 'sg.academic_level_id', '=', 'al.id')
            ->where('al.school_id', $schoolId)
            ->where('al.status', 'active')
            ->where('sg.school_id', $schoolId)
            ->where('sg.academic_cycle_id', $cycleId)
            ->where('sg.status', 'active')
            ->select(['al.id', 'al.name', 'al.sort_order'])
            ->distinct()
            ->orderBy('al.sort_order')
            ->orderBy('al.name')
            ->get();
    }

    private function groups(
        int $schoolId,
        ?int $cycleId,
        ?int $campusId,
        ?int $levelId
    ): Collection {
        if (! $cycleId) {
            return collect();
        }

        return DB::table('school_groups as sg')
            ->join('campuses as c', 'c.id', '=', 'sg.campus_id')
            ->leftJoin('academic_levels as al', 'al.id', '=', 'sg.academic_level_id')
            ->where('sg.school_id', $schoolId)
            ->where('sg.academic_cycle_id', $cycleId)
            ->where('sg.status', 'active')
            ->when($campusId, fn ($query, $id) => $query->where('sg.campus_id', $id))
            ->when($levelId, fn ($query, $id) => $query->where('sg.academic_level_id', $id))
            ->select([
                'sg.id',
                'sg.name',
                'c.name as campus_name',
                'al.name as level_name',
                'al.sort_order',
            ])
            ->orderBy('c.name')
            ->orderBy('al.sort_order')
            ->orderBy('sg.name')
            ->get();
    }

   


private function validatedFilters(
    Request $request,
    int $schoolId
): array {
    $validated = $request->validate([
        'campus_id' => [
            'nullable',
            'integer',
            Rule::exists('campuses', 'id')
                ->where('school_id', $schoolId),
        ],

        'level_id' => [
            'nullable',
            'integer',
            Rule::exists('academic_levels', 'id')
                ->where('school_id', $schoolId),
        ],

        'group_id' => [
            'nullable',
            'integer',
            Rule::exists('school_groups', 'id')
                ->where('school_id', $schoolId),
        ],
    ]);

    return [
        'campus_id' => ! empty($validated['campus_id'])
            ? (int) $validated['campus_id']
            : null,

        'level_id' => ! empty($validated['level_id'])
            ? (int) $validated['level_id']
            : null,

        'group_id' => ! empty($validated['group_id'])
            ? (int) $validated['group_id']
            : null,
    ];
}


    private function schoolId(Request $request): int
    {
        $user = $request->user();
        abort_unless($user && $user->school_id, 403);

        return (int) $user->school_id;
    }


}
