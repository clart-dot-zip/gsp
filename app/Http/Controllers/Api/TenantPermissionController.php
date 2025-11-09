<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantPermissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenant = $this->resolveTenant($request);

        $permissions = TenantPermission::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get()
            ->map(fn (TenantPermission $permission) => $this->transformPermission($permission));

        return new JsonResponse(['data' => $permissions]);
    }

    public function store(Request $request): JsonResponse
    {
        $tenant = $this->resolveTenant($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'external_reference' => ['nullable', 'string', 'max:255'],
        ]);

        $permission = TenantPermission::create([
            'tenant_id' => $tenant->id,
            'name' => $data['name'],
            'slug' => TenantPermission::generateUniqueSlug($data['name'], $tenant->id),
            'description' => $data['description'] ?? null,
            'external_reference' => $data['external_reference'] ?? null,
        ]);

        return new JsonResponse(['data' => $this->transformPermission($permission)], Response::HTTP_CREATED);
    }

    public function show(Request $request, int $permission): JsonResponse
    {
        $tenant = $this->resolveTenant($request);
        $tenantPermission = $this->findTenantPermission($tenant, $permission);

        return new JsonResponse(['data' => $this->transformPermission($tenantPermission)]);
    }

    public function update(Request $request, int $permission): JsonResponse
    {
        $tenant = $this->resolveTenant($request);
        $tenantPermission = $this->findTenantPermission($tenant, $permission);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'external_reference' => ['nullable', 'string', 'max:255'],
        ]);

        if (array_key_exists('name', $data) && $data['name'] !== $tenantPermission->name) {
            $tenantPermission->slug = TenantPermission::generateUniqueSlug($data['name'], $tenant->id);
        }

        $tenantPermission->fill([
            'name' => $data['name'] ?? $tenantPermission->name,
            'description' => $data['description'] ?? $tenantPermission->description,
            'external_reference' => $data['external_reference'] ?? $tenantPermission->external_reference,
        ]);

        $tenantPermission->save();

        return new JsonResponse(['data' => $this->transformPermission($tenantPermission)]);
    }

    public function destroy(Request $request, int $permission): JsonResponse
    {
        $tenant = $this->resolveTenant($request);
        $tenantPermission = $this->findTenantPermission($tenant, $permission);

        $tenantPermission->delete();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    private function resolveTenant(Request $request): Tenant
    {
        /** @var Tenant|null $tenant */
        $tenant = $request->attributes->get('tenant');

        abort_unless($tenant instanceof Tenant, Response::HTTP_INTERNAL_SERVER_ERROR, 'Tenant context missing.');

        return $tenant;
    }

    private function findTenantPermission(Tenant $tenant, int $permissionId): TenantPermission
    {
        $permission = TenantPermission::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey($permissionId)
            ->first();

        abort_unless($permission instanceof TenantPermission, Response::HTTP_NOT_FOUND, 'Permission not found.');

        return $permission;
    }

    private function transformPermission(TenantPermission $permission): array
    {
        $permission->loadMissing('groups:id');

        return [
            'id' => $permission->id,
            'tenant_id' => $permission->tenant_id,
            'name' => $permission->name,
            'slug' => $permission->slug,
            'description' => $permission->description,
            'external_reference' => $permission->external_reference,
            'group_ids' => $permission->groups->pluck('id')->values()->all(),
        ];
    }
}
