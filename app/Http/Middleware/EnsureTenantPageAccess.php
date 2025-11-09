<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\TenantPageAuthorization;
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
        $pageKey = (string) $request->route('page');
        $user = $request->user();
        $authorizedUser = $user instanceof User ? $user : null;

        if (! TenantPageAuthorization::canAccessPage($authorizedUser, $pageKey)) {
            abort(403);
        }

        return $next($request);
    }
}
