<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\GooglePlayPurchaseException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Family\GooglePlaySubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FamilyGooglePlayController extends Controller
{
    public function __construct(
        private readonly GooglePlaySubscriptionService $subscriptions,
    ) {
    }

    public function configuration(
        Request $request,
    ): JsonResponse {
        return response()->json([
            'ok' => true,

            'data' => $this->subscriptions
                ->configuration(
                    $this->userOrFail($request)
                ),
        ]);
    }

    public function verify(
        Request $request,
    ): JsonResponse {
        return $this->process(
            request: $request,
            mode: 'verify',
        );
    }

    public function restore(
        Request $request,
    ): JsonResponse {
        return $this->process(
            request: $request,
            mode: 'restore',
        );
    }

    private function process(
        Request $request,
        string $mode,
    ): JsonResponse {
        $data = $request->validate([
            'package_name' => [
                'required',
                'string',
                'max:255',
            ],

            'product_id' => [
                'required',
                'string',
                'max:150',
            ],

            'purchase_token' => [
                'required',
                'string',
                'max:4096',
            ],
        ]);

        try {
            $result = $this->subscriptions->verify(
                user: $this->userOrFail($request),

                packageName: (
                    $data['package_name']
                ),

                productId: $data['product_id'],

                purchaseToken: (
                    $data['purchase_token']
                ),

                mode: $mode,
            );
        } catch (
            GooglePlayPurchaseException $exception
        ) {
            return response()->json([
                'ok' => false,

                'code' => $exception->errorCode,

                'message' => $exception->getMessage(),

                'context' => app()->environment(
                    'local'
                )
                    ? $exception->context
                    : null,
            ], $exception->httpStatus);
        }

        return response()->json([
            'ok' => true,
            'data' => $result,
        ]);
    }

    private function userOrFail(
        Request $request,
    ): User {
        $user = $request->user();

        abort_unless(
            $user instanceof User
            && $user->role === 'guardian',
            403,
            'Usuario no autorizado para Family.'
        );

        return $user;
    }
}