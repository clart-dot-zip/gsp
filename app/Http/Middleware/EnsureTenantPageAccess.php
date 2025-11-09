<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantPageAccess
{
    /**
     * Allow entry when the user has the tenant page permission or is an active tenant player.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has('active_player_id')) {
            return $next($request);
        }

    return (new EnsurePermission())->handle($request, $next, 'view_tenant_pages');
    }
}
