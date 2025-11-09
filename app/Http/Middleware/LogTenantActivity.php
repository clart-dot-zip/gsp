<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\TenantActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogTenantActivity
{
    /**
     * Handle an incoming request and persist tenant-scoped activity details.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            $this->storeActivity($request, $response);
        } catch (Throwable $exception) {
            Log::warning('Failed to write tenant activity log.', [
                'message' => $exception->getMessage(),
            ]);
        }

        return $response;
    }

    private function storeActivity(Request $request, Response $response): void
    {
        $user = $request->user();
        if (! $user) {
            return;
        }

        $tenantId = $this->resolveTenantId($request);
        if (! $tenantId) {
            return;
        }

        TenantActivityLog::create([
            'tenant_id' => $tenantId,
            'user_id' => $user->getKey(),
            'event' => $this->resolveEvent($request),
            'method' => $request->getMethod(),
            'route_name' => $this->resolveRouteName($request),
            'path' => '/'.$request->path(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status_code' => $response->getStatusCode(),
            'payload' => $this->collectPayload($request),
        ]);
    }

    private function resolveEvent(Request $request): string
    {
        $routeName = $this->resolveRouteName($request);

        if ($routeName === 'tenants.pages.show') {
            $page = (string) $request->route('page');
            $pageName = Str::headline(str_replace('_', ' ', $page));

            return 'Viewed tenant page: '.$pageName;
        }

        if ($routeName) {
            return sprintf('%s %s', $request->getMethod(), $routeName);
        }

        return sprintf('%s %s', $request->getMethod(), '/'.$request->path());
    }

    private function resolveRouteName(Request $request): ?string
    {
        $route = $request->route();

        return $route ? $route->getName() : null;
    }

    private function resolveTenantId(Request $request): ?int
    {
        $route = $request->route();
        if ($route) {
            $tenantParameter = $route->parameter('tenant');
            if ($tenantParameter instanceof Tenant) {
                return $tenantParameter->getKey();
            }

            if (is_numeric($tenantParameter)) {
                return (int) $tenantParameter;
            }

            $tenantIdParameter = $route->parameter('tenant_id');
            if (is_numeric($tenantIdParameter)) {
                return (int) $tenantIdParameter;
            }

            if ($route->getName() === 'tenants.pages.show') {
                $sessionTenantId = (int) $request->session()->get('tenant_id');

                return $sessionTenantId > 0 ? $sessionTenantId : null;
            }
        }

        $requestTenantId = $request->input('tenant_id');
        if (is_numeric($requestTenantId)) {
            return (int) $requestTenantId;
        }

        return null;
    }

    private function collectPayload(Request $request): ?array
    {
        $payload = $request->except([
            '_token',
            '_method',
            'password',
            'password_confirmation',
        ]);

        if (empty($payload)) {
            return null;
        }

        foreach ($payload as $key => $value) {
            if (is_string($value) && mb_strlen($value) > 500) {
                $payload[$key] = mb_substr($value, 0, 500).'…';
            }
        }

        return $payload;
    }
}
