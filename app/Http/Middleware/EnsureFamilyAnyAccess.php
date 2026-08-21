<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Family\FamilyAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFamilyAnyAccess
{
    public function __construct(
        private readonly FamilyAccessService $familyAccessService,
    ) {
    }

    public function handle(
        Request $request,
        Closure $next,
    ): Response {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'ok' => false,
                'code' => 'UNAUTHENTICATED',
                'message' => 'Sesión no válida.',
            ], 401);
        }

        if ($user->role !== 'guardian') {
            return response()->json([
                'ok' => false,
                'code' => 'FAMILY_ROLE_REQUIRED',
                'message' => (
                    'El usuario no tiene acceso a '
                    .'PaseLista Family.'
                ),
            ], 403);
        }

        $snapshot = $request->attributes->get(
            'family_access_snapshot'
        );

        if (! is_array($snapshot)) {
            $snapshot = $this->familyAccessService
                ->snapshotForUser($user);

            $request->attributes->set(
                'family_access_snapshot',
                $snapshot
            );
        }

        $hasAccess = (bool) data_get(
            $snapshot,
            'access.has_any_full_access',
            false
        );

        if ($hasAccess) {
            return $next($request);
        }

        return response()->json([
            'ok' => false,

            'code' => 'FAMILY_MEMBERSHIP_REQUIRED',

            'message' => (
                'Activa PaseLista Family para utilizar '
                .'esta función.'
            ),

            'membership' => $snapshot['membership']
                ?? null,

            'access' => [
                'can_purchase' => (bool) data_get(
                    $snapshot,
                    'access.can_purchase',
                    false
                ),

                'show_paywall' => (bool) data_get(
                    $snapshot,
                    'access.show_paywall',
                    false
                ),

                'annual_price' => data_get(
                    $snapshot,
                    'access.annual_price'
                ),

                'currency' => data_get(
                    $snapshot,
                    'access.currency'
                ),

                'google_play_product_id' => data_get(
                    $snapshot,
                    'access.google_play_product_id'
                ),

                'google_play_base_plan_id' => data_get(
                    $snapshot,
                    'access.google_play_base_plan_id'
                ),
            ],

            /*
             * Se conserva información básica para que Android pueda
             * presentar correctamente el paywall.
             */
            'students' => collect(
                $snapshot['students'] ?? []
            )
                ->map(function (array $student): array {
                    return [
                        'id' => $student['id'] ?? null,
                        'name' => $student['name'] ?? null,
                        'photo_url' => (
                            $student['photo_url'] ?? null
                        ),
                        'level' => $student['level'] ?? null,
                        'group' => $student['group'] ?? null,
                        'relationship' => (
                            $student['relationship'] ?? null
                        ),
                        'relationship_role' => (
                            $student['relationship_role']
                            ?? null
                        ),
                        'access' => $student['access']
                            ?? null,
                    ];
                })
                ->values(),
        ], 403);
    }
}