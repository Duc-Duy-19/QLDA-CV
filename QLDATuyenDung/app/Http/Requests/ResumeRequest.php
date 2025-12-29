<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

//
class ResumeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'title' => 'nullable|string|max:255',
            'photo' => 'nullable|string', // Base64 hoặc URL
            'personal_info' => 'nullable|string',
            'skills_summary' => 'nullable|string', // Mục tiêu nghề nghiệp
        ];

        // Validation cho header (optional khi tạo CV)
        if ($this->has('header')) {
            $rules['header.Full_name'] = 'nullable|string|max:255';
            $rules['header.BirthDay'] = 'nullable|date';
            $rules['header.gender'] = 'nullable|in:male,female,other';
            $rules['header.Phone'] = 'nullable|string|max:20';
            $rules['header.Email'] = 'nullable|email|max:255';
            $rules['header.Website'] = ['nullable', 'max:255', function ($attribute, $value, $fail) {
                // ✅ FIX: Xử lý tốt hơn các trường hợp edge case khi deploy
                if ($value !== null && $value !== '') {
                    // Trim và kiểm tra lại sau khi trim
                    $trimmedValue = is_string($value) ? trim($value) : $value;

                    // Nếu sau khi trim là rỗng, coi như null (pass validation)
                    if ($trimmedValue === '') {
                        return;
                    }

                    // Chấp nhận URL có hoặc không có protocol
                    $urlPattern = '/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/i';
                    if (
                        !preg_match($urlPattern, $trimmedValue)
                        && !filter_var($trimmedValue, FILTER_VALIDATE_URL)
                        && !filter_var('http://' . $trimmedValue, FILTER_VALIDATE_URL)
                    ) {
                        $fail('Trường ' . $attribute . ' phải là URL hợp lệ.');
                    }
                }
            }];
            $rules['header.address'] = 'nullable|string';
            $rules['header.avatar'] = 'nullable|string';
        }

        // Validation cho educations (optional array)
        if ($this->has('educations')) {
            $rules['educations'] = 'nullable|array';
            $rules['educations.*.school_name'] = 'nullable|string|max:255';
            $rules['educations.*.degree'] = 'nullable|string|max:255';
            $rules['educations.*.major'] = 'nullable|string|max:255';
            $rules['educations.*.start_date'] = 'nullable|date';
            $rules['educations.*.end_date'] = 'nullable|date|after_or_equal:educations.*.start_date';
            $rules['educations.*.description'] = 'nullable|string';
        }

        // Validation cho experiences (optional array)
        if ($this->has('experiences')) {
            $rules['experiences'] = 'nullable|array';
            $rules['experiences.*.company_name'] = 'nullable|string|max:255';
            $rules['experiences.*.position'] = 'nullable|string|max:255';
            $rules['experiences.*.start_date'] = 'nullable|date';
            $rules['experiences.*.end_date'] = 'nullable|date|after_or_equal:experiences.*.start_date';
            $rules['experiences.*.description'] = 'nullable|string';
        }

        // Validation cho activities (optional array)
        if ($this->has('activities')) {
            $rules['activities'] = 'nullable|array';
            $rules['activities.*.organization_name'] = 'nullable|string|max:255';
            $rules['activities.*.role'] = 'nullable|string|max:255';
            $rules['activities.*.start_date'] = 'nullable|date';
            $rules['activities.*.end_date'] = 'nullable|date|after_or_equal:activities.*.start_date';
            $rules['activities.*.description'] = 'nullable|string';
        }

        // Validation cho certifications (optional array)
        if ($this->has('certifications')) {
            $rules['certifications'] = 'nullable|array';
            $rules['certifications.*.cert_name'] = 'nullable|string|max:255';
            $rules['certifications.*.organization'] = 'nullable|string|max:255';
            $rules['certifications.*.date_received'] = 'nullable|date';
            $rules['certifications.*.description'] = 'nullable|string';
        }

        // Validation cho awards (optional array)
        if ($this->has('awards')) {
            $rules['awards'] = 'nullable|array';
            $rules['awards.*.award_name'] = 'nullable|string|max:255';
            $rules['awards.*.organization'] = 'nullable|string|max:255';
            $rules['awards.*.date_received'] = 'nullable|date';
            $rules['awards.*.description'] = 'nullable|string';
        }

        // Validation cho skills (optional array)
        if ($this->has('skills')) {
            $rules['skills'] = 'nullable|array';
            $rules['skills.*.skill_name'] = 'nullable|string|max:255';
        }

        // Validation cho references (optional array)
        if ($this->has('references')) {
            $rules['references'] = 'nullable|array';
            $rules['references.*.name'] = 'nullable|string|max:255';
            $rules['references.*.relationship'] = 'nullable|string|max:255';
            $rules['references.*.contact_info'] = 'nullable|string|max:500';
        }

        // Validation cho projects (optional array)
        if ($this->has('projects')) {
            $rules['projects'] = 'nullable|array';
            $rules['projects.*.project_name'] = 'nullable|string|max:255';
            $rules['projects.*.role'] = 'nullable|string|max:255';
            $rules['projects.*.technologies'] = 'nullable|string|max:255';
            $rules['projects.*.start_date'] = 'nullable|date';
            $rules['projects.*.end_date'] = 'nullable|date|after_or_equal:projects.*.start_date';
            $rules['projects.*.description'] = 'nullable|string';
        }

        // Validation cho hobbies (optional array)
        if ($this->has('hobbies')) {
            $rules['hobbies'] = 'nullable|array';
            $rules['hobbies.*.description'] = 'nullable|string';
        }

        // Validation cho extrainfos (optional array)
        if ($this->has('extrainfos')) {
            $rules['extrainfos'] = 'nullable|array';
            $rules['extrainfos.*.content'] = 'nullable|string';
        }

        return $rules;
    }
}
