<?php

namespace App\Http\Requests\Employer;

use Illuminate\Foundation\Http\FormRequest;

//
class UpdateJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'requirements' => 'nullable|string',
            'salary_range' => 'nullable|string|max:255',
            'location' => 'sometimes|string|max:255',
            'employment_type' => 'sometimes|string|max:255',
            'expiration_date' => 'sometimes|date|after:today',
            'status' => 'sometimes|in:open,closed',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
        ];
    }
}
