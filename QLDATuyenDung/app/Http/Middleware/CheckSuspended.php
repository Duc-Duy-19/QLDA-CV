<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSuspended
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Kiểm tra user đã đăng nhập chưa
        $user = $request->user();
        
        if (!$user) {
            return $next($request);
        }

        // ✅ Kiểm tra user có bị khóa không — chỉ dựa trên `status`
        if ($user->status === 'suspended') {
            $message = 'Tài khoản của bạn đã bị khóa và không thể thực hiện hành động này';

            // Không truy cập trực tiếp các trường suspension_* vì có thể đã bị xóa khỏi DB.
            return response()->json([
                'message' => $message,
                'status' => 'suspended'
            ], 403);
        }

        return $next($request);
    }
}

