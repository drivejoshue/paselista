<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Family\FamilySponsorshipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FamilySponsorshipController extends Controller
{
    public function __construct(
        private readonly FamilySponsorshipService $sponsorshipService,
    ) {
    }

    public function summary(
        Request $request,
    ): JsonResponse {
        /** @var User|null $user */
        $user = $request->user();

        if (
            $user === null
            || $user->school_id === null
        ) {
            return response()->json([
                'ok' => false,
                'message' => (
                    'No existe contexto de institución.'
                ),
            ], 403);
        }

        return response()->json([
            'ok' => true,

            'data' => $this->sponsorshipService
                ->summary(
                    schoolId: (int) $user->school_id
                ),
        ]);
    }

    public function store(
        Request $request,
        int $guardian,
    ): JsonResponse|RedirectResponse {
        $data = $request->validate([
            'reason' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $result = $this->sponsorshipService
            ->assign(
                actor: $user,
                guardianId: $guardian,
                reason: $data['reason'] ?? null,
            );

        if (
            $request->expectsJson()
            || $request->is('api/*')
        ) {
            return response()->json([
                'ok' => true,
                'data' => $result,
            ], $result['created'] ? 201 : 200);
        }

        return back()->with(
            'success',
            $result['message']
        );
    }
}