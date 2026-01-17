<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Job;
use App\Models\CompanyUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class ApplicationController extends Controller
{
    //
    // Thêm helper để lấy company của user (tránh gọi động companyUsers() gây cảnh báo IDE)
    private function getUserCompany($user)
    {
        $companyUser = CompanyUser::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('company')
            ->first();

        return $companyUser?->company;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $company = $this->getUserCompany($user);
        if (! $company) {
            return response()->json(['error' => 'Bạn chưa thuộc công ty nào'], 403);
        }

        $query = Application::with(['job', 'user', 'resume'])
            ->whereHas('job', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            });

        if ($request->has('job_id')) {
            $query->where('job_id', $request->job_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $apps = $query->latest('applied_at')->paginate($request->get('per_page', 20));

        return response()->json($apps);
    }

    public function show($id)
    {
        $user = Auth::user();
        $company = $this->getUserCompany($user);
        if (! $company) {
            return response()->json(['error' => 'Bạn chưa thuộc công ty nào'], 403);
        }

        $application = Application::with(['job', 'user', 'resume'])
            ->where('id', $id)
            ->whereHas('job', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->first();

        if (! $application) {
            return response()->json(['error' => 'Không tìm thấy ứng tuyển này'], 404);
        }

        return response()->json($application);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:pending,reviewed,approved,rejected']);

        $user = Auth::user();
        $company = $this->getUserCompany($user);
        if (! $company) {
            return response()->json(['error' => 'Bạn chưa thuộc công ty nào'], 403);
        }

        $application = Application::with(['user', 'job'])->where('id', $id)
            ->whereHas('job', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->first();

        if (! $application) {
            return response()->json(['error' => 'Không tìm thấy ứng tuyển này'], 404);
        }

        $application->status = $request->status;
        $application->save();

        // Gửi email khi approved hoặc rejected
        if (in_array($request->status, ['approved', 'rejected'])) {
            $this->sendStatusEmail($application);
        }

        return response()->json(['message' => 'Cập nhật trạng thái thành công', 'data' => $application]);
    }

    private function sendStatusEmail($application)
    {
        $applicant = $application->user;
        if (!$applicant || !$applicant->email) return;

        $mailClass = match ($application->status) {
            'approved' => \App\Mail\ApplicationApprovedMail::class,
            'rejected' => \App\Mail\ApplicationRejectedMail::class,
            default => null,
        };

        if ($mailClass) {
            Mail::to($applicant->email)->send(new $mailClass($application));
        }
    }

    public function serveCv($id, $filename = null)
    {
        $user = Auth::user();
        $company = $this->getUserCompany($user);
        if (! $company) {
            abort(403, 'Bạn chưa thuộc công ty nào');
        }

        $application = Application::with('resume', 'job')
            ->where('id', $id)
            ->whereHas('job', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            })->firstOrFail();

        $relative = $application->resume->uploaded_storage_path ?? null; // e.g. "applications/cv/21/xxx.pdf"
        if (! $relative) abort(404);

        $full = storage_path('app/public/' . ltrim($relative, '/')); // file nằm ở storage/app/public/...
        if (! file_exists($full)) abort(404);

        return response()->file($full);
    }
}
