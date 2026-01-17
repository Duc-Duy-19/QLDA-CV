<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\Candidate\ApplyJobRequest;
use App\Models\Application;
use App\Models\Job;
use App\Models\Resume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use App\Mail\ApplicationSubmittedMail;
use App\Mail\NewApplicationReceivedMail;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $profile = $user->profile ?? null;

        $query = Application::with([
            'job:id,title,company_id',
            'job.company:id,company_name',
            'resume'
        ]);

        // nếu user có profile -> lấy theo profile_id, nếu không -> lấy theo user_id
        if ($profile) {
            $query->where('profile_id', $profile->id);
        } else {
            $query->where('user_id', $user->id);
        }

        $apps = $query->latest('applied_at')
            ->paginate($request->get('per_page', 20));

        return response()->json($apps);
    }

    // Sử dụng FormRequest cho validation/authorization
    public function store(ApplyJobRequest $request)
    {
        // đảm bảo user đã xác thực
        $user = Auth::user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // profile có thể null -> dùng toán tử an toàn
        $profile = $user->profile ?? null;

        // ownerId dùng để lưu file (nếu không có profile dùng user id)
        $ownerId = $profile ? $profile->id : $user->id;

        $data = $request->validated();

        // gán các trường liên quan, an toàn khi profile = null
        $data['profile_id'] = $profile ? $profile->id : null;
        $data['user_id'] = $user->id;

        // Cooldown re-apply: cho phép ứng tuyển lại sau N phút (config)
        $jobId = $data['job_id'] ?? $request->input('job_id');
        if ($jobId) {
            $latest = Application::where('job_id', $jobId)
                ->where(function ($q) use ($user, $profile) {
                    $q->where('user_id', $user->id);
                    if ($profile) {
                        $q->orWhere('profile_id', $profile->id);
                    }
                    // fallback cho các bản ghi cũ dùng email
                    $q->orWhere(function ($q2) use ($user) {
                        $q2->whereNotNull('applicant_email')
                            ->whereRaw('LOWER(applicant_email) = ?', [strtolower($user->email)]);
                    });
                })
                ->orderByRaw('COALESCE(applied_at, created_at, updated_at) DESC')
                ->first();

            if ($latest) {
                $lastTime = $latest->applied_at ?? $latest->created_at ?? $latest->updated_at;
                $lastTime = $lastTime ? Carbon::parse($lastTime) : null;
                // Đọc duy nhất từ config để ổn định khi cache config (chuẩn production)
                $cooldown = (int) config('applications.reapply_cooldown_minutes');
                if ($lastTime && $cooldown > 0) {
                    $elapsed = now()->diffInMinutes($lastTime);
                    if ($elapsed < $cooldown) {
                        $wait = $cooldown - $elapsed;
                        return response()->json([
                            'error' => 'Bạn đã ứng tuyển công việc này gần đây. Vui lòng thử lại sau ' . $wait . ' phút.',
                            'cooldown_minutes_remaining' => $wait,
                        ], 429);
                    }
                }
            }
        }

        // SỬA: tránh truy cập thuộc tính trên null — dùng optional() hoặc ternary
        $data['applicant_name'] = optional($profile)->full_name ?? $request->input('applicant_name', $user->name ?? null);
        $data['applicant_email'] = optional($profile)->email ?? $request->input('applicant_email', $user->email ?? null);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        // Nếu chọn resume có sẵn -> kiểm tra ownership trước khi dùng
        if ($request->filled('resume_id')) {
            $resume = Resume::find($request->input('resume_id'));
            // nếu không có resume hoặc user không có profile hoặc resume không thuộc profile -> lỗi
            if (! $resume || ! $profile || $resume->profile_id !== $profile->id) {
                return response()->json(['error' => 'Resume không tồn tại hoặc không thuộc bạn'], 403);
            }

            $data['resume_id'] = $resume->id;
            $data['resume_snapshot'] = $resume->toArray();

            // cố gắng copy file nếu resume lưu file nội bộ
            $possiblePath = $resume->file_path ?? $resume->path ?? null;
            $possibleUrl = $resume->file_url ?? $resume->cv_file_url ?? null;
            if ($possiblePath && Storage::disk('local')->exists($possiblePath)) {
                $stored = $disk->putFileAs(
                    'applications/cv/' . $ownerId,
                    new \Illuminate\Http\File(storage_path('app/' . $possiblePath)),
                    basename($possiblePath)
                );
                // lưu đường dẫn relative để frontend dùng origin hiện tại
                $data['cv_file_url'] = '/storage/' . $stored;
            } elseif ($possibleUrl) {
                $data['cv_file_url'] = $possibleUrl;
            }
        }

        // Nếu upload trực tiếp
        if ($request->hasFile('cv')) {
            $path = $request->file('cv')->store('applications/cv/' . $ownerId, 'public');
            $data['cv_file_url'] = '/storage/' . $path;
            $data['resume_snapshot'] = array_merge($data['resume_snapshot'] ?? [], [
                'uploaded_at' => now()->toDateTimeString(),
                'uploaded_filename' => $request->file('cv')->getClientOriginalName(),
                'uploaded_storage_path' => $path,
            ]);
        }

        if ($request->filled('cover_letter')) {
            $data['cover_letter'] = $request->cover_letter;
        }

        // Nếu resume_snapshot là mảng, đảm bảo convert hoặc model đã cast
        if (isset($data['resume_snapshot']) && is_array($data['resume_snapshot'])) {
            // nếu model đã có $casts thì không bắt buộc, nhưng an toàn hơn convert trước
            $data['resume_snapshot'] = $data['resume_snapshot'];
        }

        $data['applied_at'] = now();

        $application = Application::create($data);

        // Load relationships for emails
        $application->load('job', 'job.company', 'job.company.users');

        // Gửi email xác nhận cho ứng viên
        if ($user->email) {
            Mail::to($user->email)->send(new ApplicationSubmittedMail($application));
        }

        // Gửi email thông báo cho nhà tuyển dụng
        $company = $application->job->company ?? null;
        if ($company) {
            // Lấy danh sách email của các nhà tuyển dụng quản lý công ty
            $employerEmails = [];

            // Ưu tiên email công ty
            if ($company->email) {
                $employerEmails[] = $company->email;
            }

            // Lấy email của các user có quyền quản lý công ty (admin/manager)
            $companyManagers = $company->users()
                ->wherePivot('status', 'active')
                ->whereIn('company_users.role_in_company', ['admin', 'manager'])
                ->get();

            foreach ($companyManagers as $manager) {
                if ($manager->email && !in_array($manager->email, $employerEmails)) {
                    $employerEmails[] = $manager->email;
                }
            }

            // Gửi email cho tất cả các nhà tuyển dụng
            foreach ($employerEmails as $email) {
                try {
                    Mail::to($email)->send(new NewApplicationReceivedMail($application));
                } catch (\Exception $e) {
                    // Log lỗi nhưng không làm gián đoạn flow ứng tuyển
                    Log::error('Failed to send new application email to employer: ' . $e->getMessage());
                }
            }
        }

        return response()->json(['application' => $application], 201);
    }

    // Sử dụng route-model binding; framework inject $application
    public function show(Application $application)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $profile = $user->profile ?? null;

        // cho phép owner theo:
        // - user_id === user->id
        // - profile_id === user's profile id
        // - applicant_email === user's email (fallback cho các bản ghi cũ trước khi có user_id)
        // - hoặc admin/employer
        $allowed = ($application->user_id !== null && $application->user_id === $user->id)
            || ($profile && $application->profile_id !== null && $application->profile_id === $profile->id)
            || ($application->applicant_email && strtolower($application->applicant_email) === strtolower($user->email))
            || in_array($user->role ?? '', ['employer', 'admin']);

        if (! $allowed) {
            return response()->json(['error' => 'Không có quyền truy cập'], 403);
        }

        return response()->json($application->load('job', 'resume'));
    }

    public function destroy(Application $application)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $profile = $user->profile ?? null;

        $allowed = ($application->user_id !== null && $application->user_id === $user->id)
            || ($profile && $application->profile_id !== null && $application->profile_id === $profile->id)
            || ($application->applicant_email && strtolower($application->applicant_email) === strtolower($user->email))
            || in_array($user->role ?? '', ['employer', 'admin']);

        if (! $allowed) {
            return response()->json(['error' => 'Không có quyền truy cập'], 403);
        }

        $application->delete();

        return response()->json(['message' => 'Rút ứng tuyển thành công']);
    }

    public function serveCvByApplication($id)
    {
        $user = auth()->user();
        $application = Application::find($id);
        if (! $application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        // Authorization: owner (user_id) OR profile owner OR employer/admin
        $isOwner = $user && $application->user_id && $application->user_id === $user->id;
        $isProfileOwner = $user && $user->profile && $application->profile_id && $application->profile_id === $user->profile->id;
        $isEmployerOrAdmin = $user && in_array($user->role ?? '', ['employer', 'admin']);

        if (! ($isOwner || $isProfileOwner || $isEmployerOrAdmin)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $url = $application->cv_file_url;
        if (! $url) {
            return response()->json(['message' => 'No CV attached'], 404);
        }

        // chuyển URL (absolute hoặc relative) thành đường dẫn đĩa
        // nếu là /storage/... thì map tới storage/app/public/...
        $relative = preg_replace('#^https?://[^/]+#i', '', $url);
        $relative = ltrim($relative, '/');

        if (preg_match('#^storage/#', $relative)) {
            $diskPath = storage_path('app/public/' . substr($relative, strlen('storage/')));
        } else {
            // fallback: assume stored under applications/cv/...
            $diskPath = storage_path('app/public/' . $relative);
        }

        if (! file_exists($diskPath)) {
            return response()->json(['message' => 'File not found on disk'], 404);
        }

        return response()->file($diskPath, [
            'Content-Type' => mime_content_type($diskPath) ?: 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($diskPath) . '"'
        ]);
    }
}
