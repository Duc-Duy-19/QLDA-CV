<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Mail\PasswordResetMail;
use App\Models\User;
use App\Models\CompanyUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

//
class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $data['role'] = 'candidate';
        $data['status'] = 'active';
        $user = User::create($data);

        // Liên kết lời mời công ty với user mới
        CompanyUser::whereNull('user_id')
            ->where('email', $user->email)
            ->update(['user_id' => $user->id]);


        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $cred = $request->validated();

        $user = User::where('email', $cred['email'])->first();

        if (! $user || ! Hash::check($cred['password'], $user->password)) {
            return response()->json(['message' => 'Email hoặc mật khẩu không đúng'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'needs_profile' => $user->candidate ? false : true,
            'profile_endpoint' => '/api/profile'
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Đã đăng xuất']);
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $email = $request->validated()['email'];
        $token = Str::random(64);

        // Lưu hoặc cập nhật token trong bảng password_reset_tokens
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => $token, 'created_at' => Carbon::now()]
        );

        // Gửi mail
        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json(['message' => 'Không tìm thấy user với email này'], 404);
        }
        Mail::to($email)->send(new PasswordResetMail($user, $token));

        return response()->json(['message' => 'Gửi email lấy lại mật khẩu thành công']);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $data = $request->validated();

        $record = DB::table('password_reset_tokens')->where('email', $data['email'])->first();

        if (! $record || ! hash_equals($record->token, $data['token'])) {
            return response()->json(['message' => 'Token không hợp lệ'], 400);
        }

        // Kiểm tra hết hạn (ví dụ 60 phút)
        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(60)->isPast()) {
            return response()->json(['message' => 'Token đã hết hạn'], 400);
        }

        // Cập nhật mật khẩu
        $user = User::where('email', $data['email'])->first();
        $user->password = Hash::make($data['password']);
        $user->save();

        // Xóa token (an toàn)
        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

        return response()->json(['message' => 'Đặt lại mật khẩu thành công']);
    }

    // Ví dụ route kiểm tra role
    public function adminOnly(Request $request)
    {
        return response()->json(['message' => 'Chỉ admin mới vào được', 'user' => $request->user()]);
    }
}
