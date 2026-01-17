<?php
// filepath: c:\laragon\www\websitetuyendungthucte\webcv\app\Http\Controllers\Employer\JobController.php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Company;
use App\Models\CompanyUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Employer\StoreJobRequest;
use App\Http\Requests\Employer\UpdateJobRequest;

class JobController extends Controller
{
    //
    /**
     * Danh sách job của công ty hiện tại
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $company = $this->getUserCompany($user->id);

        if (!$company) {
            return response()->json(['error' => 'Bạn chưa thuộc công ty nào'], 403);
        }

        $query = Job::where('company_id', $company->id)
            ->with(['categories:id,name']);

        // Lọc theo status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Tìm kiếm theo title
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $jobs = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json($jobs);
    }

    /**
     * Tạo job mới
     */
    public function store(StoreJobRequest  $request)
    {
        $user = Auth::user();
        $company = $this->getUserCompany($user->id);

        if (!$company) {
            return response()->json(['error' => 'Bạn chưa thuộc công ty nào'], 403);
        }


        $job = Job::create([
            'company_id' => $company->id,
            'title' => $request->title,
            'description' => $request->description,
            'requirements' => $request->requirements,
            'salary_range' => $request->salary_range,
            'location' => $request->location,
            'employment_type' => $request->employment_type,
            'posted_date' => now(),
            'expiration_date' => $request->expiration_date,
            'status' => 'open',
        ]);

        // Attach categories
        if ($request->has('category_ids')) {
            $job->categories()->attach($request->category_ids);
        }

        return response()->json([
            'message' => 'Tạo tin tuyển dụng thành công',
            'job' => $job->load('categories:id,name')
        ], 201);
    }

    /**
     * Chi tiết job
     */
    public function show($id)
    {
        $user = Auth::user();
        $company = $this->getUserCompany($user->id);

        if (!$company) {
            return response()->json(['error' => 'Bạn chưa thuộc công ty nào'], 403);
        }

        $job = Job::where('company_id', $company->id)
            ->with(['categories:id,name', 'company:id,company_name'])
            ->findOrFail($id);

        return response()->json($job);
    }

    /**
     * Cập nhật job
     */
    public function update(UpdateJobRequest  $request, $id)
    {
        $user = Auth::user();
        $company = $this->getUserCompany($user->id);

        if (!$company) {
            return response()->json(['error' => 'Bạn chưa thuộc công ty nào'], 403);
        }

        $job = Job::where('company_id', $company->id)->findOrFail($id);


        $job->update($request->only([
            'title',
            'description',
            'requirements',
            'salary_range',
            'location',
            'employment_type',
            'expiration_date',
            'status'
        ]));

        // Sync categories
        if ($request->has('category_ids')) {
            $job->categories()->sync($request->category_ids);
        }

        return response()->json([
            'message' => 'Cập nhật tin tuyển dụng thành công',
            'job' => $job->load('categories:id,name')
        ]);
    }

    /**
     * Xóa job
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $company = $this->getUserCompany($user->id);

        if (!$company) {
            return response()->json(['error' => 'Bạn chưa thuộc công ty nào'], 403);
        }

        $job = Job::where('company_id', $company->id)->findOrFail($id);
        $job->delete();

        return response()->json(['message' => 'Xóa tin tuyển dụng thành công']);
    }

    /**
     * Đóng/mở job
     */
    public function toggleStatus($id)
    {
        $user = Auth::user();
        $company = $this->getUserCompany($user->id);

        if (!$company) {
            return response()->json(['error' => 'Bạn chưa thuộc công ty nào'], 403);
        }

        $job = Job::where('company_id', $company->id)->findOrFail($id);
        $job->status = $job->status === 'open' ? 'closed' : 'open';
        $job->save();

        return response()->json([
            'message' => 'Cập nhật trạng thái thành công',
            'job' => $job
        ]);
    }

    /**
     * Helper: Lấy company của user hiện tại
     */
    private function getUserCompany($userId)
    {
        $companyUser = CompanyUser::where('user_id', $userId)
            ->where('status', 'active')
            ->whereIn('role_in_company', ['Owner', 'Admin', 'Recruiter'])
            ->with('company')
            ->first();

        return $companyUser ? $companyUser->company : null;
    }
}
