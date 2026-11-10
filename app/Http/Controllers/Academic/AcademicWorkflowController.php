<?php
declare(strict_types=1);

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Academic\AcademicWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcademicWorkflowController extends Controller
{
    public function __construct(
        private readonly AcademicWorkflowService $workflow,
    ) {
    }

    private function schoolId(Request $request): int
    {
        $schoolId = (int) ($request->user()?->school_id ?? 0);

        abort_unless($schoolId > 0, 403);

        return $schoolId;
    }

    public function catalog(Request $request): JsonResponse
    {
        return response()->json(
            $this->workflow->catalog($this->schoolId($request))
        );
    }

    public function storePlan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'academic_level_id' => ['required', 'integer', 'min:1'],
            'framework_code' => ['nullable', 'string', 'max:80'],
        ]);

        $id = $this->workflow->createPlan(
            $this->schoolId($request),
            $data
        );

        return response()->json(['id' => $id], 201);
    }

    public function storePlanSubject(Request $request): JsonResponse
    {
        $data = $request->validate([
            'curriculum_plan_id' => ['required', 'integer', 'min:1'],
            'subject_id' => ['required', 'integer', 'min:1'],
            'grade_label' => ['required', 'string', 'max:100'],
            'is_official' => ['required', 'boolean'],
            'affects_average' => ['required', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $id = $this->workflow->addPlanSubject(
            $this->schoolId($request),
            $data
        );

        return response()->json(['id' => $id], 201);
    }

    public function activatePlan(
        Request $request,
        int $plan
    ): JsonResponse {
        $this->workflow->activatePlan(
            $this->schoolId($request),
            $plan
        );

        return response()->json(['message' => 'Plan activado.']);
    }

    public function storeAssignment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'school_group_id' => ['required', 'integer', 'min:1'],
            'plan_subject_id' => ['required', 'integer', 'min:1'],
            'teacher_profile_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $id = $this->workflow->createAssignment(
            $this->schoolId($request),
            $data
        );

        return response()->json(['id' => $id], 201);
    }

    public function activatePeriod(
        Request $request,
        int $period
    ): JsonResponse {
        $this->workflow->activatePeriod(
            $this->schoolId($request),
            $period
        );

        return response()->json(['message' => 'Período activado.']);
    }

    public function storeGradebook(Request $request): JsonResponse
    {
        $data = $request->validate([
            'teaching_assignment_id' => ['required', 'integer', 'min:1'],
            'grading_period_id' => ['required', 'integer', 'min:1'],
        ]);

        $id = $this->workflow->createGradebook(
            $this->schoolId($request),
            $data
        );

        return response()->json(['id' => $id], 201);
    }

    public function roster(
        Request $request,
        int $gradebook
    ): JsonResponse {
        return response()->json(
            $this->workflow->gradebookRoster(
                $this->schoolId($request),
                $gradebook
            )
        );
    }

    public function saveGrade(
        Request $request,
        int $gradebook,
        int $enrollment
    ): JsonResponse {
        $data = $request->validate([
            'numeric_value' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999.999',
            ],
            'text_value' => ['nullable', 'string', 'max:80'],
            'comment' => ['nullable', 'string', 'max:5000'],
        ]);

        if (
            ($data['numeric_value'] ?? null) === null
            && blank($data['text_value'] ?? null)
        ) {
            return response()->json([
                'message' => 'Captura una calificación numérica o textual.',
            ], 422);
        }

        $this->workflow->saveGrade(
            $this->schoolId($request),
            $gradebook,
            $enrollment,
            (int) $request->user()->id,
            $data
        );

        return response()->json([
            'message' => 'Calificación guardada y auditada.',
        ]);
    }
}
