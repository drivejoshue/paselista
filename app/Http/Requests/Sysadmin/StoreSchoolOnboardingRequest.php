<?php

namespace App\Http\Requests\Sysadmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreSchoolOnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->role === 'superadmin'
            && $user->school_id === null
            && $user->status === 'active';
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'auto_renew' => $this->boolean('auto_renew'),
            'schedule_enabled' => $this->boolean('schedule_enabled'),
            'requires_guardian_scan' =>
                $this->boolean('requires_guardian_scan'),
            'create_director' => $this->boolean('create_director'),
            'create_prefect' => $this->boolean('create_prefect'),
            'create_kiosk' => $this->boolean('create_kiosk'),
        ]);
    }

    public function rules(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Institución
            |--------------------------------------------------------------------------
            */

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'legal_name' => [
                'nullable',
                'string',
                'max:180',
            ],

            'slug' => [
                'nullable',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('schools', 'slug'),
            ],

            'status' => [
                'required',
                Rule::in([
                    'active',
                    'suspended',
                ]),
            ],

            'timezone' => [
                'required',
                'timezone',
                'max:64',
            ],

            'logo' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg,webp',
                'max:4096',
                'dimensions:min_width=128,min_height=128,max_width=4096,max_height=4096',
            ],

            'primary_color' => [
                'required',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],

            'secondary_color' => [
                'required',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],

            'contact_name' => [
                'nullable',
                'string',
                'max:160',
            ],

            'contact_email' => [
                'nullable',
                'email',
                'max:180',
            ],

            'contact_phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'address' => [
                'nullable',
                'string',
                'max:255',
            ],

            'tax_id' => [
                'nullable',
                'string',
                'max:30',
            ],

            'support_email' => [
                'nullable',
                'email',
                'max:180',
            ],

            'whatsapp_number' => [
                'nullable',
                'string',
                'max:30',
            ],

            /*
            |--------------------------------------------------------------------------
            | Licencia
            |--------------------------------------------------------------------------
            */

            'subscription_plan_id' => [
                'required',
                'integer',
                Rule::exists('subscription_plans', 'id')
                    ->where(
                        fn ($query) =>
                            $query->where('status', 'active')
                    ),
            ],

            'license_status' => [
                'required',
                Rule::in([
                    'trial',
                    'active',
                ]),
            ],

            'license_starts_at' => [
                'required',
                'date',
            ],

            'trial_days' => [
                Rule::requiredIf(
                    fn (): bool =>
                        $this->input('license_status') === 'trial'
                ),
                'nullable',
                'integer',
                'min:1',
                'max:365',
            ],

            'billing_cycle' => [
                Rule::requiredIf(
                    fn (): bool =>
                        $this->input('license_status') === 'active'
                ),
                'nullable',
                Rule::in([
                    'monthly',
                    'annual',
                    'custom',
                ]),
            ],

            'license_expires_at' => [
                Rule::requiredIf(
                    fn (): bool =>
                        $this->input('license_status') === 'active'
                        && $this->input('billing_cycle') === 'custom'
                ),
                'nullable',
                'date',
                'after_or_equal:license_starts_at',
            ],

            'contract_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],

            'auto_renew' => [
                'required',
                'boolean',
            ],

            'license_notes' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'student_limit' => [
                'nullable',
                'integer',
                'min:1',
                'max:1000000',
            ],

            'device_limit' => [
                'nullable',
                'integer',
                'min:1',
                'max:100000',
            ],

            'staff_limit' => [
                'nullable',
                'integer',
                'min:1',
                'max:100000',
            ],

            'campus_limit' => [
                'nullable',
                'integer',
                'min:1',
                'max:10000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Ciclo, plantel y estructura
            |--------------------------------------------------------------------------
            */

            'cycle_name' => [
                'required',
                'string',
                'max:100',
            ],

            'cycle_starts_on' => [
                'required',
                'date',
            ],

            'cycle_ends_on' => [
                'required',
                'date',
                'after:cycle_starts_on',
            ],

            'cycle_status' => [
                'required',
                Rule::in([
                    'active',
                    'draft',
                ]),
            ],

            'cycle_notes' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'campus_name' => [
                'required',
                'string',
                'max:150',
            ],

            'campus_address' => [
                'nullable',
                'string',
                'max:255',
            ],

            'structure_lines' => [
                'required',
                'string',
                'max:20000',
            ],

            'requires_guardian_scan' => [
                'required',
                'boolean',
            ],

            'auto_transition_minutes' => [
                'required',
                'integer',
                'min:0',
                'max:1440',
            ],

            'schedule_enabled' => [
                'required',
                'boolean',
            ],

            'weekdays' => [
                Rule::requiredIf(
                    fn (): bool =>
                        $this->boolean('schedule_enabled')
                ),
                'nullable',
                'array',
                'min:1',
            ],

            'weekdays.*' => [
                'integer',
                Rule::in([
                    1,
                    2,
                    3,
                    4,
                    5,
                    6,
                    7,
                ]),
            ],

            'entry_time' => [
                Rule::requiredIf(
                    fn (): bool =>
                        $this->boolean('schedule_enabled')
                ),
                'nullable',
                'date_format:H:i',
            ],

            'grace_until' => [
                Rule::requiredIf(
                    fn (): bool =>
                        $this->boolean('schedule_enabled')
                ),
                'nullable',
                'date_format:H:i',
            ],

            'late_until' => [
                Rule::requiredIf(
                    fn (): bool =>
                        $this->boolean('schedule_enabled')
                ),
                'nullable',
                'date_format:H:i',
            ],

            'exit_time' => [
                Rule::requiredIf(
                    fn (): bool =>
                        $this->boolean('schedule_enabled')
                ),
                'nullable',
                'date_format:H:i',
            ],

            /*
            |--------------------------------------------------------------------------
            | Cuenta responsable obligatoria
            |--------------------------------------------------------------------------
            */

            'admin_name' => [
                'required',
                'string',
                'max:255',
            ],

            'admin_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],

            'admin_phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'admin_password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->numbers(),
            ],

            /*
            |--------------------------------------------------------------------------
            | Usuarios iniciales opcionales
            |--------------------------------------------------------------------------
            */

            'create_director' => [
                'required',
                'boolean',
            ],

            'director_name' => [
                Rule::requiredIf(
                    fn (): bool =>
                        $this->boolean('create_director')
                ),
                'nullable',
                'string',
                'max:255',
            ],

            'director_email' => [
                Rule::requiredIf(
                    fn (): bool =>
                        $this->boolean('create_director')
                ),
                'nullable',
                'email',
                'max:255',
                'different:admin_email',
                Rule::unique('users', 'email'),
            ],

            'director_phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'director_password' => [
                Rule::requiredIf(
                    fn (): bool =>
                        $this->boolean('create_director')
                ),
                'nullable',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->numbers(),
            ],

            'create_prefect' => [
                'required',
                'boolean',
            ],

            'prefect_name' => [
                Rule::requiredIf(
                    fn (): bool =>
                        $this->boolean('create_prefect')
                ),
                'nullable',
                'string',
                'max:255',
            ],

            'prefect_email' => [
                Rule::requiredIf(
                    fn (): bool =>
                        $this->boolean('create_prefect')
                ),
                'nullable',
                'email',
                'max:255',
                'different:admin_email',
                'different:director_email',
                Rule::unique('users', 'email'),
            ],

            'prefect_phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'prefect_password' => [
                Rule::requiredIf(
                    fn (): bool =>
                        $this->boolean('create_prefect')
                ),
                'nullable',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->numbers(),
            ],

            'create_kiosk' => [
                'required',
                'boolean',
            ],

            'kiosk_name' => [
                Rule::requiredIf(
                    fn (): bool =>
                        $this->boolean('create_kiosk')
                ),
                'nullable',
                'string',
                'max:255',
            ],

            'kiosk_email' => [
                Rule::requiredIf(
                    fn (): bool =>
                        $this->boolean('create_kiosk')
                ),
                'nullable',
                'email',
                'max:255',
                'different:admin_email',
                'different:director_email',
                'different:prefect_email',
                Rule::unique('users', 'email'),
            ],

            'kiosk_password' => [
                Rule::requiredIf(
                    fn (): bool =>
                        $this->boolean('create_kiosk')
                ),
                'nullable',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->numbers(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'logo.image' =>
                'El logotipo debe ser una imagen válida.',
            'logo.mimes' =>
                'El logotipo debe ser PNG, JPG, JPEG o WebP.',
            'logo.max' =>
                'El logotipo no puede superar 4 MB.',
            'logo.dimensions' =>
                'El logotipo debe medir entre 128 y 4096 píxeles por lado.',
            'structure_lines.required' =>
                'Escribe al menos un nivel y sus grupos.',
            'cycle_ends_on.after' =>
                'La fecha final del ciclo debe ser posterior a la inicial.',
        ];
    }
}
