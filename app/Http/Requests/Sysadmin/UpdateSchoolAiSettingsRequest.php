<?php

namespace App\Http\Requests\Sysadmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSchoolAiSettingsRequest extends FormRequest
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
            'enabled' => $this->boolean('enabled'),
            'allow_pro' => $this->boolean('allow_pro'),
            'allow_school_analysis' => $this->boolean('allow_school_analysis'),
            'allow_group_analysis' => $this->boolean('allow_group_analysis'),
            'allow_student_analysis' => $this->boolean('allow_student_analysis'),
        ]);
    }

    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'default_model' => ['required', Rule::in(['fast', 'pro'])],
            'allow_pro' => ['required', 'boolean'],
            'monthly_query_limit' => ['required', 'integer', 'min:1', 'max:100000'],
            'max_range_days' => ['required', 'integer', 'min:1', 'max:730'],
            'allow_school_analysis' => ['required', 'boolean'],
            'allow_group_analysis' => ['required', 'boolean'],
            'allow_student_analysis' => ['required', 'boolean'],
        ];
    }
}
