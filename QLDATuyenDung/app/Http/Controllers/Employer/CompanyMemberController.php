<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddCompanyUserRequest;
use App\Http\Requests\UpdateCompanyUserRoleRequest;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class CompanyMemberController extends Controller
{
    //
    public function index(Request $request, int $companyId)
    {
        // 1) Công ty phải active
        $company = Company::where('id', $companyId)
            ->where('status', 'active')
            ->firstOrFail();

        // 2) Chỉ Owner (đang active) của công ty mới được xem danh sách
        $currentUserId = Auth::id();
        $isOwnerActive = CompanyUser::where('company_id', $companyId)
            ->where('user_id', $currentUserId)
            ->where('role_in_company', 'Owner')
            ->where('status', 'active')
            ->exists();

        if (!$isOwnerActive) {
            return response()->json(['error' => 'Chỉ Owner (đang active) mới có quyền xem danh sách user'], 403);
        }

        // 3) Phân trang đơn giản
        $perPage = min(max((int) $request->get('per_page', 20), 1), 100);

        // 4) Lấy danh sách nhân sự kèm thông tin user cơ bản
        $members = CompanyUser::with(['user:id,name,email'])
            ->where('company_id', $companyId)
            ->orderBy('role_in_company')
            ->orderByDesc('joined_at')
            ->paginate($perPage)
            ->appends($request->query());

        return response()->json([
            'message' => 'Danh sách nhân sự của công ty',
            'company_id' => $companyId,
            'per_page' => $perPage,
            'members' => $members,
        ]);
    }

    public function addUser(AddCompanyUserRequest $request, $companyId)
    {
        $currentUserId = Auth::id();

        // 1) Công ty phải active
        $company = Company::findOrFail($companyId);
        if ($company->status !== 'active') {
            return response()->json(['error' => 'Công ty chưa được duyệt (active).'], 403);
        }

        // 2) Chỉ Owner đang active mới được thao tác
        $isOwnerActive = CompanyUser::where('company_id', $companyId)
            ->where('user_id', $currentUserId)
            ->where('role_in_company', 'Owner')
            ->where('status', 'active')
            ->exists();

        if (!$isOwnerActive) {
            return response()->json(['error' => 'Chỉ Owner (đang active) mới có quyền thêm nhân sự'], 403);
        }

        // 3) Role mặc định
        $role = $request->input('role_in_company', 'Recruiter');
        if ($role === 'Owner') {
            return response()->json(['error' => 'Không thể gán role Owner qua lời mời.'], 403);
        }

        // 4) Xử lý mời theo email hoặc user_id
        $userId = $request->user_id ?? null;
        $email = $request->email ?? null;

        if ($userId) {
            // Không cho mời chính mình
            if ((int)$userId === (int)$currentUserId) {
                return response()->json(['error' => 'Không thể mời chính mình.'], 422);
            }

            // kiểm user tồn tại
            $targetUser = User::find($userId);
            if (! $targetUser) {
                return response()->json(['error' => 'Người dùng không tồn tại.'], 404);
            }

            // Không cho mời nếu target đang là thành viên active của công ty khác
            $isActiveMemberElsewhere = CompanyUser::where('user_id', $targetUser->id)
                ->where('company_id', '!=', $companyId)
                ->where('status', 'active')
                ->exists();
            if ($isActiveMemberElsewhere) {
                return response()->json(['error' => 'Người dùng hiện đang là thành viên của công ty khác.'], 403);
            }

            // NGUYÊN TẮC: nếu target là employer Owner ở bất kỳ công ty nào -> không cho mời
            if ($targetUser->role === 'employer') {
                $isOwnerElsewhere = CompanyUser::where('user_id', $targetUser->id)
                    ->where('role_in_company', 'Owner')
                    ->where('status', 'active')
                    ->exists();
                if ($isOwnerElsewhere) {
                    return response()->json(['error' => 'Chỉ được mời candidate. Không thể mời Owner (employer).'], 403);
                }
            }

            // Kiểm tra trùng trong cùng công ty
            $exists = CompanyUser::where('company_id', $companyId)
                ->where('user_id', $userId)
                ->exists();
            if ($exists) {
                return response()->json(['error' => 'User đã là thành viên hoặc đang chờ duyệt'], 409);
            }

            $companyUser = CompanyUser::create([
                'company_id'      => $companyId,
                'user_id'         => $userId,
                'email'           => null,
                'role_in_company' => $role,
                'status'          => 'pending',
                'joined_at'       => null,
            ]);
        } elseif ($email) {
            // Check user theo email
            $user = User::where('email', $email)->first();

            // Nếu user tồn tại
            if ($user) {
                if ((int)$user->id === (int)$currentUserId) {
                    return response()->json(['error' => 'Không thể mời chính mình.'], 422);
                }

                // Không cho mời nếu target đang là thành viên active của công ty khác
                $isActiveMemberElsewhere = CompanyUser::where('user_id', $user->id)
                    ->where('company_id', '!=', $companyId)
                    ->where('status', 'active')
                    ->exists();
                if ($isActiveMemberElsewhere) {
                    return response()->json(['error' => 'Người dùng hiện đang là thành viên của công ty khác.'], 403);
                }

                // NGUYÊN TẮC: nếu email thuộc employer Owner -> không cho mời
                if ($user->role === 'employer') {
                    $isOwnerElsewhere = CompanyUser::where('user_id', $user->id)
                        ->where('role_in_company', 'Owner')
                        ->where('status', 'active')
                        ->exists();
                    if ($isOwnerElsewhere) {
                        return response()->json(['error' => 'Chỉ được mời candidate. Email này thuộc Owner (employer).'], 403);
                    }
                }

                $exists = CompanyUser::where('company_id', $companyId)
                    ->where('user_id', $user->id)
                    ->exists();
                if ($exists) {
                    return response()->json(['error' => 'User đã là thành viên hoặc đang chờ duyệt'], 409);
                }

                $companyUser = CompanyUser::create([
                    'company_id'      => $companyId,
                    'user_id'         => $user->id,
                    'email'           => $email,
                    'role_in_company' => $role,
                    'status'          => 'pending',
                    'joined_at'       => null,
                ]);
            } else {
                // User chưa tồn tại → tạo lời mời chờ
                $exists = CompanyUser::where('company_id', $companyId)
                    ->where('email', $email)
                    ->exists();
                if ($exists) {
                    return response()->json(['error' => 'Email đã được mời vào công ty này.'], 409);
                }

                $companyUser = CompanyUser::create([
                    'company_id'      => $companyId,
                    'user_id'         => null,
                    'email'           => $email,
                    'role_in_company' => $role,
                    'status'          => 'pending',
                    'joined_at'       => null,
                ]);
            }
        } else {
            return response()->json(['error' => 'Phải có user_id hoặc email để mời.'], 422);
        }

        return response()->json([
            'message' => 'Đã gửi lời mời vào công ty.',
            'company_user' => $companyUser,
        ], 201);
    }

    // Cập nhật role (Owner active + Company active)
    public function updateRole(UpdateCompanyUserRoleRequest $request, int $companyId, int $companyUserId)
    {
        // 1) Công ty phải active
        $company = Company::where('id', $companyId)
            ->where('status', 'active')
            ->firstOrFail();

        // 2) Người gọi API phải là Owner (đang active) của công ty
        $currentUserId = Auth::id();
        $isOwnerActive = CompanyUser::where('company_id', $companyId)
            ->where('user_id', $currentUserId)
            ->where('role_in_company', 'Owner')
            ->where('status', 'active')
            ->exists();

        if (!$isOwnerActive) {
            return response()->json(['error' => 'Chỉ Owner (đang active) mới có quyền chỉnh sửa role'], 403);
        }

        // 3) Lấy đúng bản ghi trung gian theo id + company_id
        $companyUser = CompanyUser::where('id', $companyUserId)
            ->where('company_id', $companyId)
            ->firstOrFail();

        // 4) Không cho sửa role của Owner
        if ($companyUser->role_in_company === 'Owner') {
            return response()->json(['error' => 'Không thể thay đổi role của Owner'], 403);
        }

        // 5) Không cho gán Owner qua endpoint này
        if (($request->role_in_company ?? null) === 'Owner') {
            return response()->json(['error' => 'Không thể gán role Owner qua endpoint này. Hãy dùng chức năng chuyển quyền sở hữu.'], 403);
        }

        // 6) Cập nhật
        $companyUser->update([
            'role_in_company' => $request->role_in_company ?? $companyUser->role_in_company,
        ]);

        return response()->json([
            'message' => 'Cập nhật quyền thành công!',
            'company_user' => $companyUser->fresh(),
        ]);
    }

    /**
     * Xóa user khỏi công ty (Owner active + Company active)
     */
    public function removeUser(int $companyId, int $companyUserId)
    {
        // 1) Công ty phải active
        $company = Company::where('id', $companyId)
            ->where('status', 'active')
            ->firstOrFail();

        // 2) Lấy đúng bản ghi trung gian theo id + company_id
        $companyUser = CompanyUser::where('id', $companyUserId)
            ->where('company_id', $companyId)
            ->firstOrFail();

        // 3) Không cho xóa Owner
        if ($companyUser->role_in_company === 'Owner') {
            return response()->json(['error' => 'Không thể xóa Owner khỏi công ty'], 403);
        }

        // 4) Người gọi API phải là Owner (đang active) của công ty
        $currentUserId = Auth::id();
        $isOwnerActive = CompanyUser::where('company_id', $companyId)
            ->where('user_id', $currentUserId)
            ->where('role_in_company', 'Owner')
            ->where('status', 'active')
            ->exists();

        if (!$isOwnerActive) {
            return response()->json(['error' => 'Chỉ Owner (đang active) mới có quyền xóa user'], 403);
        }

        // 5) Optional: không cho Owner tự xóa chính mình
        if ((int) $companyUser->user_id === (int) $currentUserId) {
            return response()->json(['error' => 'Owner không thể tự xóa bản thân khỏi công ty.'], 403);
        }

        // 6) Nếu có user_id thì cố gắng chuyển role về candidate (nếu user không đang là Owner active ở công ty khác)
        $roleChanged = false;
        if ($companyUser->user_id) {
            $user = User::find($companyUser->user_id);
            if ($user) {
                $isOwnerElsewhere = CompanyUser::where('user_id', $user->id)
                    ->where('role_in_company', 'Owner')
                    ->where('status', 'active')
                    ->where('company_id', '!=', $companyId)
                    ->exists();

                if (! $isOwnerElsewhere) {
                    $user->role = 'candidate';
                    $user->save();
                    $roleChanged = true;
                }
            }
        }

        // 7) Xóa bản ghi liên kết
        $companyUser->delete();

        $message = 'Xóa user khỏi công ty thành công';
        if ($roleChanged) {
            $message .= ' và đã chuyển role user về candidate';
        }

        return response()->json([
            'message' => $message,
        ]);
    }
}
