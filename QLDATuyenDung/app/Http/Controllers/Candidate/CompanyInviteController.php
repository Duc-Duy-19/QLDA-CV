<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class CompanyInviteController extends Controller
{
    public function pendingInvites()
    {
        $userId = Auth::id();

        $invites = CompanyUser::with('company')
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->get();

        return response()->json([
            'message' => 'Danh sách lời mời đang chờ',
            'invites' => $invites
        ]);
    }

    public function acceptInvite(int $companyId)
    {
        $userId = Auth::id();

        $membership = CompanyUser::where('company_id', $companyId)
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->first();

        if (!$membership) {
            return response()->json(['error' => 'Không tìm thấy lời mời hoặc đã xử lý.'], 404);
        }

        $company = Company::findOrFail($companyId);
        if ($company->status !== 'active') {
            return response()->json(['error' => 'Công ty chưa active.'], 403);
        }

        $membership->status = 'active';
        $membership->joined_at = now();
        $membership->save();

        $user = User::find($userId);
        if ($user && $user->role !== 'employer' && in_array($membership->role_in_company, ['Admin','Recruiter','Owner'])) {
            $user->role = 'employer';
            $user->save();
        }

        return response()->json([
            'message' => 'Bạn đã tham gia công ty.',
            'company_user' => $membership->fresh(),
        ]);
    }
}

