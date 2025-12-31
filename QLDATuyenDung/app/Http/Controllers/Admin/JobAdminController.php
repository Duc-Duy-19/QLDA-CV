<?php
// filepath: c:\laragon\www\websitetuyendungthucte\webcv\app\Http\Controllers\Admin\JobAdminController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Company;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\UpdateJobAdminRequest;

//
class JobAdminController extends Controller
{
    /**
     * Danh sách tất cả job (admin)
     */
    public function index(Request $request)
    {
        $query = Job::with([
            'company:id,company_name',
            'categories:id,name'
        ]);

        // Lọc theo status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Lọc theo company
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Lọc theo category
        if ($request->has('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->category_id);
            });
        }

        // Tìm kiếm
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Jobs sắp hết hạn
        if ($request->has('expiring_soon')) {
            $query->where('expiration_date', '<=', now()->addDays(7))
                ->where('status', 'open');
        }

        $jobs = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json($jobs);
    }

    /**
     * Chi tiết job (admin)
     */
    public function show($id)
    {
        $job = Job::with([
            'company:id,company_name,address,email,phone',
            'categories:id,name'
        ])->findOrFail($id);

        return response()->json($job);
    }

    /**
     * Cập nhật job (admin)
     */
    public function update(UpdateJobAdminRequest $request, $id)
    {
        $job = Job::findOrFail($id);


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
            'message' => 'Cập nhật job thành công',
            'job' => $job->load('categories:id,name')
        ]);
    }

    /**
     * Xóa job (admin)
     */
    public function destroy($id)
    {
        $job = Job::findOrFail($id);
        $job->delete();

        return response()->json(['message' => 'Xóa job thành công']);
    }

    /**
     * Thống kê job
     */
    public function stats(Request $request)
    {
        $totalJobs = Job::count();
        $openJobs = Job::where('status', 'open')->count();
        $closedJobs = Job::where('status', 'closed')->count();
        $expiringJobs = Job::where('expiration_date', '<=', now()->addDays(7))
            ->where('status', 'open')->count();

        // Jobs by category
        $jobsByCategory = Category::withCount(['jobs' => function ($q) {
            $q->where('status', 'open');
        }])->get();

        // Jobs by company (top 10)
        $jobsByCompany = Company::withCount(['jobs' => function ($q) {
            $q->where('status', 'open');
        }])
            ->orderBy('jobs_count', 'desc')
            ->take(10)
            ->get();

        return response()->json([
            'total_jobs' => $totalJobs,
            'open_jobs' => $openJobs,
            'closed_jobs' => $closedJobs,
            'expiring_jobs' => $expiringJobs,
            'jobs_by_category' => $jobsByCategory,
            'jobs_by_company' => $jobsByCompany
        ]);
    }

    /**
     * Đóng job hàng loạt
     */
    public function bulkClose(Request $request)
    {
        $request->validate([
            'job_ids' => 'required|array',
            'job_ids.*' => 'exists:jobs,id'
        ]);

        Job::whereIn('id', $request->job_ids)
            ->update(['status' => 'closed']);

        return response()->json([
            'message' => 'Đã đóng ' . count($request->job_ids) . ' tin tuyển dụng'
        ]);
    }
}
