<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if ($permission === 'view_tenant_pages' && $request->session()->has('active_player_id')) {
            return $next($request);
        }

        $user = Auth::user();

        if (! $user || (method_exists($user, 'hasPermission') && ! $user->hasPermission($permission))) {
            abort(403);
        }

        return $next($request);
    }
}
