<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

//
class CompanyAdminController extends Controller
{
    /**
     * Lấy danh sách công ty đang chờ duyệt
     */
    public function pendingCompanies()
    {
        $companies = Company::where('status', 'pending')->get();
        return response()->json($companies, 200);
    }

    /**
     * Duyệt công ty (chuyển status từ pending -> active)
     * - Active company
     * - Active Owner (company_users)
     * - Nâng role user Owner lên employer (nếu chưa)
     */
    public function approveCompany($id)
    {
        return DB::transaction(function () use ($id) {
            $company = Company::lockForUpdate()->findOrFail($id);

            if ($company->status !== 'pending') {
                return response()->json([
                    'message' => 'Công ty này không ở trạng thái pending'
                ], 400);
            }

            // Tìm Owner
            $owners = CompanyUser::where('company_id', $company->id)
                ->where('role_in_company', 'Owner')
                ->get();

            if ($owners->isEmpty()) {
                return response()->json([
                    'message' => 'Không tìm thấy Owner cho công ty này'
                ], 422);
            }

            // 1) Active công ty
            $company->status = 'active';
            $company->save();

            // 2) Active Owner + set joined_at
            foreach ($owners as $owner) {
                $owner->status = 'active';
                if (is_null($owner->joined_at)) {
                    $owner->joined_at = now();
                }
                $owner->save();

                // 3) Nâng role user lên employer nếu chưa
                $user = User::find($owner->user_id);
                if ($user && $user->role !== 'employer') {
                    $user->role = 'employer';
                    $user->save();
                }
            }

            return response()->json([
                'message' => 'Duyệt công ty thành công',
                'company' => $company
            ], 200);
        });
    }

    /**
     * Từ chối công ty (chuyển status -> inactive) và vô hiệu hóa membership
     */
    public function rejectCompany($id)
    {
        return DB::transaction(function () use ($id) {
            $company = Company::lockForUpdate()->findOrFail($id);

            if ($company->status !== 'pending') {
                return response()->json([
                    'message' => 'Chỉ từ chối được công ty đang pending'
                ], 400);
            }

            $company->status = 'inactive';
            $company->save();

            CompanyUser::where('company_id', $company->id)
                ->update(['status' => 'inactive']);

            return response()->json([
                'message' => 'Đã từ chối công ty',
                'company' => $company
            ], 200);
        });
    }

    public function index(Request $request)
    {
        $query = Company::with(['users' => function ($q) {
            $q->where('role_in_company', 'Owner');
        }]);

        // Lọc theo status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Tìm kiếm theo tên công ty
        if ($request->has('search')) {
            $query->where('company_name', 'like', '%' . $request->search . '%');
        }

        $companies = $query->paginate($request->get('per_page', 20));

        return response()->json($companies, 200);
    }

    /**
     * Xem chi tiết công ty
     */
    public function show($id)
    {
        $company = Company::with([
            'users:id,name,email,role',

            //sau này có kèm theo các job thì mở ra
            // 'jobs' => function($q) {
            //     $q->latest()->take(10);
            // }
        ])->findOrFail($id);

        return response()->json($company, 200);
    }

    /**
     * Tạm dừng hoạt động của công ty
     */
    public function suspendCompany($id)
    {
        return DB::transaction(function () use ($id) {
            $company = Company::lockForUpdate()->findOrFail($id);

            if ($company->status !== 'active') {
                return response()->json([
                    'message' => 'Chỉ có thể tạm dừng công ty đang active'
                ], 400);
            }

            $company->status = 'suspended';
            $company->save();

            // Tạm dừng tất cả membership
            CompanyUser::where('company_id', $company->id)
                ->where('status', 'active')
                ->update(['status' => 'suspended']);

            return response()->json([
                'message' => 'Đã tạm dừng hoạt động công ty',
                'company' => $company
            ], 200);
        });
    }

    /**
     * Kích hoạt lại công ty đã bị tạm dừng
     */
    public function reactivateCompany($id)
    {
        return DB::transaction(function () use ($id) {
            $company = Company::lockForUpdate()->findOrFail($id);

            if ($company->status !== 'suspended') {
                return response()->json([
                    'message' => 'Chỉ có thể kích hoạt lại công ty đang bị tạm dừng'
                ], 400);
            }

            $company->status = 'active';
            $company->save();

            // Kích hoạt lại membership
            CompanyUser::where('company_id', $company->id)
                ->where('status', 'suspended')
                ->update(['status' => 'active']);

            return response()->json([
                'message' => 'Đã kích hoạt lại công ty',
                'company' => $company
            ], 200);
        });
    }

    /**
     * Xóa công ty vĩnh viễn
     */
    public function deleteCompany($id)
    {
        return DB::transaction(function () use ($id) {
            $company = Company::lockForUpdate()->findOrFail($id);

            // Lấy danh sách tất cả thành viên trước khi xóa
            $members = CompanyUser::where('company_id', $company->id)->get();

            // Xử lý role của từng thành viên
            foreach ($members as $member) {
                $user = User::find($member->user_id);
                if ($user && $user->role === 'employer') {
                    // Kiểm tra xem user có thuộc công ty nào khác không
                    $otherCompanyMemberships = CompanyUser::where('user_id', $user->id)
                        ->where('company_id', '!=', $company->id)
                        ->where('status', 'active')
                        ->whereIn('role_in_company', ['Owner', 'Admin', 'Recruiter'])
                        ->exists();

                    // Nếu không có membership ở công ty nào khác thì chuyển về candidate
                    if (!$otherCompanyMemberships) {
                        $user->role = 'candidate';
                        $user->save();
                    }
                }
            }

            // Xóa tất cả membership trước
            CompanyUser::where('company_id', $company->id)->delete();

            // Xóa công ty
            $company->delete();

            return response()->json([
                'message' => 'Đã xóa công ty vĩnh viễn và cập nhật role của các thành viên'
            ], 200);
        });
    }

    /**
     * Cập nhật thông tin công ty bởi admin
     */
    // Route::put('/{id}', [CompanyAdminController::class, 'updateCompany']);
    // public function updateCompany(Request $request, $id)
    // {
    //     $company = Company::findOrFail($id);

    //     $request->validate([
    //         'company_name' => 'sometimes|string|max:255',
    //         'address' => 'sometimes|string|max:500',
    //         'description' => 'sometimes|string',
    //       //  'website' => 'sometimes|url|max:255',
    //         'website' => ['nullable', 'regex:/^(https?:\/\/)?([\w.-]+)\.([a-z]{2,})([\/\w .-]*)*\/?$/'],
    //         'email' => 'sometimes|email|max:255',
    //         'phone' => 'sometimes|string|max:20',
    //         'status' => 'sometimes|in:pending,active,inactive,suspended',
    //     ]);

    //     $company->update($request->only([
    //         'company_name', 'address', 'description', 'website', 
    //         'email', 'phone', 'status'
    //     ]));

    //     return response()->json([
    //         'message' => 'Cập nhật thông tin công ty thành công',
    //         'company' => $company
    //     ], 200);
    // }

    /**
     * Lấy thống kê tổng quan về công ty
     */
    public function getCompanyStats()
    {
        $stats = [
            'total_companies' => Company::count(),
            'pending_companies' => Company::where('status', 'pending')->count(),
            'active_companies' => Company::where('status', 'active')->count(),
            'inactive_companies' => Company::where('status', 'inactive')->count(),
            'suspended_companies' => Company::where('status', 'suspended')->count(),
            'companies_this_month' => Company::whereMonth('created_at', now()->month)->count(),
            'companies_today' => Company::whereDate('created_at', today())->count(),
        ];

        return response()->json($stats, 200);
    }

    /**
     * Lấy danh sách thành viên của một công ty
     */
    public function getCompanyMembers($id, Request $request)
    {
        $company = Company::findOrFail($id);

        $query = CompanyUser::with('user:id,name,email,role')
            ->where('company_id', $id);

        // Lọc theo role
        if ($request->has('role')) {
            $query->where('role_in_company', $request->role);
        }

        // Lọc theo status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $members = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'company' => $company,
            'members' => $members
        ], 200);
    }

    /**
     * Xóa thành viên khỏi công ty (bởi admin)
     */
    public function removeMemberFromCompany($companyId, $memberId)
    {
        return DB::transaction(function () use ($companyId, $memberId) {
            $member = CompanyUser::where('company_id', $companyId)
                ->where('id', $memberId)
                ->firstOrFail();

            if ($member->role_in_company === 'Owner') {
                return response()->json([
                    'message' => 'Không thể xóa Owner. Hãy chuyển quyền sở hữu trước.'
                ], 400);
            }

            $member->delete();

            return response()->json([
                'message' => 'Đã xóa thành viên khỏi công ty'
            ], 200);
        });
    }

    /**
     * Thay đổi role của thành viên trong công ty
     */
    public function changeMemberRole(Request $request, $companyId, $memberId)
    {
        $request->validate([
            'role_in_company' => 'required|in:Admin,Recruiter,Owner'
        ]);

        return DB::transaction(function () use ($request, $companyId, $memberId) {
            $member = CompanyUser::where('company_id', $companyId)
                ->where('id', $memberId)
                ->firstOrFail();

            $newRole = $request->role_in_company;

            // Nếu gán Owner, cần xử lý đặc biệt
            if ($newRole === 'Owner') {
                // Tìm Owner hiện tại và hạ xuống Admin
                $currentOwner = CompanyUser::where('company_id', $companyId)
                    ->where('role_in_company', 'Owner')
                    ->first();

                if ($currentOwner && $currentOwner->id !== $member->id) {
                    $currentOwner->role_in_company = 'Admin';
                    $currentOwner->save();
                }
            }

            $member->role_in_company = $newRole;
            $member->save();

            // Cập nhật role user nếu cần
            if ($member->user && in_array($newRole, ['Owner', 'Admin', 'Recruiter'])) {
                $user = User::find($member->user_id);
                if ($user && $user->role !== 'employer') {
                    $user->role = 'employer';
                    $user->save();
                }
            }

            return response()->json([
                'message' => 'Đã thay đổi role thành công',
                'member' => $member->fresh()
            ], 200);
        });
    }

    /**
     * Lấy danh sách công ty gần đây
     */
    public function getRecentCompanies(Request $request)
    {
        $limit = min($request->get('limit', 10), 50);

        $companies = Company::with(['users' => function ($q) {
            $q->where('role_in_company', 'Owner');
        }])
            ->latest()
            ->limit($limit)
            ->get();

        return response()->json($companies, 200);
    }
}
