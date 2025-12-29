<?php

namespace App\Http\Requests\Employer;

use Illuminate\Foundation\Http\FormRequest;

//
class StoreJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Cho phép employer tạo job
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'requirements' => 'required|string',
            'salary_range' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'employment_type' => 'required|string|max:255',
            'expiration_date' => 'required|date|after:today',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:categories,id',
        ];
    }

    public function messages(): array
    {
        return [
            // Tiêu đề
            'title.required' => 'Vui lòng nhập tiêu đề công việc.',


            // Mô tả
            'description.required' => 'Vui lòng nhập mô tả chi tiết.',

            // Yêu cầu (Mới)
            'requirements.required' => 'Vui lòng nhập yêu cầu công việc.',

            // Mức lương (Mới)
            'salary_range.required' => 'Vui lòng nhập mức lương.',


            // Địa điểm
            'location.required' => 'Vui lòng nhập địa điểm làm việc.',


            // Hình thức làm việc
            'employment_type.required' => 'Vui lòng chọn hình thức làm việc.',


            // Ngày hết hạn
            'expiration_date.required' => 'Vui lòng chọn ngày hết hạn.',
            'expiration_date.date' => 'Ngày hết hạn không đúng định dạng.',
            'expiration_date.after' => 'Ngày hết hạn phải sau ngày hôm nay.',

            // Danh mục (Mới)
            'category_ids.required' => 'Vui lòng chọn ít nhất một danh mục.',
            'category_ids.array' => 'Định dạng danh mục không hợp lệ.',
            'category_ids.min' => 'Vui lòng chọn ít nhất một danh mục.', // Cho rule 'min:1'

            // Từng danh mục
            'category_ids.*.exists' => 'Một danh mục được chọn không hợp lệ hoặc không tồn tại.',
        ];
    }
}
