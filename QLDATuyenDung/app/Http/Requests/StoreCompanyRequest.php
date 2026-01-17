<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;


//
class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'website' => 'nullable|url',
            // 'website' => ['nullable', 'regex:/^(https?:\/\/)?([\w.-]+)\.([a-z]{2,})([\/\w .-]*)*\/?$/'],
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // <= validate ảnh
        ];
    }

    public function messages(): array
    {
        return [
            // Tên công ty
            'company_name.required' => 'Vui lòng nhập tên công ty.',
            'company_name.max' => 'Tên công ty không được vượt quá 255 ký tự.',

            // Địa chỉ
            'address.required' => 'Vui lòng nhập địa chỉ công ty.',
            'address.max' => 'Địa chỉ không được vượt quá 255 ký tự.',

            // Mô tả
            'description.required' => 'Vui lòng nhập mô tả công ty.',

            // Website
            'website.required' => 'Vui lòng nhập địa chỉ Website của công ty.',
            'website.regex' => 'Định dạng website không hợp lệ (ví dụ: https://example.com).',

            // Email
            'email.required' => 'Vui lòng nhập email công ty.',
            'email.email' => 'Email công ty không đúng định dạng.',

            // Số điện thoại
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.max' => 'Số điện thoại không được vượt quá 20 ký tự.',

            // Logo
            'logo.image' => 'Logo phải là một tệp hình ảnh.',
            'logo.mimes' => 'Logo phải có định dạng: jpeg, png, jpg, hoặc gif.',
            'logo.max' => 'Logo không được vượt quá 2MB.',

            // Trạng thái (thêm mới)
            'status.required' => 'Vui lòng chọn trạng thái hoạt động.',
        ];
    }
}
