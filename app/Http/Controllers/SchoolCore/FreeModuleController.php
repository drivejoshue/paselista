<?php
declare(strict_types=1);

namespace App\Http\Controllers\SchoolCore;

use App\Http\Controllers\Controller;
use App\Services\SchoolCore\FreeModuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FreeModuleController extends Controller
{
    public function __construct(
        private readonly FreeModuleService $modules,
    ) {
    }

    public function update(
        Request $request,
        string $code
    ): JsonResponse {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $schoolId = (int) ($request->user()?->school_id ?? 0);

        abort_unless($schoolId > 0, 403);

        $this->modules->setEnabled(
            $schoolId,
            $code,
            (bool) $validated['enabled']
        );

        return response()->json([
            'message' => 'Configuración del módulo actualizada.',
            'module' => $code,
            'active' => (bool) $validated['enabled'],
        ]);
    }
}
