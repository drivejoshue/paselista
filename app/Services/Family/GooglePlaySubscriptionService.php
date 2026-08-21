<?php

namespace App\Services\Family;

use App\Exceptions\GooglePlayPurchaseException;
use App\Models\GuardianMembership;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GooglePlaySubscriptionService
{
    public function __construct(
        private readonly GooglePlayServiceAccount $serviceAccount,
        private readonly FamilyAccessService $familyAccessService,
    ) {
    }

    public function configuration(
        User $user,
    ): array {
        $guardian = $this->guardianOrFail($user);

        $snapshot = $this->familyAccessService
            ->snapshotForUser($user);

        return [
            'enabled' => (bool) config(
                'family_memberships.google_play.enabled',
                false
            ),

            'package_name' => $this->packageName(),

            'product_id' => $this->productId(),

            'base_plan_id' => $this->basePlanId(),

            'product_type' => 'subscription',

            'plan_type' => 'prepaid',

            'annual_price' => (float) config(
                'family_memberships.annual_price',
                149
            ),

            'currency' => (string) config(
                'family_memberships.currency',
                'MXN'
            ),

            'can_purchase' => (bool) data_get(
                $snapshot,
                'access.can_purchase',
                false
            ),

            'can_restore' => true,

            'obfuscated_account_id' => (
                $this->obfuscatedAccountId(
                    user: $user,
                    guardianId: (int) $guardian->id,
                )
            ),
        ];
    }

    public function verify(
        User $user,
        string $packageName,
        string $productId,
        string $purchaseToken,
        string $mode = 'verify',
    ): array {
        $this->assertEnabled();

        $guardian = $this->guardianOrFail($user);

        $packageName = trim($packageName);
        $productId = trim($productId);
        $purchaseToken = trim($purchaseToken);

        $this->assertExpectedProduct(
            packageName: $packageName,
            productId: $productId,
        );

        if ($purchaseToken === '') {
            throw new GooglePlayPurchaseException(
                errorCode: 'PURCHASE_TOKEN_REQUIRED',
                message: 'Falta el token de compra.',
                httpStatus: 422,
            );
        }

        $purchase = $this->fetchSubscription(
            packageName: $packageName,
            purchaseToken: $purchaseToken,
        );

        $parsed = $this->parsePurchase(
            purchase: $purchase,
            productId: $productId,
        );

        $this->assertExternalAccount(
            purchase: $purchase,
            expectedAccountId: (
                $this->obfuscatedAccountId(
                    user: $user,
                    guardianId: (int) $guardian->id,
                )
            ),
        );

        $tokenHash = hash(
            'sha256',
            $purchaseToken
        );

        $membership = $this->persistPurchase(
            user: $user,
            guardianId: (int) $guardian->id,
            packageName: $packageName,
            productId: $productId,
            purchaseToken: $purchaseToken,
            tokenHash: $tokenHash,
            purchase: $purchase,
            parsed: $parsed,
        );

        $acknowledgementPending = false;

        if (
            $parsed['entitled']
            && $parsed['acknowledgement_state']
                === 'ACKNOWLEDGEMENT_STATE_PENDING'
        ) {
            try {
                $this->acknowledge(
                    packageName: $packageName,
                    productId: $productId,
                    purchaseToken: $purchaseToken,
                );

                $membership->forceFill([
                    'google_play_acknowledgement_state' => (
                        'ACKNOWLEDGEMENT_STATE_ACKNOWLEDGED'
                    ),

                    'updated_by' => $user->id,
                ])->save();
            } catch (
                GooglePlayPurchaseException $exception
            ) {
                $acknowledgementPending = true;

                Log::error(
                    'No se pudo confirmar una compra '
                    .'de PaseLista Family.',
                    [
                        'membership_id' => $membership->id,
                        'guardian_id' => $guardian->id,
                        'school_id' => $user->school_id,
                        'error_code' => (
                            $exception->errorCode
                        ),
                        'context' => $exception->context,
                    ]
                );
            }
        }

        if (! $parsed['entitled']) {
            throw new GooglePlayPurchaseException(
                errorCode: (
                    'GOOGLE_PLAY_SUBSCRIPTION_NOT_ACTIVE'
                ),
                message: (
                    'La suscripción no concede acceso '
                    .'en su estado actual.'
                ),
                httpStatus: 409,
                context: [
                    'subscription_state' => (
                        $parsed['subscription_state']
                    ),
                    'expires_at' => (
                        $parsed['expires_at']
                            ?->toIso8601String()
                    ),
                ],
            );
        }

        return [
            'mode' => $mode,

            'membership' => [
                'id' => (int) $membership->id,
                'status' => $membership->status,
                'source' => $membership->source,

                'starts_at' => $membership->starts_at
                    ?->toDateString(),

                'expires_at' => $membership->expires_at
                    ?->toDateString(),

                'google_play_started_at' => (
                    $membership->google_play_started_at
                        ?->toIso8601String()
                ),

                'google_play_expires_at' => (
                    $membership->google_play_expires_at
                        ?->toIso8601String()
                ),

                'usable' => $membership->isUsable(),

                'subscription_state' => (
                    $membership
                        ->google_play_subscription_state
                ),

                'acknowledgement_state' => (
                    $membership
                        ->google_play_acknowledgement_state
                ),

                'acknowledgement_pending' => (
                    $acknowledgementPending
                ),

                'is_test_purchase' => (
                    (bool) $membership
                        ->google_play_is_test_purchase
                ),
            ],

            'access' => $this->familyAccessService
                ->snapshotForUser($user),
        ];
    }

    public function reconcileMembership(
        GuardianMembership $membership,
    ): array {
        $this->assertEnabled();

        $membership->refresh();

        if ($membership->source !== 'google_play') {
            throw new GooglePlayPurchaseException(
                errorCode: 'GOOGLE_PLAY_MEMBERSHIP_REQUIRED',
                message: (
                    'La membresía no corresponde a '
                    .'Google Play.'
                ),
                httpStatus: 422,
            );
        }

        if ($membership->revoked_at !== null) {
            throw new GooglePlayPurchaseException(
                errorCode: 'MEMBERSHIP_MANUALLY_REVOKED',
                message: (
                    'La membresía fue revocada manualmente '
                    .'y no se conciliará automáticamente.'
                ),
                httpStatus: 409,
            );
        }

        $encryptedToken = trim(
            (string) $membership
                ->google_play_purchase_token_encrypted
        );

        if ($encryptedToken === '') {
            throw new GooglePlayPurchaseException(
                errorCode: 'PURCHASE_TOKEN_UNAVAILABLE',
                message: (
                    'La membresía no conserva un token de '
                    .'compra para conciliar.'
                ),
                httpStatus: 409,
            );
        }

        try {
            $purchaseToken = Crypt::decryptString(
                $encryptedToken
            );
        } catch (\Throwable $exception) {
            throw new GooglePlayPurchaseException(
                errorCode: 'PURCHASE_TOKEN_DECRYPT_FAILED',
                message: (
                    'No fue posible recuperar el token de '
                    .'compra almacenado.'
                ),
                httpStatus: 500,
                context: [
                    'membership_id' => $membership->id,
                    'exception' => $exception->getMessage(),
                ],
            );
        }

        $packageName = trim(
            (string) (
                $membership->google_play_package_name
                ?: $this->packageName()
            )
        );

        $productId = trim(
            (string) (
                $membership->google_play_product_id
                ?: $this->productId()
            )
        );

        $this->assertExpectedProduct(
            packageName: $packageName,
            productId: $productId,
        );

        $purchase = $this->fetchSubscription(
            packageName: $packageName,
            purchaseToken: $purchaseToken,
        );

        $parsed = $this->parsePurchase(
            purchase: $purchase,
            productId: $productId,
        );

        $guardianUser = DB::table('guardians as guardian')
            ->join(
                'users as user',
                'user.id',
                '=',
                'guardian.user_id'
            )
            ->where('guardian.id', $membership->guardian_id)
            ->where('guardian.school_id', $membership->school_id)
            ->where('guardian.status', 'active')
            ->where('user.status', 'active')
            ->select([
                'user.id',
                'user.school_id',
                'user.role',
            ])
            ->first();

        if ($guardianUser !== null) {
            $user = new User();
            $user->forceFill([
                'id' => (int) $guardianUser->id,
                'school_id' => (int) $guardianUser->school_id,
                'role' => (string) $guardianUser->role,
            ]);
            $user->exists = true;

            $this->assertExternalAccount(
                purchase: $purchase,
                expectedAccountId: (
                    $this->obfuscatedAccountId(
                        user: $user,
                        guardianId: (
                            (int) $membership->guardian_id
                        ),
                    )
                ),
            );
        }

        $membership = DB::transaction(
            function () use (
                $membership,
                $packageName,
                $productId,
                $purchaseToken,
                $purchase,
                $parsed,
            ): GuardianMembership {
                $lockedMembership = GuardianMembership::query()
                    ->whereKey($membership->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedMembership->forceFill([
                    'status' => $parsed['entitled']
                        ? 'active'
                        : 'expired',

                    'starts_at' => $parsed['starts_at']
                        ?->toDateString(),

                    'expires_at' => $parsed['expires_at']
                        ?->toDateString(),

                    'google_play_started_at' => (
                        $parsed['starts_at']
                            ?->copy()
                            ->setTimezone(
                                (string) config(
                                    'app.timezone',
                                    'UTC'
                                )
                            )
                    ),

                    'google_play_expires_at' => (
                        $parsed['expires_at']
                            ?->copy()
                            ->setTimezone(
                                (string) config(
                                    'app.timezone',
                                    'UTC'
                                )
                            )
                    ),

                    'google_play_package_name' => $packageName,
                    'google_play_product_id' => $productId,
                    'google_play_base_plan_id' => (
                        $parsed['base_plan_id']
                    ),
                    'google_play_order_id' => (
                        $parsed['latest_order_id']
                    ),
                    'google_play_purchase_token_hash' => hash(
                        'sha256',
                        $purchaseToken
                    ),
                    'google_play_linked_purchase_token_hash' => (
                        $parsed['linked_purchase_token']
                            ? hash(
                                'sha256',
                                $parsed[
                                    'linked_purchase_token'
                                ]
                            )
                            : null
                    ),
                    'google_play_subscription_state' => (
                        $parsed['subscription_state']
                    ),
                    'google_play_acknowledgement_state' => (
                        $parsed['acknowledgement_state']
                    ),
                    'google_play_auto_renewing' => (
                        $parsed['auto_renewing']
                    ),
                    'google_play_region_code' => (
                        $parsed['region_code']
                    ),
                    'google_play_is_test_purchase' => (
                        $parsed['is_test_purchase']
                    ),
                    'google_play_payload' => $purchase,
                    'last_verified_at' => now(),
                ])->save();

                return $lockedMembership->fresh();
            },
            3
        );

        $acknowledgementPending = false;

        if (
            $parsed['entitled']
            && $parsed['acknowledgement_state']
                === 'ACKNOWLEDGEMENT_STATE_PENDING'
        ) {
            try {
                $this->acknowledge(
                    packageName: $packageName,
                    productId: $productId,
                    purchaseToken: $purchaseToken,
                );

                $membership->forceFill([
                    'google_play_acknowledgement_state' => (
                        'ACKNOWLEDGEMENT_STATE_ACKNOWLEDGED'
                    ),
                ])->save();
            } catch (
                GooglePlayPurchaseException $exception
            ) {
                $acknowledgementPending = true;

                Log::error(
                    'No se pudo confirmar una membresía '
                    .'Family durante la conciliación.',
                    [
                        'membership_id' => $membership->id,
                        'school_id' => $membership->school_id,
                        'guardian_id' => $membership->guardian_id,
                        'error_code' => $exception->errorCode,
                        'context' => $exception->context,
                    ]
                );
            }
        }

        $membership->refresh();

        return [
            'membership_id' => (int) $membership->id,
            'status' => $membership->status,
            'usable' => $membership->isUsable(),
            'subscription_state' => (
                $membership->google_play_subscription_state
            ),
            'acknowledgement_state' => (
                $membership
                    ->google_play_acknowledgement_state
            ),
            'acknowledgement_pending' => (
                $acknowledgementPending
            ),
            'google_play_started_at' => (
                $membership->google_play_started_at
                    ?->toIso8601String()
            ),
            'google_play_expires_at' => (
                $membership->google_play_expires_at
                    ?->toIso8601String()
            ),
            'last_verified_at' => (
                $membership->last_verified_at
                    ?->toIso8601String()
            ),
        ];
    }

    private function persistPurchase(
        User $user,
        int $guardianId,
        string $packageName,
        string $productId,
        string $purchaseToken,
        string $tokenHash,
        array $purchase,
        array $parsed,
    ): GuardianMembership {
        return DB::transaction(
            function () use (
                $user,
                $guardianId,
                $packageName,
                $productId,
                $purchaseToken,
                $tokenHash,
                $purchase,
                $parsed,
            ): GuardianMembership {
                $tokenOwner = GuardianMembership::query()
                    ->where(
                        'google_play_purchase_token_hash',
                        $tokenHash
                    )
                    ->lockForUpdate()
                    ->first();

                if (
                    $tokenOwner !== null
                    && (
                        (int) $tokenOwner->school_id
                            !== (int) $user->school_id
                        || (int) $tokenOwner->guardian_id
                            !== $guardianId
                    )
                ) {
                    throw new GooglePlayPurchaseException(
                        errorCode: (
                            'PURCHASE_TOKEN_ALREADY_LINKED'
                        ),
                        message: (
                            'La compra ya está vinculada '
                            .'con otra familia.'
                        ),
                        httpStatus: 409,
                    );
                }

                $membership = GuardianMembership::query()
                    ->where(
                        'school_id',
                        $user->school_id
                    )
                    ->where(
                        'guardian_id',
                        $guardianId
                    )
                    ->lockForUpdate()
                    ->first();

                if (
                    $membership !== null
                    && $membership->isUsable()
                    && $membership->source
                        !== 'google_play'
                ) {
                    throw new GooglePlayPurchaseException(
                        errorCode: (
                            'MEMBERSHIP_ALREADY_ACTIVE'
                        ),
                        message: (
                            'La familia ya cuenta con una '
                            .'membresía vigente que no debe '
                            .'ser reemplazada.'
                        ),
                        httpStatus: 409,
                        context: [
                            'current_source' => (
                                $membership->source
                            ),
                        ],
                    );
                }

                if ($membership === null) {
                    $membership = new GuardianMembership();

                    $membership->created_by = $user->id;
                }

                $status = $parsed['entitled']
                    ? 'active'
                    : 'expired';

                $membership->forceFill([
                    'school_id' => $user->school_id,
                    'guardian_id' => $guardianId,

                    'academic_cycle_id' => null,

                    'status' => $status,
                    'source' => 'google_play',

                    'starts_at' => $parsed['starts_at']
                        ?->toDateString(),

                    'expires_at' => $parsed['expires_at']
                        ?->toDateString(),

                    'google_play_started_at' => (
                        $parsed['starts_at']
                            ?->copy()
                            ->setTimezone(
                                (string) config(
                                    'app.timezone',
                                    'UTC'
                                )
                            )
                    ),

                    'google_play_expires_at' => (
                        $parsed['expires_at']
                            ?->copy()
                            ->setTimezone(
                                (string) config(
                                    'app.timezone',
                                    'UTC'
                                )
                            )
                    ),

                    'google_play_package_name' => (
                        $packageName
                    ),

                    'google_play_product_id' => (
                        $productId
                    ),

                    'google_play_base_plan_id' => (
                        $parsed['base_plan_id']
                    ),

                    'google_play_order_id' => (
                        $parsed['latest_order_id']
                    ),

                    'google_play_purchase_token_hash' => (
                        $tokenHash
                    ),

                    'google_play_purchase_token_encrypted' => (
                        Crypt::encryptString(
                            $purchaseToken
                        )
                    ),

                    'google_play_linked_purchase_token_hash' => (
                        $parsed['linked_purchase_token']
                            ? hash(
                                'sha256',
                                $parsed[
                                    'linked_purchase_token'
                                ]
                            )
                            : null
                    ),

                    'google_play_subscription_state' => (
                        $parsed['subscription_state']
                    ),

                    'google_play_acknowledgement_state' => (
                        $parsed['acknowledgement_state']
                    ),

                    'google_play_auto_renewing' => (
                        $parsed['auto_renewing']
                    ),

                    'google_play_region_code' => (
                        $parsed['region_code']
                    ),

                    'google_play_is_test_purchase' => (
                        $parsed['is_test_purchase']
                    ),

                    'google_play_payload' => $purchase,

                    'last_verified_at' => now(),

                    'sponsored_by_user_id' => null,

                    'revoked_at' => null,
                    'revoked_by_user_id' => null,
                    'revocation_reason' => null,

                    'updated_by' => $user->id,
                ]);

                $membership->save();

                return $membership->fresh();
            },
            3
        );
    }

    private function fetchSubscription(
        string $packageName,
        string $purchaseToken,
    ): array {
        $url = rtrim(
            (string) config(
                'family_memberships.google_play'
                .'.publisher_base_url'
            ),
            '/'
        )
            .'/applications/'
            .rawurlencode($packageName)
            .'/purchases/subscriptionsv2/tokens/'
            .rawurlencode($purchaseToken);

        $response = Http::withToken(
            $this->serviceAccount->accessToken()
        )
            ->acceptJson()
            ->timeout($this->timeoutSeconds())
            ->retry(
                times: 2,
                sleepMilliseconds: 300,
                throw: false,
            )
            ->get($url);

        if (! $response->successful()) {
            $status = $response->status();

            throw new GooglePlayPurchaseException(
                errorCode: match ($status) {
                    404 => 'GOOGLE_PLAY_PURCHASE_NOT_FOUND',
                    410 => 'GOOGLE_PLAY_PURCHASE_EXPIRED',
                    401, 403 => (
                        'GOOGLE_PLAY_PERMISSION_DENIED'
                    ),
                    default => 'GOOGLE_PLAY_VERIFY_FAILED',
                },

                message: match ($status) {
                    404 => (
                        'Google Play no encontró la compra.'
                    ),

                    410 => (
                        'El token de compra ya no puede '
                        .'consultarse.'
                    ),

                    401, 403 => (
                        'La cuenta de servicio no tiene '
                        .'permisos suficientes en Play Console.'
                    ),

                    default => (
                        'No se pudo verificar la compra '
                        .'con Google Play.'
                    ),
                },

                httpStatus: in_array(
                    $status,
                    [404, 410],
                    true
                )
                    ? 409
                    : 502,

                context: [
                    'google_status' => $status,
                    'google_body' => $response->json(),
                ],
            );
        }

        $purchase = $response->json();

        if (! is_array($purchase)) {
            throw new GooglePlayPurchaseException(
                errorCode: 'GOOGLE_PLAY_RESPONSE_INVALID',
                message: (
                    'Google Play devolvió una respuesta '
                    .'de compra inválida.'
                ),
                httpStatus: 502,
            );
        }

        return $purchase;
    }

    private function parsePurchase(
        array $purchase,
        string $productId,
    ): array {
        $matchingItems = collect(
            $purchase['lineItems'] ?? []
        )
            ->filter(
                fn ($item): bool => (
                    is_array($item)
                    && ($item['productId'] ?? null)
                        === $productId
                    && data_get(
                        $item,
                        'offerDetails.basePlanId'
                    ) === $this->basePlanId()
                )
            )
            ->map(function (array $item): array {
                $expiry = isset($item['expiryTime'])
                    ? Carbon::parse(
                        $item['expiryTime']
                    )
                    : null;

                return [
                    'item' => $item,
                    'expiry' => $expiry,
                ];
            })
            ->sortByDesc(
                fn (array $row): int => (
                    $row['expiry']?->getTimestamp()
                    ?? 0
                )
            )
            ->values();

        if ($matchingItems->isEmpty()) {
            throw new GooglePlayPurchaseException(
                errorCode: (
                    'GOOGLE_PLAY_PRODUCT_MISMATCH'
                ),
                message: (
                    'La compra no corresponde al producto '
                    .'y plan anual configurados.'
                ),
                httpStatus: 409,
            );
        }

        $selected = $matchingItems->first();
        $item = $selected['item'];
        $expiresAt = $selected['expiry'];

        $subscriptionState = (string) (
            $purchase['subscriptionState']
            ?? 'SUBSCRIPTION_STATE_UNSPECIFIED'
        );

        $entitledStates = [
            'SUBSCRIPTION_STATE_ACTIVE',
            'SUBSCRIPTION_STATE_IN_GRACE_PERIOD',
            'SUBSCRIPTION_STATE_CANCELED',
        ];

        $startsAt = isset($purchase['startTime'])
            ? Carbon::parse($purchase['startTime'])
            : null;

        $now = now();

        $entitled = in_array(
            $subscriptionState,
            $entitledStates,
            true
        )
            && $expiresAt !== null
            && $expiresAt->gt($now)
            && (
                $startsAt === null
                || $startsAt->lte($now)
            );

        return [
            'entitled' => $entitled,

            'subscription_state' => (
                $subscriptionState
            ),

            'acknowledgement_state' => (
                (string) (
                    $purchase['acknowledgementState']
                    ?? 'ACKNOWLEDGEMENT_STATE_UNSPECIFIED'
                )
            ),

            'starts_at' => $startsAt,

            'expires_at' => $expiresAt,

            'base_plan_id' => data_get(
                $item,
                'offerDetails.basePlanId'
            ),

            'latest_order_id' => (
                $purchase['latestOrderId']
                ?? $item['latestSuccessfulOrderId']
                ?? null
            ),

            'linked_purchase_token' => (
                $purchase['linkedPurchaseToken']
                ?? null
            ),

            'auto_renewing' => (bool) data_get(
                $item,
                'autoRenewingPlan.autoRenewEnabled',
                false
            ),

            'region_code' => (
                $purchase['regionCode']
                ?? null
            ),

            'is_test_purchase' => (
                array_key_exists(
                    'testPurchase',
                    $purchase
                )
                && $purchase['testPurchase'] !== null
            ),
        ];
    }

    private function acknowledge(
        string $packageName,
        string $productId,
        string $purchaseToken,
    ): void {
        $url = rtrim(
            (string) config(
                'family_memberships.google_play'
                .'.publisher_base_url'
            ),
            '/'
        )
            .'/applications/'
            .rawurlencode($packageName)
            .'/purchases/subscriptions/'
            .rawurlencode($productId)
            .'/tokens/'
            .rawurlencode($purchaseToken)
            .':acknowledge';

        $response = Http::withToken(
            $this->serviceAccount->accessToken()
        )
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeoutSeconds())
            ->retry(
                times: 2,
                sleepMilliseconds: 300,
                throw: false,
            )
            ->post($url, []);

        if (! $response->successful()) {
            throw new GooglePlayPurchaseException(
                errorCode: (
                    'GOOGLE_PLAY_ACKNOWLEDGE_FAILED'
                ),
                message: (
                    'La membresía fue registrada, pero '
                    .'Google Play no confirmó todavía '
                    .'el procesamiento de la compra.'
                ),
                httpStatus: 502,
                context: [
                    'google_status' => $response->status(),
                    'google_body' => $response->json(),
                ],
            );
        }
    }

    private function assertExternalAccount(
        array $purchase,
        string $expectedAccountId,
    ): void {
        $receivedAccountId = trim(
            (string) data_get(
                $purchase,
                'externalAccountIdentifiers'
                .'.obfuscatedExternalAccountId',
                ''
            )
        );

        $required = (bool) config(
            'family_memberships.google_play'
            .'.require_obfuscated_account_id',
            true
        );

        if (
            $receivedAccountId === ''
            && ! $required
        ) {
            return;
        }

        if (
            $receivedAccountId === ''
            || ! hash_equals(
                $expectedAccountId,
                $receivedAccountId
            )
        ) {
            throw new GooglePlayPurchaseException(
                errorCode: (
                    'PURCHASE_ACCOUNT_MISMATCH'
                ),
                message: (
                    'La compra no pertenece a la cuenta '
                    .'Family autenticada.'
                ),
                httpStatus: 409,
            );
        }
    }

    private function assertExpectedProduct(
        string $packageName,
        string $productId,
    ): void {
        if (
            $packageName !== $this->packageName()
            || $productId !== $this->productId()
        ) {
            throw new GooglePlayPurchaseException(
                errorCode: (
                    'GOOGLE_PLAY_PRODUCT_MISMATCH'
                ),
                message: (
                    'El paquete o producto enviado no '
                    .'corresponde a PaseLista Family.'
                ),
                httpStatus: 422,
            );
        }
    }

    private function assertEnabled(): void
    {
        if (
            ! (bool) config(
                'family_memberships.google_play.enabled',
                false
            )
        ) {
            throw new GooglePlayPurchaseException(
                errorCode: 'GOOGLE_PLAY_DISABLED',
                message: (
                    'Las compras de Google Play todavía '
                    .'no están habilitadas.'
                ),
                httpStatus: 503,
            );
        }
    }

    private function guardianOrFail(
        User $user,
    ): object {
        if (
            $user->role !== 'guardian'
            || $user->school_id === null
        ) {
            throw new GooglePlayPurchaseException(
                errorCode: 'FAMILY_USER_REQUIRED',
                message: (
                    'La cuenta no corresponde a '
                    .'PaseLista Family.'
                ),
                httpStatus: 403,
            );
        }

        $guardian = DB::table('guardians')
            ->where('user_id', $user->id)
            ->where(
                'school_id',
                $user->school_id
            )
            ->where('status', 'active')
            ->first();

        if ($guardian === null) {
            throw new GooglePlayPurchaseException(
                errorCode: 'GUARDIAN_PROFILE_REQUIRED',
                message: (
                    'No existe un perfil de tutor activo.'
                ),
                httpStatus: 403,
            );
        }

        $hasPrimaryStudent = DB::table(
            'student_guardians as sg'
        )
            ->join(
                'students as student',
                'student.id',
                '=',
                'sg.student_id'
            )
            ->where(
                'sg.guardian_id',
                $guardian->id
            )
            ->where('sg.status', 'active')
            ->where('sg.is_primary', true)
            ->where(
                'student.school_id',
                $user->school_id
            )
            ->where('student.status', 'active')
            ->exists();

        if (! $hasPrimaryStudent) {
            throw new GooglePlayPurchaseException(
                errorCode: (
                    'PRIMARY_GUARDIAN_REQUIRED'
                ),
                message: (
                    'Solo un tutor principal puede '
                    .'activar la membresía familiar.'
                ),
                httpStatus: 409,
            );
        }

        return $guardian;
    }

    private function obfuscatedAccountId(
        User $user,
        int $guardianId,
    ): string {
        $payload = implode('|', [
            'paselista-family',
            'school:'.(int) $user->school_id,
            'guardian:'.$guardianId,
            'user:'.(int) $user->id,
        ]);

        $appKey = (string) config('app.key');

        if (str_starts_with($appKey, 'base64:')) {
            $decoded = base64_decode(
                substr($appKey, 7),
                true
            );

            if ($decoded !== false) {
                $appKey = $decoded;
            }
        }

        return hash_hmac(
            'sha256',
            $payload,
            $appKey
        );
    }

    private function packageName(): string
    {
        return trim(
            (string) config(
                'family_memberships.google_play'
                .'.package_name'
            )
        );
    }

    private function productId(): string
    {
        return trim(
            (string) config(
                'family_memberships.google_play'
                .'.product_id'
            )
        );
    }

    private function basePlanId(): string
    {
        return trim(
            (string) config(
                'family_memberships.google_play'
                .'.base_plan_id'
            )
        );
    }

    private function timeoutSeconds(): int
    {
        return max(
            5,
            (int) config(
                'family_memberships.google_play'
                .'.timeout_seconds',
                20
            )
        );
    }
}