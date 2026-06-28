<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ForcePasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            if (!session()->has('needs_password_change')) {
                $needsChange = Hash::check(Auth::user()->dni, Auth::user()->password_hash);
                session(['needs_password_change' => $needsChange]);
            }

            if (session('needs_password_change')) {
                $excludedRoutes = ['password.change.show', 'password.change.update', 'logout'];
                
                if (!in_array($request->route()?->getName(), $excludedRoutes)) {
                    return redirect()->route('password.change.show');
                }
            }
        }

        return $next($request);
    }
}
