<?php

namespace App\Http\Requests\Sysadmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSchoolAdministratorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'superadmin';
    }

    public function rules(): array
    {
        $administratorId = (int) $this->route('administrator');

        return [
            'name' => [
                'required',
                'string',
                'max:160',
            ],

            'email' => [
                'required',
                'email',
                'max:180',
                Rule::unique('users', 'email')
                    ->ignore($administratorId),
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('users', 'phone')
                    ->ignore($administratorId),
            ],

            'role' => [
                'required',
                Rule::in([
                    'director',
                    'school_admin',
                ]),
            ],

            'status' => [
                'required',
                Rule::in([
                    'active',
                    'blocked',
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' =>
                'El nombre es obligatorio.',

            'email.required' =>
                'El correo es obligatorio.',

            'email.email' =>
                'El correo no tiene un formato válido.',

            'email.unique' =>
                'Este correo ya está registrado en otra cuenta.',

            'phone.unique' =>
                'Este teléfono ya está registrado en otra cuenta.',

            'role.required' =>
                'Selecciona un rol.',

            'role.in' =>
                'El rol seleccionado no es válido.',

            'status.required' =>
                'Selecciona un estado.',

            'status.in' =>
                'El estado seleccionado no es válido.',
        ];
    }
}