<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $role)
    {
        if (Auth::guest()) {
            return redirect('/login');
        }

        $user = Auth::user();

        if ($user->hasRole($role) || $user->can($role)) {
            return $next($request);
        }

        // Nếu không đáp ứng, trả về lỗi 403
        abort(403, 'Unauthorized action.');
    }
}
