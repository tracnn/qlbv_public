<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Kiểm tra Authorization header
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authorization header is required',
                    'details' => 'Please include \'Authorization: Bearer {token}\' in your request headers'
                ],
                'meta' => [
                    'timestamp' => now()->format('YmdHis'),
                    'request_id' => uniqid('req_')
                ]
            ], 401);
        }

        // Kiểm tra Bearer format
        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Invalid authorization format',
                    'details' => 'Authorization header must be in format: Bearer {token}'
                ],
                'meta' => [
                    'timestamp' => now()->format('YmdHis'),
                    'request_id' => uniqid('req_')
                ]
            ], 401);
        }

        $token = $matches[1];
        $validToken = config('organization.api.access_token');

        // Kiểm tra token
        if (!$validToken || $token !== $validToken) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Invalid access token',
                    'details' => 'The provided token is not valid or has expired'
                ],
                'meta' => [
                    'timestamp' => now()->format('YmdHis'),
                    'request_id' => uniqid('req_')
                ]
            ], 401);
        }

        // Log successful authentication
        \Log::info('API Authentication successful', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'endpoint' => $request->path(),
            'timestamp' => now()->format('YmdHis')
        ]);

        return $next($request);
    }
}