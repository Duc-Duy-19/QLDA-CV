<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Candidate\ProfileController;
use App\Http\Controllers\Candidate\ResumeController;
use App\Http\Controllers\Candidate\ResumeEducationController;
use App\Http\Controllers\Candidate\CompanyInviteController;
use App\Http\Controllers\Employer\CompanyController as EmployerCompanyController;
use App\Http\Controllers\Employer\CompanyMemberController;
use App\Http\Controllers\Admin\CompanyAdminController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\Public\PublicCompanyController;
use App\Http\Controllers\Admin\CategoryAdminController;
use App\Http\Controllers\Public\PublicJobController;
use App\Http\Controllers\Employer\JobController;
use App\Http\Controllers\Public\PublicCategoryController;
use App\Http\Controllers\Admin\ApplicationAdminController;
use App\Http\Controllers\Candidate\ApplicationController as CandidateApplicationController;
use App\Http\Controllers\Employer\ApplicationController as EmployerApplicationController;
use App\Http\Controllers\Candidate\SavedJobController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Admin\RevenueReportController;

/*
|--------------------------------------------------------------------------
| Payment Routes (Webhook - Public)
|--------------------------------------------------------------------------
*/
// Sepay webhook - no auth required (verified by signature)
Route::post('/payments/webhook', [PaymentController::class, 'webhook']);

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');



/*
|--------------------------------------------------------------------------
| Public Company Routes (không cần auth)
|--------------------------------------------------------------------------
*/
Route::prefix('public/')->group(function () {
    //job
    Route::get('jobs', [PublicJobController::class, 'index']);
    Route::get('jobs/search', [PublicJobController::class, 'search']);
    Route::get('jobs/{id}', [PublicJobController::class, 'show']);


    // Categories
    Route::get('categories', [PublicCategoryController::class, 'index']);
    Route::get('categories/popular', [PublicCategoryController::class, 'popular']);
    Route::get('categories/{id}', [PublicCategoryController::class, 'show']);
    Route::get('categories/{id}/jobs', [PublicCategoryController::class, 'jobs']);

    //company
    Route::get('companies', [PublicCompanyController::class, 'index']);
    Route::get('companies/{id}', [PublicCompanyController::class, 'show']);
    Route::get('companies/{id}/jobs', [PublicCompanyController::class, 'getJobs']);
});
Route::middleware(['auth:sanctum', 'check.suspended'])->group(function () {
    // Candidate Profile (existing)
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::post('/profile', [ProfileController::class, 'update']);

    // Serve CV by application id (authenticated)
    Route::get('/cv/application/{id}', [\App\Http\Controllers\Candidate\ApplicationController::class, 'serveCvByApplication']);

    // Payment Routes (authenticated users)
    Route::prefix('payments')->group(function () {
        Route::get('/my-payments', [PaymentController::class, 'myPayments']); // Get current user's payments
        Route::get('/{id}', [PaymentController::class, 'show']); // View payment details
        Route::post('/{id}/confirm', [PaymentController::class, 'confirm']); // User confirms payment
    });

    // Chat Routes (available for all authenticated users)
    Route::prefix('chat')->group(function () {
        Route::get('/conversations', [ChatController::class, 'getConversations']);
        Route::post('/conversations', [ChatController::class, 'getOrCreateConversation']);
        Route::get('/conversations/{conversationId}/messages', [ChatController::class, 'getMessages']);
        Route::post('/conversations/{conversationId}/messages', [ChatController::class, 'sendMessage']);
        Route::post('/conversations/{conversationId}/read', [ChatController::class, 'markAsRead']);
        Route::get('/applications/{applicationId}/can-chat', [ChatController::class, 'canChatWithApplication']);
                
        // AI Career Advisor Routes
        Route::post('/ai/start', [ChatController::class, 'startAiChat']); // Bắt đầu chat với AI
        Route::post('/ai/message', [ChatController::class, 'sendToAi']); // Gửi message tới AI
    });

    // Debug Route
    Route::get('/debug/ai-config', function() {
        $apiKey = config('services.gemini.api_key');
        return response()->json([
            'gemini_api_key_set' => !empty($apiKey),
            'api_key_length' => $apiKey ? strlen($apiKey) : 0,
            'api_key_preview' => $apiKey ? substr($apiKey, 0, 10) . '...' : 'NOT SET',
            'config_path' => config_path('services.php'),
            'env_loaded' => env('GEMINI_API_KEY') ? 'YES' : 'NO',
        ]);
    });

    Route::get('/debug/logs', function() {
        $logPath = storage_path('logs/laravel.log');
        if (!file_exists($logPath)) {
            return 'Log file not found';
        }
        
        $lines = file($logPath);
        $lastLines = array_slice($lines, -200); // Last 200 lines
        
        $output = '';
        foreach ($lastLines as $line) {
            if (stripos($line, 'gemini') !== false || 
                stripos($line, 'AI Career') !== false ||
                stripos($line, 'error') !== false) {
                $output .= htmlspecialchars($line);
            }
        }
        
        return $output ?: 'No relevant logs found';
    });

    Route::get('/debug/test-gemini', function() {
        $apiKey = config('services.gemini.api_key');
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';
        
        try {
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->timeout(30)
                ->post($url . '?key=' . $apiKey, [
                    'contents' => [
                        ['parts' => [['text' => 'Test message: Hello']]]
                    ]
                ]);

            return [
                'status' => $response->status(),
                'success' => $response->successful(),
                'body' => $response->json(),
                'api_key_used' => substr($apiKey, 0, 10) . '...'
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    });
});
/*
|--------------------------------------------------------------------------
| Candidate Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'check.suspended', 'role:candidate'])->group(function () {
    // // Candidate Profile
    // Route::get('/profile', [ProfileController::class, 'show']);
    // Route::post('/profile', [ProfileController::class, 'update']);

    // Company invites (đã tách sang Candidate\CompanyInviteController)
    Route::get('/company-invites', [CompanyInviteController::class, 'pendingInvites']);
    Route::post('/companies/{companyId}/acceptInvite', [CompanyInviteController::class, 'acceptInvite']);

    // Đăng ký công ty (pending approval)
    Route::post('/companies', [EmployerCompanyController::class, 'store']);

    // Resumes
    Route::get('/resumes', [ResumeController::class, 'index']);
    Route::get('/resumes/{id}', [ResumeController::class, 'show']);
    Route::post('/resumes', [ResumeController::class, 'store']);
    Route::put('/resumes/{id}', [ResumeController::class, 'update']);
    Route::delete('/resumes/{id}', [ResumeController::class, 'destroy']);

    // Applications (candidate)
    Route::get('applications', [CandidateApplicationController::class, 'index']);
    Route::post('applications', [CandidateApplicationController::class, 'store']);
    Route::get('applications/{application}', [CandidateApplicationController::class, 'show']);
    Route::delete('applications/{application}', [CandidateApplicationController::class, 'destroy']);

    // Saved jobs (candidate)
    Route::get('saved-jobs', [SavedJobController::class, 'index']);
    Route::post('saved-jobs', [SavedJobController::class, 'store']);
    Route::delete('saved-jobs/{jobId}', [SavedJobController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Employer Routes (không gộp chung admin vào đây để tránh nhầm quyền)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'check.suspended', 'role:employer'])->group(function () {
    // Cập nhật thông tin công ty
    Route::get('/my-company', [EmployerCompanyController::class, 'getMyCompany']);
    Route::put('/companies/{id}', [EmployerCompanyController::class, 'update']);
    Route::put('/companies/{id}/context', [EmployerCompanyController::class, 'updateCompanyContext']);

    // Quản lý nhân sự nội bộ công ty
    Route::prefix('companies/{companyId}')->group(function () {
        Route::get('/users', [CompanyMemberController::class, 'index']);
        Route::post('/users/add', [CompanyMemberController::class, 'addUser']);   // hoặc POST /users (RESTful hơn)
        Route::put('/users/{companyUserId}/role', [CompanyMemberController::class, 'updateRole']);
        Route::delete('/users/{companyUserId}', [CompanyMemberController::class, 'removeUser']);
    });

    //job 
    Route::get('jobs', [JobController::class, 'index']);
    Route::post('jobs', [JobController::class, 'store']);
    Route::get('jobs/{id}', [JobController::class, 'show']);
    Route::put('jobs/{id}', [JobController::class, 'update']);
    Route::delete('jobs/{id}', [JobController::class, 'destroy']);
    Route::post('jobs/{id}/toggle-status', [JobController::class, 'toggleStatus']);

    Route::prefix('employer/applications')->group(function () {
        Route::get('/', [EmployerApplicationController::class, 'index']);
        Route::get('/{id}', [EmployerApplicationController::class, 'show']);
        Route::put('/{id}/status', [EmployerApplicationController::class, 'updateStatus']);
    });

    Route::get('employer-dashboard', function () {
        return response()->json(['message' => 'Employer area']);
    });
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Company Management Routes
    Route::prefix('admin/companies')->group(function () {
        // Danh sách và quản lý công ty
        Route::get('/', [CompanyAdminController::class, 'index']);
        Route::get('/pending', [CompanyAdminController::class, 'pendingCompanies']);
        Route::get('/recent', [CompanyAdminController::class, 'getRecentCompanies']);
        Route::get('/stats', [CompanyAdminController::class, 'getCompanyStats']);
        Route::get('/{id}', [CompanyAdminController::class, 'show']);

        // Duyệt/từ chối công ty
        Route::post('/{id}/approve', [CompanyAdminController::class, 'approveCompany']);
        Route::post('/{id}/reject', [CompanyAdminController::class, 'rejectCompany']);

        // Tạm dừng/kích hoạt lại công ty
        Route::post('/{id}/suspend', [CompanyAdminController::class, 'suspendCompany']);
        Route::post('/{id}/reactivate', [CompanyAdminController::class, 'reactivateCompany']);

        // Cập nhật/xóa công ty

        Route::delete('/{id}', [CompanyAdminController::class, 'deleteCompany']);

        // Quản lý thành viên công ty
        Route::get('/{id}/members', [CompanyAdminController::class, 'getCompanyMembers']);
        Route::delete('/{companyId}/members/{memberId}', [CompanyAdminController::class, 'removeMemberFromCompany']);
        Route::put('/{companyId}/members/{memberId}/role', [CompanyAdminController::class, 'changeMemberRole']);
    });
    Route::prefix('admin/applications')->group(function () {
        Route::get('/', [ApplicationAdminController::class, 'index']);
        Route::get('/{id}', [ApplicationAdminController::class, 'show']);
        Route::delete('/{id}', [ApplicationAdminController::class, 'destroy']);
    });

    // User Management Routes
    Route::prefix('admin/users')->group(function () {
        Route::get('/', [UserAdminController::class, 'index']);
        Route::get('/stats', [UserAdminController::class, 'getUserStats']);
        Route::post('/{id}/suspend', [UserAdminController::class, 'suspendUser']);
        Route::post('/{id}/reactivate', [UserAdminController::class, 'reactivateUser']);
    });

    // category
    Route::prefix('admin/categories')->group(function () {
        Route::get('/', [CategoryAdminController::class, 'index']);
        Route::post('/', [CategoryAdminController::class, 'store']);
        Route::get('/{id}', [CategoryAdminController::class, 'show']);
        Route::put('/{id}', [CategoryAdminController::class, 'update']);
        Route::delete('/{id}', [CategoryAdminController::class, 'destroy']);
    });

    // Admin Dashboard
    Route::get('/admin/dashboard', function () {
        return response()->json(['message' => 'Admin dashboard']);
    });

    // Payment Management Routes (Admin only)
    Route::prefix('admin/payments')->group(function () {
        Route::get('/pending', [PaymentController::class, 'index']); // List payments pending verification
        Route::post('/{id}/approve', [PaymentController::class, 'approve']); // Admin approves payment
        Route::post('/{id}/reject', [PaymentController::class, 'reject']); // Admin rejects payment
    });

    // Revenue Report Routes (Admin only)
    Route::prefix('admin/revenue')->group(function () {
        Route::get('/data', [RevenueReportController::class, 'getRevenueData']);
        Route::get('/export', [RevenueReportController::class, 'export']);
    });

    // Industry Contexts Management (Admin only)
    Route::prefix('admin/industry-contexts')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\IndustryContextController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Admin\IndustryContextController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\Admin\IndustryContextController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\Admin\IndustryContextController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Admin\IndustryContextController::class, 'destroy']);
        Route::patch('/{id}/toggle', [\App\Http\Controllers\Admin\IndustryContextController::class, 'toggleActive']);
    });
});
/*
|--------------------------------------------------------------------------
| AI Analysis Routes
|--------------------------------------------------------------------------
*/
// AI Feedback - Allow both authenticated users and guests
Route::post('ai/feedback', [\App\Http\Controllers\AiFeedbackController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    // AI matching với CV đã tạo trên website
    Route::post('ai/check-match', [\App\Http\Controllers\AiAnalysisController::class, 'checkMatch']);
});


Route::get('debug/me', function () {
    return response()->json([
        'user' => auth()->user(),
        'role' => auth()->user()?->role,
        'candidate' => auth()->user()?->candidate,
    ]);
})->middleware('auth:sanctum');
