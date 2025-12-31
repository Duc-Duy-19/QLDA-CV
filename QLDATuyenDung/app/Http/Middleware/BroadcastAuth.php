<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class BroadcastAuth
{
    /**
     * Handle an incoming request.
     * 
     * Supports both web session and Sanctum token authentication
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Debug logging
        Log::info('BroadcastAuth: Checking authentication', [
            'has_session' => $request->hasSession(),
            'session_id' => $request->session()->getId() ?? 'none',
            'has_bearer_token' => $request->bearerToken() ? 'yes' : 'no',
            'authorization_header' => $request->header('Authorization') ? 'present' : 'missing',
            'cookies' => array_keys($request->cookies->all()),
        ]);
        
        // Try to authenticate via web session first
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            Log::info('BroadcastAuth: Authenticated via web session', [
                'user_id' => $user->id,
                'user_email' => $user->email
            ]);
            return $next($request);
        }
        
        // If no session, try Sanctum token from Authorization header
        // Check both Bearer token and X-Authorization header
        $token = $request->bearerToken() 
            ?? $request->header('X-Authorization') 
            ?? ($request->header('Authorization') ? preg_replace('/^Bearer\s+/i', '', $request->header('Authorization')) : null);
        
        // ✅ CRITICAL: Log token extraction
        Log::info('BroadcastAuth: Token extraction', [
            'bearerToken' => $request->bearerToken() ? substr($request->bearerToken(), 0, 10) . '...' : 'null',
            'x_authorization' => $request->header('X-Authorization') ? substr($request->header('X-Authorization'), 0, 10) . '...' : 'null',
            'authorization_header' => $request->header('Authorization') ? substr($request->header('Authorization'), 0, 30) . '...' : 'null',
            'extracted_token' => $token ? substr($token, 0, 10) . '...' : 'null',
            'all_headers' => array_keys($request->headers->all())
        ]);
        
        if ($token) {
            try {
                // Find token and authenticate user manually
                $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
                
                Log::info('BroadcastAuth: Token lookup', [
                    'token_preview' => substr($token, 0, 10) . '...',
                    'token_found' => $accessToken ? 'yes' : 'no',
                    'token_id' => $accessToken ? $accessToken->id : null
                ]);
                
                if ($accessToken) {
                    // Check if token is expired
                    if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
                        Log::warning('BroadcastAuth: Token expired', [
                            'token_id' => $accessToken->id,
                            'expires_at' => $accessToken->expires_at
                        ]);
                    } else if ($accessToken->tokenable) {
                        $user = $accessToken->tokenable;
                        
                        // Create web session from token for better compatibility
                        if (!$request->hasSession() || !Auth::guard('web')->check()) {
                            Auth::guard('web')->login($user);
                            $request->session()->regenerate();
                        } else {
                            // Just set user to default guard
                            Auth::setUser($user);
                        }
                        
                        Log::info('BroadcastAuth: Authenticated via Sanctum token', [
                            'user_id' => $user->id,
                            'user_email' => $user->email,
                            'token_id' => $accessToken->id,
                            'session_created' => $request->hasSession(),
                            'session_id' => $request->session()->getId()
                        ]);
                        return $next($request);
                    }
                }
                
                Log::warning('BroadcastAuth: Invalid or expired Sanctum token', [
                    'token_preview' => substr($token, 0, 10) . '...',
                    'token_exists' => $accessToken ? 'yes' : 'no',
                    'tokenable_exists' => $accessToken && $accessToken->tokenable ? 'yes' : 'no'
                ]);
            } catch (\Exception $e) {
                Log::error('BroadcastAuth: Exception during token authentication', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        } else {
            Log::warning('BroadcastAuth: No token found in request', [
                'authorization_header' => $request->header('Authorization') ? 'present' : 'missing',
                'bearer_token' => $request->bearerToken() ? 'present' : 'missing',
                'x_authorization' => $request->header('X-Authorization') ? 'present' : 'missing',
                'all_headers' => array_keys($request->headers->all())
            ]);
        }
        
        // If both fail, return 403
        Log::error('BroadcastAuth: Authentication failed - returning 403', [
            'has_session' => $request->hasSession(),
            'has_token' => $token ? 'yes' : 'no',
            'origin' => $request->header('Origin'),
            'referer' => $request->header('Referer'),
            'host' => $request->getHost(),
            'cookies' => array_keys($request->cookies->all()),
        ]);
        
        // ✅ FIX: Better error message for production debugging
        $isProduction = config('app.env') === 'production';
        return response()->json([
            'message' => 'Unauthenticated.',
            'error' => 'Authentication failed. Please ensure you are logged in and your session is valid.',
            ...($isProduction ? [] : [
                'debug' => [
                    'has_session' => $request->hasSession(),
                    'session_id' => $request->session()->getId() ?? 'none',
                    'has_bearer_token' => $request->bearerToken() ? 'yes' : 'no',
                    'has_authorization_header' => $request->header('Authorization') ? 'yes' : 'no',
                    'origin' => $request->header('Origin'),
                    'host' => $request->getHost(),
                ]
            ])
        ], 403);
    }
}

