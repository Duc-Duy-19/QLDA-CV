<?php

namespace App\Http\Requests\Candidate;

use Illuminate\Foundation\Http\FormRequest;

//
class ApplyJobRequest extends FormRequest
{
    // Cho phép bất kỳ user đã xác thực, không bắt buộc phải có role = candidate
    public function authorize()
    {
        return $this->user() !== null;
    }

    public function rules()
    {
        return [
            'job_id' => 'required|exists:jobs,id',
            'resume_id' => 'nullable|exists:resumes,id|required_without:cv',
            'cv' => 'nullable|file|mimes:pdf|max:10240|required_without:resume_id',
            'cover_letter' => 'nullable|string|max:5000',

            // thông tin applicant khi user chưa có candidate (frontend có thể gửi hoặc backend sẽ lấy từ user)
            'applicant_name'  => 'nullable|string|max:255',
            'applicant_email' => 'nullable|email|max:255',
        ];
    }

    public function messages()
    {
        return [
            'job_id.required' => 'Job là bắt buộc',
            'resume_id.exists' => 'Resume không tồn tại',
            'cv.mimes' => 'Chỉ chấp nhận file PDF',

            'applicant_name.string' => 'Tên người nộp không hợp lệ',
            'applicant_name.max' => 'Tên người nộp không được vượt quá 255 ký tự',
            'applicant_email.email' => 'Email người nộp không hợp lệ',
            'applicant_email.max' => 'Email người nộp không được vượt quá 255 ký tự',
        ];
    }
}
