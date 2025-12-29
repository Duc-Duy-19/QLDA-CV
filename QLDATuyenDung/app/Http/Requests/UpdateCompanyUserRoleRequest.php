<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

//
class UpdateCompanyUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role_in_company' => 'required|in:Admin,Recruiter,Viewer',
            'status' => 'nullable|in:pending,active,inactive',
        ];
    }
}
