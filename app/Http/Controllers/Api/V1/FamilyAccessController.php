<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Family\FamilyAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FamilyAccessController extends Controller
{
    public function __construct(
        private readonly FamilyAccessService $familyAccessService,
    ) {
    }

    public function __invoke(
        Request $request,
    ): JsonResponse {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Sesión no válida.',
            ], 401);
        }

        return response()->json([
            'ok' => true,
            'data' => $this->familyAccessService
                ->snapshotForUser($user),
        ]);
    }
}