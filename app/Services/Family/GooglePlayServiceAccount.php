<?php

namespace App\Services\Family;

use App\Exceptions\GooglePlayPurchaseException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use JsonException;

class GooglePlayServiceAccount
{
    public function accessToken(): string
    {
        $credentials = $this->credentials();

        $cacheKey = 'google_play:oauth_token:'.hash(
            'sha256',
            (string) $credentials['client_email']
        );

        return Cache::remember(
            $cacheKey,
            now()->addSeconds(
                max(
                    60,
                    (int) config(
                        'family_memberships.google_play'
                        .'.token_cache_seconds',
                        3300
                    )
                )
            ),
            fn (): string => (
                $this->requestAccessToken(
                    $credentials
                )
            )
        );
    }

    private function requestAccessToken(
        array $credentials,
    ): string {
        $tokenUri = (string) (
            $credentials['token_uri']
            ?? config(
                'family_memberships.google_play'
                .'.oauth_token_uri'
            )
        );

        $assertion = $this->signedAssertion(
            credentials: $credentials,
            tokenUri: $tokenUri,
        );

        $response = Http::asForm()
            ->acceptJson()
            ->timeout($this->timeoutSeconds())
            ->retry(
                times: 2,
                sleepMilliseconds: 300,
                throw: false,
            )
            ->post($tokenUri, [
                'grant_type' => (
                    'urn:ietf:params:oauth:'
                    .'grant-type:jwt-bearer'
                ),

                'assertion' => $assertion,
            ]);

        if (! $response->successful()) {
            throw new GooglePlayPurchaseException(
                errorCode: 'GOOGLE_PLAY_OAUTH_FAILED',
                message: (
                    'Google Play rechazó la autenticación '
                    .'de la cuenta de servicio.'
                ),
                httpStatus: 502,
                context: [
                    'google_status' => $response->status(),
                    'google_body' => $response->json(),
                ],
            );
        }

        $token = trim(
            (string) $response->json('access_token')
        );

        if ($token === '') {
            throw new GooglePlayPurchaseException(
                errorCode: 'GOOGLE_PLAY_OAUTH_TOKEN_MISSING',
                message: (
                    'Google Play no devolvió un token '
                    .'de acceso válido.'
                ),
                httpStatus: 502,
            );
        }

        return $token;
    }

    private function credentials(): array
    {
        $path = (string) config(
            'family_memberships.google_play'
            .'.service_account_json'
        );

        if (
            $path === ''
            || ! is_file($path)
            || ! is_readable($path)
        ) {
            throw new GooglePlayPurchaseException(
                errorCode: (
                    'GOOGLE_PLAY_SERVICE_ACCOUNT_MISSING'
                ),
                message: (
                    'No se encontró el archivo JSON de '
                    .'la cuenta de servicio de Google Play.'
                ),
                httpStatus: 503,
                context: [
                    'configured_path' => $path,
                ],
            );
        }

        try {
            $credentials = json_decode(
                file_get_contents($path),
                true,
                flags: JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            throw new GooglePlayPurchaseException(
                errorCode: (
                    'GOOGLE_PLAY_SERVICE_ACCOUNT_INVALID'
                ),
                message: (
                    'El archivo de la cuenta de servicio '
                    .'no contiene JSON válido.'
                ),
                httpStatus: 503,
                previous: $exception,
            );
        }

        foreach (
            [
                'client_email',
                'private_key',
            ] as $requiredField
        ) {
            if (
                ! is_string(
                    $credentials[$requiredField] ?? null
                )
                || trim(
                    $credentials[$requiredField]
                ) === ''
            ) {
                throw new GooglePlayPurchaseException(
                    errorCode: (
                        'GOOGLE_PLAY_SERVICE_ACCOUNT_INVALID'
                    ),
                    message: (
                        'La cuenta de servicio no contiene '
                        .$requiredField.'.'
                    ),
                    httpStatus: 503,
                );
            }
        }

        return $credentials;
    }

    private function signedAssertion(
        array $credentials,
        string $tokenUri,
    ): string {
        $now = time();

        $header = $this->base64UrlEncode(
            json_encode(
                [
                    'alg' => 'RS256',
                    'typ' => 'JWT',
                ],
                JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
            )
        );

        $payload = $this->base64UrlEncode(
            json_encode(
                [
                    'iss' => $credentials['client_email'],

                    'scope' => (string) config(
                        'family_memberships.google_play'
                        .'.oauth_scope'
                    ),

                    'aud' => $tokenUri,
                    'iat' => $now,
                    'exp' => $now + 3600,
                ],
                JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
            )
        );

        $unsignedToken = $header.'.'.$payload;

        $privateKey = openssl_pkey_get_private(
            $credentials['private_key']
        );

        if ($privateKey === false) {
            throw new GooglePlayPurchaseException(
                errorCode: (
                    'GOOGLE_PLAY_PRIVATE_KEY_INVALID'
                ),
                message: (
                    'La llave privada de la cuenta de '
                    .'servicio no es válida.'
                ),
                httpStatus: 503,
            );
        }

        $signature = '';

        $signed = openssl_sign(
            $unsignedToken,
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA256
        );

        if (! $signed) {
            throw new GooglePlayPurchaseException(
                errorCode: (
                    'GOOGLE_PLAY_JWT_SIGN_FAILED'
                ),
                message: (
                    'No se pudo firmar la solicitud '
                    .'OAuth para Google Play.'
                ),
                httpStatus: 503,
            );
        }

        return $unsignedToken.'.'
            .$this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(
        string $value,
    ): string {
        return rtrim(
            strtr(
                base64_encode($value),
                '+/',
                '-_'
            ),
            '='
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