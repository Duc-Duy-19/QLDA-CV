<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Profile;
use App\Mail\AccountSuspendedMail;
use App\Mail\AccountReactivatedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

//
class UserAdminController extends Controller
{
    /**
     * Danh sách tất cả users
     */
    public function index(Request $request)
    {
        $query = User::with('profile');

        // Lọc theo role
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Lọc theo status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Tìm kiếm
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $users = $query->paginate($request->get('per_page', 20));

        return response()->json($users, 200);
    }

    /**
     * Tạm khóa user
     */
    public function suspendUser(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $user = User::findOrFail($id);

        // Không được khóa admin hoặc chính mình
        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'Không thể khóa tài khoản admin'
            ], 403);
        }

        $reason = $request->input('reason', 'Bị khóa bởi quản trị viên');

        // Nếu bạn đã xóa các cột `suspension_reason`, `suspended_at`, `suspended_by`
        // khỏi bảng `users`, đừng gán trực tiếp các thuộc tính đó (sẽ gây SQL error).
        // Ở đây chỉ cập nhật `status`. Lý do và thời điểm suspension sẽ được gửi qua email
        // nhưng không lưu trên model user.
        $user->status = 'suspended';
        $user->save();

        // Gửi email thông báo cho user
        try {
            Mail::to($user->email)->send(new AccountSuspendedMail($user, $reason, auth()->user()));
        } catch (\Exception $e) {
            // Log lỗi nhưng không fail request
            \Illuminate\Support\Facades\Log::error('Failed to send suspension email: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Đã tạm khóa user',
            'user' => $user
        ], 200);
    }

    /**
     * Kích hoạt lại user
     */
    public function reactivateUser($id)
    {
        $user = User::findOrFail($id);

        // ✅ Nếu các cột suspension đã bị xóa, chỉ cập nhật `status`.
        $user->status = 'active';
        $user->save();

        // Gửi email thông báo cho user
        try {
            Mail::to($user->email)->send(new AccountReactivatedMail($user));
        } catch (\Exception $e) {
            // Log lỗi nhưng không fail request
            \Illuminate\Support\Facades\Log::error('Failed to send reactivation email: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Đã kích hoạt lại user',
            'user' => $user
        ], 200);
    }

    /**
     * Thống kê users
     */
    public function getUserStats()
    {
        $stats = [
            'total_users' => User::count(),
            'candidates' => User::where('role', 'candidate')->count(),
            'employers' => User::where('role', 'employer')->count(),
            'admins' => User::where('role', 'admin')->count(),
            'active_users' => User::where('status', 'active')->count(),
            'suspended_users' => User::where('status', 'suspended')->count(),
            'users_this_month' => User::whereMonth('created_at', now()->month)->count(),
        ];

        return response()->json($stats, 200);
    }
}
