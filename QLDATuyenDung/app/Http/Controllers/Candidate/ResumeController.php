<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use App\Models\ResumeHeader;
use App\Models\ResumeEducation;
use App\Models\ResumeExperience;
use App\Models\ResumeActivity;
use App\Models\ResumeCertification;
use App\Models\ResumeAward;
use App\Models\ResumeSkill;
use App\Models\ResumeReference;
use App\Models\ResumeProject;
use App\Models\ResumeHobby;
use App\Models\ResumeExtrainfo;
use App\Http\Requests\ResumeRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ResumeController extends Controller
{
    /**
     * Lấy danh sách Resume của user đang đăng nhập
     */
    public function index()
    {
        $user = Auth::user();
        $resumes = Resume::where('user_id', $user->id)
            ->with(['header', 'educations', 'experiences', 'activities', 'certifications', 'awards', 'skills', 'references', 'projects', 'hobbies', 'extraInfos'])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($resumes->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Chưa có CV nào',
                'data' => []
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách CV thành công',
            'data' => $resumes
        ], 200);
    }

    /**
     * Lấy chi tiết Resume với tất cả relations
     */
    public function show($id)
    {
        $user = Auth::user();
        $resume = Resume::with([
            'header',
            'educations',
            'experiences',
            'activities',
            'certifications',
            'awards',
            'skills',
            'references',
            'projects',
            'hobbies',
            'extraInfos'
        ])->findOrFail($id);

        // Kiểm tra quyền truy cập
        if ($resume->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập CV này'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lấy chi tiết CV thành công',
            'data' => $resume
        ], 200);
    }

    /**
     * Tạo CV mới (có thể tạo kèm header và các sections)
     */
    public function store(ResumeRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();

        DB::beginTransaction();
        try {
            // Tạo Resume chính
            $resumeData = [
                'user_id' => $user->id,
                'title' => $data['title'] ?? null,
                'personal_info' => $data['personal_info'] ?? null,
                'skills_summary' => $data['skills_summary'] ?? null, // Mục tiêu nghề nghiệp
            ];

            $resume = Resume::create($resumeData);

            // Tạo Header nếu có
            if ($request->has('header') && !empty($request->header)) {
                $headerData = $request->header;
                $headerData['resume_id'] = $resume->id;

                // Xử lý avatar nếu có file upload
                if ($request->hasFile('header.avatar')) {
                    $file = $request->file('header.avatar');
                    $base64 = base64_encode(file_get_contents($file->getRealPath()));
                    $mime = $file->getMimeType();
                    $headerData['avatar'] = 'data:' . $mime . ';base64,' . $base64;
                }

                ResumeHeader::create($headerData);
            }

            // Tạo Educations nếu có
            if ($request->has('educations') && is_array($request->educations)) {
                foreach ($request->educations as $edu) {
                    $edu['resume_id'] = $resume->id;
                    ResumeEducation::create($edu);
                }
            }

            // Tạo Experiences nếu có
            if ($request->has('experiences') && is_array($request->experiences)) {
                foreach ($request->experiences as $exp) {
                    $exp['resume_id'] = $resume->id;
                    ResumeExperience::create($exp);
                }
            }

            // Tạo Activities nếu có
            if ($request->has('activities') && is_array($request->activities)) {
                foreach ($request->activities as $activity) {
                    $activity['resume_id'] = $resume->id;
                    ResumeActivity::create($activity);
                }
            }

            // Tạo Certifications nếu có
            if ($request->has('certifications') && is_array($request->certifications)) {
                foreach ($request->certifications as $cert) {
                    $cert['resume_id'] = $resume->id;
                    ResumeCertification::create($cert);
                }
            }

            // Tạo Awards nếu có
            if ($request->has('awards') && is_array($request->awards)) {
                foreach ($request->awards as $award) {
                    $award['resume_id'] = $resume->id;
                    ResumeAward::create($award);
                }
            }

            // Tạo Skills nếu có
            if ($request->has('skills') && is_array($request->skills)) {
                foreach ($request->skills as $skill) {
                    $skill['resume_id'] = $resume->id;
                    ResumeSkill::create($skill);
                }
            }

            // Tạo References nếu có
            if ($request->has('references') && is_array($request->references)) {
                foreach ($request->references as $ref) {
                    $ref['resume_id'] = $resume->id;
                    ResumeReference::create($ref);
                }
            }

            // Tạo Projects nếu có
            if ($request->has('projects') && is_array($request->projects)) {
                foreach ($request->projects as $project) {
                    $project['resume_id'] = $resume->id;
                    ResumeProject::create($project);
                }
            }

            // Tạo Hobbies nếu có
            if ($request->has('hobbies') && is_array($request->hobbies)) {
                foreach ($request->hobbies as $hobby) {
                    $hobby['resume_id'] = $resume->id;
                    ResumeHobby::create($hobby);
                }
            }

            // Tạo Extrainfos nếu có
            if ($request->has('extrainfos') && is_array($request->extrainfos)) {
                foreach ($request->extrainfos as $extrainfo) {
                    $extrainfo['resume_id'] = $resume->id;
                    ResumeExtrainfo::create($extrainfo);
                }
            }

            DB::commit();

            // Load lại với tất cả relations
            $resume->load([
                'header',
                'educations',
                'experiences',
                'activities',
                'certifications',
                'awards',
                'skills',
                'references',
                'projects',
                'hobbies',
                'extraInfos'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tạo CV thành công',
                'data' => $resume
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo CV: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật Resume
     */
    public function update(ResumeRequest $request, $id)
    {
        $user = Auth::user();
        $resume = Resume::findOrFail($id);

        // Kiểm tra quyền truy cập
        if ($resume->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền cập nhật CV này'
            ], 403);
        }

        $data = $request->validated();
        
        // ✅ DEBUG: Log request data để kiểm tra khi deploy
        Log::info('Updating resume', [
            'resume_id' => $id,
            'user_id' => $user->id,
            'has_header' => $request->has('header'),
            'has_educations' => $request->has('educations'),
            'educations_count' => $request->has('educations') ? (is_array($request->educations) ? count($request->educations) : 'not_array') : 0,
            'has_experiences' => $request->has('experiences'),
            'experiences_count' => $request->has('experiences') ? (is_array($request->experiences) ? count($request->experiences) : 'not_array') : 0,
            'request_all_keys' => array_keys($request->all()),
        ]);

        DB::beginTransaction();
        try {
            // Cập nhật Resume chính
            $resumeData = [
                'title' => $data['title'] ?? $resume->title,
                'photo' => $data['photo'] ?? $resume->photo,
                'personal_info' => $data['personal_info'] ?? $resume->personal_info,
                'skills_summary' => $data['skills_summary'] ?? $resume->skills_summary,
            ];
            $resume->update($resumeData);

            // ✅ FIX: Cập nhật Header nếu có
            if ($request->has('header')) {
                $headerData = $request->input('header');
                
                // ✅ FIX: Xử lý trường hợp header là JSON string (khi gửi từ frontend)
                if (is_string($headerData)) {
                    $headerData = json_decode($headerData, true);
                }
                
                if (!empty($headerData) && is_array($headerData)) {
                    if ($resume->header) {
                        $resume->header->update($headerData);
                    } else {
                        $headerData['resume_id'] = $resume->id;
                        ResumeHeader::create($headerData);
                    }
                }
            }

            // ✅ FIX: Cập nhật Educations (xóa cũ, tạo mới)
            if ($request->has('educations')) {
                $educations = $request->input('educations');
                
                // ✅ FIX: Xử lý trường hợp educations là JSON string
                if (is_string($educations)) {
                    $educations = json_decode($educations, true);
                }
                
                $resume->educations()->delete();
                if (is_array($educations) && count($educations) > 0) {
                    foreach ($educations as $edu) {
                        if (is_array($edu)) {
                            $edu['resume_id'] = $resume->id;
                            ResumeEducation::create($edu);
                        }
                    }
                }
            }

            // ✅ FIX: Cập nhật Experiences
            if ($request->has('experiences')) {
                $experiences = $request->input('experiences');
                
                // ✅ FIX: Xử lý trường hợp experiences là JSON string
                if (is_string($experiences)) {
                    $experiences = json_decode($experiences, true);
                }
                
                $resume->experiences()->delete();
                if (is_array($experiences) && count($experiences) > 0) {
                    foreach ($experiences as $exp) {
                        if (is_array($exp)) {
                            $exp['resume_id'] = $resume->id;
                            ResumeExperience::create($exp);
                        }
                    }
                }
            }

            // ✅ FIX: Cập nhật Activities
            if ($request->has('activities')) {
                $activities = $request->input('activities');
                if (is_string($activities)) $activities = json_decode($activities, true);
                
                $resume->activities()->delete();
                if (is_array($activities) && count($activities) > 0) {
                    foreach ($activities as $activity) {
                        if (is_array($activity)) {
                            $activity['resume_id'] = $resume->id;
                            ResumeActivity::create($activity);
                        }
                    }
                }
            }

            // ✅ FIX: Cập nhật Certifications
            if ($request->has('certifications')) {
                $certifications = $request->input('certifications');
                if (is_string($certifications)) $certifications = json_decode($certifications, true);
                
                $resume->certifications()->delete();
                if (is_array($certifications) && count($certifications) > 0) {
                    foreach ($certifications as $cert) {
                        if (is_array($cert)) {
                            $cert['resume_id'] = $resume->id;
                            ResumeCertification::create($cert);
                        }
                    }
                }
            }

            // ✅ FIX: Cập nhật Awards
            if ($request->has('awards')) {
                $awards = $request->input('awards');
                if (is_string($awards)) $awards = json_decode($awards, true);
                
                $resume->awards()->delete();
                if (is_array($awards) && count($awards) > 0) {
                    foreach ($awards as $award) {
                        if (is_array($award)) {
                            $award['resume_id'] = $resume->id;
                            ResumeAward::create($award);
                        }
                    }
                }
            }

            // ✅ FIX: Cập nhật Skills
            if ($request->has('skills')) {
                $skills = $request->input('skills');
                if (is_string($skills)) $skills = json_decode($skills, true);
                
                $resume->skills()->delete();
                if (is_array($skills) && count($skills) > 0) {
                    foreach ($skills as $skill) {
                        if (is_array($skill)) {
                            $skill['resume_id'] = $resume->id;
                            ResumeSkill::create($skill);
                        }
                    }
                }
            }

            // ✅ FIX: Cập nhật References
            if ($request->has('references')) {
                $references = $request->input('references');
                if (is_string($references)) $references = json_decode($references, true);
                
                $resume->references()->delete();
                if (is_array($references) && count($references) > 0) {
                    foreach ($references as $ref) {
                        if (is_array($ref)) {
                            $ref['resume_id'] = $resume->id;
                            ResumeReference::create($ref);
                        }
                    }
                }
            }

            // ✅ FIX: Cập nhật Projects
            if ($request->has('projects')) {
                $projects = $request->input('projects');
                if (is_string($projects)) $projects = json_decode($projects, true);
                
                $resume->projects()->delete();
                if (is_array($projects) && count($projects) > 0) {
                    foreach ($projects as $project) {
                        if (is_array($project)) {
                            $project['resume_id'] = $resume->id;
                            ResumeProject::create($project);
                        }
                    }
                }
            }

            // ✅ FIX: Cập nhật Hobbies
            if ($request->has('hobbies')) {
                $hobbies = $request->input('hobbies');
                if (is_string($hobbies)) $hobbies = json_decode($hobbies, true);
                
                $resume->hobbies()->delete();
                if (is_array($hobbies) && count($hobbies) > 0) {
                    foreach ($hobbies as $hobby) {
                        if (is_array($hobby)) {
                            $hobby['resume_id'] = $resume->id;
                            ResumeHobby::create($hobby);
                        }
                    }
                }
            }

            // ✅ FIX: Cập nhật Extrainfos
            if ($request->has('extrainfos')) {
                $extrainfos = $request->input('extrainfos');
                if (is_string($extrainfos)) $extrainfos = json_decode($extrainfos, true);
                
                $resume->extraInfos()->delete();
                if (is_array($extrainfos) && count($extrainfos) > 0) {
                    foreach ($extrainfos as $extrainfo) {
                        if (is_array($extrainfo)) {
                            $extrainfo['resume_id'] = $resume->id;
                            ResumeExtrainfo::create($extrainfo);
                        }
                    }
                }
            }

            DB::commit();

            // Load lại với tất cả relations
            $resume->load([
                'header',
                'educations',
                'experiences',
                'activities',
                'certifications',
                'awards',
                'skills',
                'references',
                'projects',
                'hobbies',
                'extraInfos'
            ]);

            // ✅ FIX: Convert avatar path thành URL đầy đủ nếu là path (không phải base64)
            if ($resume->header && $resume->header->avatar) {
                // Nếu avatar là base64 (bắt đầu bằng data:), giữ nguyên
                // Nếu avatar là path (không bắt đầu bằng data: hoặc http), convert thành URL
                $avatar = $resume->header->avatar;
                if (strpos($avatar, 'data:') !== 0 && strpos($avatar, 'http') !== 0) {
                    $resume->header->avatar = Storage::disk('public')->url($avatar);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật CV thành công',
                'data' => $resume
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating resume', [
                'resume_id' => $id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật CV: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa Resume
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $resume = Resume::findOrFail($id);

        // Kiểm tra quyền truy cập
        if ($resume->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xóa CV này'
            ], 403);
        }

        $resume->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa CV thành công'
        ], 200);
    }
}
