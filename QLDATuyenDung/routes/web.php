<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Employer\ApplicationController as EmployerApplicationController;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Đây là nơi bạn có thể đăng ký các web route cho ứng dụng của mình.
| Các route này được tải bởi RouteServiceProvider và tất cả chúng sẽ
| được gán vào nhóm middleware "web".
|
*/

// =========================================================================
// Nhóm Routes Public (Không Cần Đăng Nhập)
// =========================================================================

Route::get('/', function () {
    return view('pages.home');
})->name('home');

Route::get('/welcome', function () {
    return view('welcome');
});

Route::get('/job-categories', function () {
    return view('job-categories');
})->name('job-categories');

// Job Search Routes
Route::get('shared/job-search-skill', function () {
    return view('job-search-skill');
})->name('job-search-skill');

Route::get('shared/job-search-expertise', function () {
    return view('job-search-expertise');
})->name('job-search-expertise');

Route::get('shared/job-search-title', function () {
    return view('job-search-title');
})->name('job-search-title');

Route::get('shared/job-search-company', function () {
    return view('job-search-company');
})->name('job-search-company');

Route::get('shared/job-search-city', function () {
    return view('job-search-city');
})->name('job-search-city');

// Public Company Routes
Route::get('/companies', function () {
    return view('pages.companies');
})->name('companies');

// Public company detail page - loads data from API on client-side
Route::get('/companies/{id}', function ($id) {
    return view('pages.company-detail', compact('id'));
})->name('companies.show');

// Public job detail page - loads data from API on client-side
Route::get('/jobs/{id}', function ($id) {
    return view('pages.job-detail', compact('id'));
})->name('jobs.show');

// Job Category Detail Routes
// Route::get('/test-category/{category}', function ($category) {
//     return response()->json([
//         'original' => $category,
//         'decoded' => urldecode($category),
//         'url' => request()->url()
//     ]);
// })->where('category', '.*'); // Được giữ nguyên nhưng bị comment vì là route test

Route::get('/category/{category}', function ($category) {
    $decodedCategory = urldecode($category);

    Log::info('Category route accessed', [
        'original' => $category,
        'decoded' => $decodedCategory,
        'url' => request()->url()
    ]);

    return view('candidate.category-jobs', compact('category', 'decodedCategory'));
})->name('category-jobs')->where('category', '.*');

Route::get('/category/*', function () {
    $path = request()->path();
    $category = str_replace('category/', '', $path);
    $decodedCategory = urldecode($category);
    Log::info('Category fallback route accessed', [
        'path' => $path,
        'category' => $category,
        'decoded' => $decodedCategory,
        'url' => request()->url()
    ]);

    return view('candidate.category-jobs', compact('category', 'decodedCategory'));
});

// =========================================================================
// Nhóm Routes Authentication (Login, Register, Reset, Logout)
// =========================================================================

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->name('password.request');

Route::get('/reset-password', function (Request $request) {
    $token = $request->get('token');
    $email = $request->get('email');

    if (!$token || !$email) {
        return redirect()->route('login')->with('error', 'Link reset không hợp lệ');
    }

    return view('auth.reset-password', compact('token', 'email'));
})->name('password.reset');

// Note: POST xử lý qua API route /api/forgot-password và /api/reset-password

// Web Login Route - Tạo session cho admin/user
Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    if (Auth::attempt($credentials)) {
        $user = Auth::user();

        // ✅ Kiểm tra tài khoản có bị khóa không (dựa trên status)
        if ($user->status === 'suspended') {
            Auth::logout();
            $message = 'Tài khoản của bạn đã bị khóa';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'status' => 'suspended'
                ], 403);
            }

            return back()->withErrors([
                'email' => $message,
            ]);
        }

        $request->session()->regenerate();

        // Trả về JSON response cho AJAX requests
        if ($request->expectsJson()) {
            return response()->json([
                'user' => $user,
                'redirect' => $user->role === 'admin' ? route('admin.dashboard') : route('home')
            ]);
        }

        // Redirect cho form submissions thông thường
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('home');
    }

    // Trả về JSON error cho AJAX requests
    if ($request->expectsJson()) {
        return response()->json([
            'message' => 'Email hoặc mật khẩu không đúng.'
        ], 401);
    }

    return back()->withErrors([
        'email' => 'Email hoặc mật khẩu không đúng.',
    ]);
})->name('login.post');

// API Login Route - Tạo session từ token
Route::post('/api/web-login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    if (Auth::attempt($credentials)) {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // ✅ Kiểm tra tài khoản có bị khóa không (dựa trên status)
        if ($user->status === 'suspended') {
            Auth::logout();
            $message = 'Tài khoản của bạn đã bị khóa';

            return response()->json([
                'message' => $message,
                'status' => 'suspended'
            ], 403);
        }

        $request->session()->regenerate();
        // Also create a personal access token so SPA/frontend can use token-based auth
        try {
            $token = $user->createToken('web-login-token')->plainTextToken;
        } catch (\Exception $e) {
            // If token creation fails, log and continue without token
            Log::error('Failed to create token during web-login: ' . $e->getMessage());
            $token = null;
        }

        return response()->json([
            'user' => $user,
            'token' => $token,
            'redirect' => $user->role === 'admin' ? route('admin.dashboard') : route('home')
        ]);
    }

    return response()->json([
        'message' => 'Email hoặc mật khẩu không đúng.'
    ], 401);
})->name('api.web-login');


Route::get('/logout', function (Request $request) {
    // Perform server-side logout for session-based auth
    try {
        Auth::logout();
    } catch (\Exception $e) {
        // ignore if logout fails
    }

    // Invalidate session and regenerate CSRF token
    try {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    } catch (\Exception $e) {
        // ignore session errors
    }

    return redirect('/')->with('message', 'Đã đăng xuất thành công');
})->name('logout');

// =========================================================================
// Chat Route (Dùng chung cho Candidate và Employer)
// =========================================================================

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/chat', function () {
        return view('chat');
    })->name('chat.index');
});

// =========================================================================
// Nhóm Routes Candidate (Yêu Cầu Đăng Nhập)
// =========================================================================

Route::prefix('candidate')->middleware(['web', 'auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('candidate.dashboard');
    })->name('candidate.dashboard');

    Route::get('/profile', function () {
        return view('candidate.profile');
    })->name('candidate.profile');

    Route::get('/cv-builder', function () {
        return view('candidate.cv-builder');
    })->name('candidate.cv-builder');

    Route::get('/cv', function () {
        return view('candidate.cv-index');
    })->name('candidate.cv.index');

    Route::get('/cv/view', function () {
        return view('candidate.cv-view');
    })->name('candidate.cv.view');

    Route::get('/job-search', function () {
        return view('candidate.job-search');
    })->name('candidate.job-search');

    Route::get('/applications', function () {
        return view('candidate.applications');
    })->name('candidate.applications');

    Route::get('/favorites', function () {
        return view('candidate.favorites');
    })->name('candidate.favorites');

    Route::get('/notifications', function () {
        return view('candidate.notifications');
    })->name('candidate.notifications');

    Route::get('/career-development', function () {
        return view('candidate.career-development');
    })->name('candidate.career-development');

    Route::get('/community', function () {
        return view('candidate.community');
    })->name('candidate.community');

    Route::get('/settings', function () {
        return view('candidate.settings');
    })->name('candidate.settings');

    // Candidate upgrade to employer route (cũng thuộc nhóm auth)
    Route::post('/companies', function (Request $request) {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'address' => 'required|string',
            'description' => 'required|string',
            'website' => 'nullable|url',
            'email' => 'required|email',
            'phone' => 'required|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:active,inactive'
        ]);

        try {
            // Tạo công ty mới
            $company = \App\Models\Company::create([
                'company_name' => $request->company_name,
                'address' => $request->address,
                'description' => $request->description,
                'website' => $request->website,
                'email' => $request->email,
                'phone' => $request->phone,
                'status' => 'pending' // Chờ admin duyệt
            ]);

            // Xử lý logo nếu có
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('company-logos', 'public');
                $company->update(['logo' => $logoPath]);
            }

            // Tạo liên kết user với công ty (chờ duyệt)
            \App\Models\CompanyUser::create([
                'company_id' => $company->id,
                'user_id' => auth()->id(),
                'role_in_company' => 'Owner',
                'status' => 'pending',
                'email' => auth()->user()->email
            ]);

            return response()->json([
                'message' => 'Đăng ký nâng cấp thành công! Vui lòng chờ admin duyệt.',
                'company' => $company
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    })->middleware(['web', 'auth'])->name('companies.store');
});

// =========================================================================
// Nhóm Routes Employer (Yêu Cầu Đăng Nhập & Role Employer)
// =========================================================================

Route::prefix('employer')->middleware(['web', 'auth', 'role:employer'])->group(function () {
    Route::get('/profile', function () {
        // Employer profile view moved into company.info — reuse that view for now.
        return view('company.info');
    })->name('employer.profile');

    // Employer job management page (frontend) - data loaded via API
    // Use existing company.jobs view to avoid duplicating templates.
    Route::get('/jobs', function () {
        return view('company.jobs');
    })->name('employer.jobs');

    // Employer applications management page (frontend) - data loaded via API
    // Map to existing company.manage-applications view so the template exists.
    Route::get('/applications', function () {
        return view('company.manage-applications');
    })->name('employer.manage-applications');

    // Serve CV Route
    Route::get('/cv/{id}/{filename?}', [EmployerApplicationController::class, 'serveCv'])
        ->name('employer.cv.serve');
});


// =========================================================================
// Nhóm Routes Company Info (Dùng chung cho Employer/Admin/Public có thể truy cập)
// =========================================================================

Route::get('/company/info', function () {
    return view('company.info');
})->name('company.info');

Route::get('/company/members', function () {
    return view('company.members');
})->name('company.members');


// =========================================================================
// Nhóm Routes Admin (Yêu Cầu Đăng Nhập & Role Admin)
// =========================================================================

Route::prefix('admin')->middleware(['web', 'auth', 'role:admin'])->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        try {
            // Lấy dữ liệu từ database
            $user = auth()->user();

            // Lấy thống kê thực tế với error handling
            $stats = [];

            try {
                $stats['pendingCompanies'] = \App\Models\Company::where('status', 'pending')->count();
            } catch (Exception $e) {
                Log::error('Error counting pending companies: ' . $e->getMessage());
                $stats['pendingCompanies'] = 0;
            }

            try {
                $stats['activeCompanies'] = \App\Models\Company::where('status', 'active')->count();
            } catch (Exception $e) {
                Log::error('Error counting active companies: ' . $e->getMessage());
                $stats['activeCompanies'] = 0;
            }

            try {
                $stats['totalUsers'] = \App\Models\User::count();
            } catch (Exception $e) {
                Log::error('Error counting total users: ' . $e->getMessage());
                $stats['totalUsers'] = 0;
            }

            try {
                $stats['totalCompanies'] = \App\Models\Company::count();
            } catch (Exception $e) {
                Log::error('Error counting total companies: ' . $e->getMessage());
                $stats['totalCompanies'] = 0;
            }

            $stats['employers'] = \App\Models\User::where('role', 'employer')->count();
            $stats['candidates'] = \App\Models\User::where('role', 'candidate')->count();
            $stats['rejectedCompanies'] = \App\Models\Company::where('status', 'rejected')->count();
            $stats['todayRegistrations'] = \App\Models\User::whereDate('created_at', today())->count();

            // Debug log
            Log::info('Admin Dashboard Stats: ', $stats);

            // Lấy danh sách công ty chờ duyệt (chờ admin approve)
            $pendingUpgrades = [];
            try {
                $pendingUpgrades = \App\Models\Company::with(['companyUsers' => function ($query) {
                    $query->where('role_in_company', 'Owner')->with('user');
                }])
                    ->where('status', 'pending')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
            } catch (Exception $e) {
                Log::error('Error loading pending companies: ' . $e->getMessage());
                $pendingUpgrades = collect([]);
            }

            return view('admin.dashboard', compact('stats', 'pendingUpgrades', 'user'));
        } catch (Exception $e) {
            Log::error('Admin Dashboard Error: ' . $e->getMessage());
            // Return view with empty data if error
            $stats = [
                'pendingCompanies' => 0,
                'activeCompanies' => 0,
                'totalUsers' => 0,
                'totalCompanies' => 0,
                'employers' => 0,
                'candidates' => 0,
                'rejectedCompanies' => 0,
                'todayRegistrations' => 0
            ];
            $pendingUpgrades = collect([]);
            $user = auth()->user();

            return view('admin.dashboard', compact('stats', 'pendingUpgrades', 'user'));
        }
    })->name('admin.dashboard');

    // Debug helper for admin session (temporary) — returns current authenticated user and request cookies/headers
    Route::get('/debug-session', function (
        Request $request
    ) {
        try {
            return response()->json([
                'user' => auth()->user(),
                'cookies' => $request->cookies->all(),
                'headers' => [
                    'cookie' => $request->header('cookie'),
                    'x-xsrf-token' => $request->header('x-xsrf-token'),
                    'x-csrf-token' => $request->header('x-csrf-token'),
                    'authorization' => $request->header('authorization'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });

    // --- Companies Management Views ---
    Route::get('/companies/pending', function () {
        // Trả về view không cần dữ liệu vì sẽ load bằng API
        return view('admin.companies-pending');
    })->name('admin.companies.pending');

    Route::get('/companies', function () {
        // Lấy dữ liệu thực từ database
        $companies = \App\Models\Company::with('users')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.companies', compact('companies'));
    })->name('admin.companies');

    // --- Users Management Views ---
    Route::get('/users', function () {
        // Mock data for users (kept for compatibility), the page will fetch real data from API via JS.
        $users = collect([
            (object)[
                'id' => 1,
                'name' => 'Nguyễn Văn A',
                'email' => 'nguyenvana@email.com',
                'role' => 'candidate',
                'status' => 'active',
                'companies' => collect([]),
                'created_at' => now()->subDays(30)
            ],
            (object)[
                'id' => 2,
                'name' => 'Trần Thị B',
                'email' => 'tranthib@email.com',
                'role' => 'employer',
                'status' => 'active',
                'companies' => collect([
                    (object)[
                        'company_name' => 'FPT Software',
                        'pivot' => (object)['role_in_company' => 'Owner', 'status' => 'active']
                    ]
                ]),
                'created_at' => now()->subDays(25)
            ],
            (object)[
                'id' => 3,
                'name' => 'Admin User',
                'email' => 'admin@gmail.com',
                'role' => 'admin',
                'status' => 'active',
                'companies' => collect([]),
                'created_at' => now()->subDays(60)
            ]
        ]);

        // Provide common header/sidebar data so layouts.admin renders consistently
        try {
            $pendingCount = \App\Models\Company::where('status', 'pending')->count();
        } catch (Exception $e) {
            Log::error('Error counting pending companies for admin.users: ' . $e->getMessage());
            $pendingCount = 0;
        }

        return view('admin.users', compact('users', 'pendingCount'));
    })->name('admin.users');

    // --- Other Admin Views ---
    Route::get('/settings', function () {
        $systemStats = [
            'totalUsers' => 150,
            'totalCompanies' => 30,
            'pendingApprovals' => 5,
            'diskUsage' => 1024
        ];

        return view('admin.settings', compact('systemStats'));
    })->name('admin.settings');

    Route::get('/categories', function () {
        return view('admin.categories');
    })->name('admin.categories');


    // --- Admin API Routes (Sử dụng session auth) ---
    Route::prefix('api')->group(function () {
        Route::get('/companies', function (Request $request) {
            return app(\App\Http\Controllers\Admin\CompanyAdminController::class)->index($request);
        });

        Route::get('/companies/pending', function () {
            $companies = \App\Models\Company::where('status', 'pending')->get();
            return response()->json($companies);
        });

        Route::post('/companies/{id}/approve', function ($id) {
            return app(\App\Http\Controllers\Admin\CompanyAdminController::class)->approveCompany($id);
        });

        Route::post('/companies/{id}/reject', function ($id) {
            return app(\App\Http\Controllers\Admin\CompanyAdminController::class)->rejectCompany($id);
        });
    });

    // --- Admin Actions Routes ---
    // Company Actions
    Route::post('/companies/{id}/approve', function ($id) {
        try {
            $companyUser = \App\Models\CompanyUser::findOrFail($id);
            $companyUser->update(['status' => 'active']);

            // Cập nhật role của user thành employer
            $companyUser->user->update(['role' => 'employer']);

            return response()->json(['message' => 'Ứng viên đã được duyệt nâng cấp thành công']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    })->name('admin.companies.approve');

    Route::post('/companies/{id}/reject', function ($id) {
        try {
            $companyUser = \App\Models\CompanyUser::findOrFail($id);
            $companyUser->update(['status' => 'inactive']);

            return response()->json(['message' => 'Ứng viên đã bị từ chối nâng cấp']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    })->name('admin.companies.reject');

    Route::get('/companies/{id}', function ($id) {
        try {
            $company = \App\Models\Company::findOrFail($id);

            return response()->json([
                'id' => $company->id,
                'company_name' => $company->company_name,
                'email' => $company->email,
                'address' => $company->address,
                'website' => $company->website,
                'phone' => $company->phone,
                'logo' => $company->logo,
                'description' => $company->description,
                'status' => $company->status,
                'created_at' => $company->created_at->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching company details: ' . $e->getMessage());
            return response()->json(['error' => 'Không tìm thấy công ty: ' . $e->getMessage()], 404);
        }
    })->name('admin.companies.show');

    // Xóa công ty và hạ cấp tài khoản employer xuống candidate
    Route::post('/companies/{id}/delete', function ($id) {
        try {
            $company = \App\Models\Company::findOrFail($id);

            // Tìm tất cả user có role employer liên quan đến công ty này
            $companyUsers = \App\Models\CompanyUser::where('company_id', $id)->get();

            foreach ($companyUsers as $companyUser) {
                // Hạ cấp role từ employer xuống candidate
                $companyUser->user->update(['role' => 'candidate']);

                // Xóa CompanyUser record
                $companyUser->delete();
            }

            // Xóa công ty
            $company->delete();

            return response()->json(['message' => 'Công ty đã được xóa thành công. Tài khoản employer đã được hạ cấp xuống candidate.']);
        } catch (\Exception $e) {
            Log::error('Error deleting company: ' . $e->getMessage());
            return response()->json(['message' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    })->name('admin.companies.delete');

    // User Actions
    Route::get('/users/{id}', function ($id) {
        // Mock user details - replace with actual data
        $user = (object)[
            'id' => $id,
            'name' => 'Nguyễn Văn A',
            'email' => 'nguyenvana@email.com',
            'role' => 'candidate',
            'status' => 'active',
            'companies' => [],
            'created_at' => now()->subDays(30)
        ];

        return response()->json($user);
    })->name('admin.users.show');
});

// =========================================================================
// Routes Test 
// =========================================================================

// Test route to debug
Route::get('/test-notifications', function () {
    return 'Test notifications route works!';
});