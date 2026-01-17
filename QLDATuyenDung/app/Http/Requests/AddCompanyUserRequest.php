<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

//
class AddCompanyUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'role_in_company' => 'nullable|in:Admin,Recruiter,Viewer',
        ];
    }
}
