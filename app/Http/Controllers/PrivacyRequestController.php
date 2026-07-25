<?php

namespace App\Http\Controllers;

use App\Models\PrivacyRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class PrivacyRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_if(
            trim((string) $request->input('website')) !== '',
            422,
            'No fue posible registrar la solicitud.'
        );

        $validated = $request->validate([
            'request_type' => [
                'required',
                Rule::in([
                    'access',
                    'rectification',
                    'cancellation',
                    'opposition',
                    'account_deletion',
                    'data_deletion',
                    'data_copy',
                    'security_report',
                    'other',
                ]),
            ],
            'full_name' => ['required', 'string', 'max:180'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'relationship' => [
                'nullable',
                Rule::in([
                    'guardian',
                    'staff',
                    'student_representative',
                    'school',
                    'other',
                ]),
            ],
            'school_name' => ['nullable', 'string', 'max:190'],
            'account_reference' => ['nullable', 'string', 'max:190'],
            'description' => ['required', 'string', 'min:20', 'max:3000'],
            'privacy_acknowledgement' => ['accepted'],
        ], [
            'privacy_acknowledgement.accepted' =>
                'Debes confirmar que leíste el aviso de privacidad.',
        ]);

        $privacyRequest = PrivacyRequest::query()->create([
            ...$validated,
            'request_code' => (string) Str::uuid(),
            'status' => 'pending',
            'ip_hash' => $request->ip()
                ? hash_hmac(
                    'sha256',
                    $request->ip(),
                    (string) config('app.key')
                )
                : null,
            'user_agent' => Str::limit(
                (string) $request->userAgent(),
                500,
                ''
            ),
        ]);

        $this->notifyPrivacyTeam($privacyRequest);

        return back()->with(
            'privacy_request_status',
            'Solicitud registrada. Folio: '.$privacyRequest->request_code
        );
    }

    private function notifyPrivacyTeam(
        PrivacyRequest $privacyRequest
    ): void {
        $recipient = trim((string) config(
            'schoolpass_public.privacy_notification_email'
        ));

        if ($recipient === '') {
            return;
        }

        try {
            Mail::raw(
                implode("\n", [
                    'Nueva solicitud de privacidad SchoolPass',
                    '',
                    'Folio: '.$privacyRequest->request_code,
                    'Tipo: '.$privacyRequest->request_type,
                    'Nombre: '.$privacyRequest->full_name,
                    'Correo: '.$privacyRequest->email,
                    'Institución: '.($privacyRequest->school_name ?: 'No indicada'),
                    '',
                    'Consulta el registro en la base de datos privacy_requests.',
                ]),
                function ($message) use ($recipient, $privacyRequest): void {
                    $message
                        ->to($recipient)
                        ->subject(
                            'Solicitud de privacidad '.$privacyRequest->request_code
                        );
                }
            );
        } catch (Throwable $exception) {
            report($exception);

            Log::warning('No se pudo enviar la notificación de privacidad.', [
                'privacy_request_id' => $privacyRequest->id,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
