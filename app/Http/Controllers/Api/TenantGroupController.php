<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantGroup;
use App\Models\TenantPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

class TenantGroupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenant = $this->resolveTenant($request);

        $groups = $tenant->permissionGroups()
            ->with([
                'permissions:id,tenant_id,name,slug,external_reference',
                'parents:id',
                'children:id',
                'players:id',
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (TenantGroup $group) => $this->transformGroup($group));

        return new JsonResponse(['data' => $groups]);
    }

    public function store(Request $request): JsonResponse
    {
        $tenant = $this->resolveTenant($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'external_reference' => ['nullable', 'string', 'max:255'],
            'parent_ids' => ['nullable', 'array'],
            'parent_ids.*' => ['integer', 'exists:tenant_groups,id'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:tenant_permissions,id'],
        ]);

        $group = TenantGroup::create([
            'tenant_id' => $tenant->id,
            'name' => $data['name'],
            'slug' => TenantGroup::generateUniqueSlug($data['name'], $tenant->id),
            'description' => $data['description'] ?? null,
            'external_reference' => $data['external_reference'] ?? null,
        ]);

        $parentIds = $this->filterTenantGroupIds($tenant, Arr::get($data, 'parent_ids', []), $group->id);
        $group->parents()->sync($parentIds);

        $permissionIds = $this->filterTenantPermissionIds($tenant, Arr::get($data, 'permission_ids', []));
        $group->permissions()->sync($permissionIds);

        $group->load([
            'permissions:id,tenant_id,name,slug,external_reference',
            'parents:id',
            'children:id',
            'players:id',
        ]);

        return new JsonResponse(['data' => $this->transformGroup($group)], Response::HTTP_CREATED);
    }

    public function show(Request $request, int $group): JsonResponse
    {
        $tenant = $this->resolveTenant($request);
        $tenantGroup = $this->findTenantGroup($tenant, $group);

        $tenantGroup->load([
            'permissions:id,tenant_id,name,slug,external_reference',
            'parents:id',
            'children:id',
            'players:id',
        ]);

        return new JsonResponse(['data' => $this->transformGroup($tenantGroup)]);
    }

    public function update(Request $request, int $group): JsonResponse
    {
        $tenant = $this->resolveTenant($request);
        $tenantGroup = $this->findTenantGroup($tenant, $group);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'external_reference' => ['nullable', 'string', 'max:255'],
            'parent_ids' => ['nullable', 'array'],
            'parent_ids.*' => ['integer', 'exists:tenant_groups,id'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:tenant_permissions,id'],
        ]);

        if (array_key_exists('name', $data) && $data['name'] !== $tenantGroup->name) {
            $tenantGroup->slug = TenantGroup::generateUniqueSlug($data['name'], $tenant->id);
        }

        $tenantGroup->fill([
            'name' => $data['name'] ?? $tenantGroup->name,
            'description' => $data['description'] ?? $tenantGroup->description,
            'external_reference' => $data['external_reference'] ?? $tenantGroup->external_reference,
        ]);

        $tenantGroup->save();

        if (array_key_exists('parent_ids', $data)) {
            $parentIds = $this->filterTenantGroupIds($tenant, $data['parent_ids'] ?? [], $tenantGroup->id);
            $tenantGroup->parents()->sync($parentIds);
        }

        if (array_key_exists('permission_ids', $data)) {
            $permissionIds = $this->filterTenantPermissionIds($tenant, $data['permission_ids'] ?? []);
            $tenantGroup->permissions()->sync($permissionIds);
        }

        $tenantGroup->load([
            'permissions:id,tenant_id,name,slug,external_reference',
            'parents:id',
            'children:id',
            'players:id',
        ]);

        return new JsonResponse(['data' => $this->transformGroup($tenantGroup)]);
    }

    public function destroy(Request $request, int $group): JsonResponse
    {
        $tenant = $this->resolveTenant($request);
        $tenantGroup = $this->findTenantGroup($tenant, $group);

        $tenantGroup->delete();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    private function resolveTenant(Request $request): Tenant
    {
        /** @var Tenant|null $tenant */
        $tenant = $request->attributes->get('tenant');

        abort_unless($tenant instanceof Tenant, Response::HTTP_INTERNAL_SERVER_ERROR, 'Tenant context missing.');

        return $tenant;
    }

    private function findTenantGroup(Tenant $tenant, int $groupId): TenantGroup
    {
        $group = TenantGroup::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey($groupId)
            ->first();

        abort_unless($group instanceof TenantGroup, Response::HTTP_NOT_FOUND, 'Group not found.');

        return $group;
    }

    private function filterTenantGroupIds(Tenant $tenant, array $ids, ?int $excludeId = null): array
    {
        if ($ids === []) {
            return [];
        }

        $query = TenantGroup::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('id', $ids);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->pluck('id')->values()->all();
    }

    private function filterTenantPermissionIds(Tenant $tenant, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return TenantPermission::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->values()
            ->all();
    }

    private function transformGroup(TenantGroup $group): array
    {
        $group->loadMissing([
            'permissions:id,tenant_id,name,slug,external_reference',
            'parents:id',
            'children:id',
            'players:id',
        ]);

        return [
            'id' => $group->id,
            'tenant_id' => $group->tenant_id,
            'name' => $group->name,
            'slug' => $group->slug,
            'description' => $group->description,
            'external_reference' => $group->external_reference,
            'parent_ids' => $group->parents->pluck('id')->values()->all(),
            'child_ids' => $group->children->pluck('id')->values()->all(),
            'player_ids' => $group->players->pluck('id')->values()->all(),
            'permissions' => $group->permissions->map(static function (TenantPermission $permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'slug' => $permission->slug,
                    'external_reference' => $permission->external_reference,
                ];
            })->values()->all(),
        ];
    }
}
