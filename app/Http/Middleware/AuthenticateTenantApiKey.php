<?php

namespace App\Http\Middleware;

use App\Models\TenantApiKey;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AuthenticateTenantApiKey
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $providedKey = $request->header('X-Api-Key') ?: $request->bearerToken();

        if (! $providedKey) {
            return $this->unauthorized('API key missing.');
        }

        $apiKey = TenantApiKey::with('tenant')
            ->where('key_hash', hash('sha256', $providedKey))
            ->first();

        if (! $apiKey || ! $apiKey->tenant) {
            return $this->unauthorized('Invalid API key.');
        }

        $apiKey->forceFill([
            'last_used_at' => Carbon::now(),
        ])->save();

        $request->attributes->set('tenant', $apiKey->tenant);
        $request->attributes->set('tenant_api_key', $apiKey);

        return $next($request);
    }

    private function unauthorized(string $message): JsonResponse
    {
        return new JsonResponse([
            'message' => $message,
        ], 401);
    }
}
