<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

//
class CandidateUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone'      => 'nullable|string|max:10',
            'address'    => 'nullable|string|max:255',
            'birthday'   => 'nullable|date',
            'gender'     => 'nullable|in:male,female,other',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'status'     => 'nullable|in:active,inactive,suspended',
        ];
    }
    public function messages(): array
    {
        return [
            'phone.max' => 'Số điện thoại không được vượt quá 10 ký tự.',
            'phone.regex' => 'Số điện thoại không hợp lệ, chỉ được chứa chữ số.',
            'address.max' => 'Địa chỉ không được dài quá 255 ký tự.',
            'birthday.date' => 'Ngày sinh phải là ngày hợp lệ.',
            'gender.in' => 'Giới tính phải là Nam, Nữ hoặc Khác.',
            'avatar.image' => 'Tệp tải lên phải là hình ảnh.',
            'avatar.mimes' => 'Ảnh chỉ được định dạng jpg, jpeg, png, gif.',
            'avatar.max' => 'Ảnh không được vượt quá 2MB.',
            'status.in' => 'Trạng thái không hợp lệ.',
        ];
    }
}
