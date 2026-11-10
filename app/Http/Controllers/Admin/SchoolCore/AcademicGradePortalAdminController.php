<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\SchoolCore;

use App\Http\Controllers\Controller;
use App\Services\SchoolCore\ModuleAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class AcademicGradePortalAdminController extends Controller
{
    public function __construct(
        private readonly ModuleAccessService $modules
    ) {}

    private function schoolId(Request $request): int
    {
        $user = $request->user();

        abort_unless(
            $user &&
            $user->school_id &&
            in_array(
                (string) $user->role,
                ['director', 'school_admin'],
                true
            ),
            403
        );

        $schoolId = (int) $user->school_id;

        abort_unless(
            $this->modules->canAccess(
                $schoolId,
                'academic'
            ),
            403
        );

        return $schoolId;
    }

    public function index(Request $request): JsonResponse
    {
        $schoolId = $this->schoolId($request);

        $school = DB::table('schools')
            ->where('id', $schoolId)
            ->where('status', 'active')
            ->first(['id', 'name', 'slug']);

        abort_unless($school, 404);

        $enabled = (bool) DB::table(
            'academic_grade_portal_settings'
        )
            ->where('school_id', $schoolId)
            ->value('enabled');

        return response()->json([
            'school_name' => $school->name,
            'url' => route('school.portal.show', [
                'school' => $school->slug,
            ]),
            'enabled' => $enabled,
        ])->header('Cache-Control', 'no-store, private');
    }

    public function settings(Request $request): JsonResponse
    {
        $schoolId = $this->schoolId($request);

        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $enabled = (bool) $data['enabled'];

        DB::table('academic_grade_portal_settings')
            ->updateOrInsert(
                ['school_id' => $schoolId],
                [
                    'enabled' => $enabled,
                    'updated_at' => now(),
                ]
            );

        return response()->json([
            'enabled' => $enabled,
            'message' => $enabled
                ? 'Consulta pública habilitada.'
                : 'Consulta pública deshabilitada.',
        ]);
    }
}
