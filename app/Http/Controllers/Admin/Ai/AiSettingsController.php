<?php

namespace App\Http\Controllers\Admin\Ai;

use App\Http\Controllers\Controller;
use App\Services\Ai\AiSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AiSettingsController extends Controller
{
    public function __construct(
        private readonly AiSettingsService $settingsService
    ) {
    }

    public function edit(Request $request): View
    {
        $this->authorizeManager($request);
        $schoolId = (int) $request->user()->school_id;

        return view('admin.ai.settings', [
            'settings' => $this->settingsService->forSchool($schoolId),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeManager($request);
        $schoolId = (int) $request->user()->school_id;
        $userId = (int) $request->user()->id;

        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'default_model' => ['required', Rule::in(['fast', 'deep'])],
            'allow_pro' => ['nullable', 'boolean'],
            'monthly_query_limit' => ['required', 'integer', 'min:1', 'max:100000'],
            'max_range_days' => ['required', 'integer', 'min:7', 'max:366'],
            'allow_school_analysis' => ['nullable', 'boolean'],
            'allow_group_analysis' => ['nullable', 'boolean'],
            'allow_student_analysis' => ['nullable', 'boolean'],
        ]);

        $existing = DB::table('ai_settings')
            ->where('school_id', $schoolId)
            ->first();

        $payload = [
            'enabled' => $request->boolean('enabled'),
            'default_model' => $validated['default_model'],
            'allow_pro' => $request->boolean('allow_pro'),
            'monthly_query_limit' => (int) $validated['monthly_query_limit'],
            'max_range_days' => (int) $validated['max_range_days'],
            'allow_school_analysis' => $request->boolean('allow_school_analysis'),
            'allow_group_analysis' => $request->boolean('allow_group_analysis'),
            'allow_student_analysis' => $request->boolean('allow_student_analysis'),
            'updated_by' => $userId,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('ai_settings')
                ->where('school_id', $schoolId)
                ->update($payload);
        } else {
            DB::table('ai_settings')->insert([
                'school_id' => $schoolId,
                ...$payload,
                'created_by' => $userId,
                'created_at' => now(),
            ]);
        }

        return redirect()
            ->route('admin.ai.settings.edit')
            ->with('success', 'Configuración de SchoolPass IA actualizada.');
    }

    private function authorizeManager(Request $request): void
    {
        abort_unless(
            in_array(
                $request->user()?->role,
                ['superadmin', 'school_admin'],
                true
            ),
            403,
            'Solo la administración escolar puede configurar la IA.'
        );
    }
}
