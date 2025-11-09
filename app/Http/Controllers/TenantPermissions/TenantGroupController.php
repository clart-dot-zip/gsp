<?php

namespace App\Http\Controllers\TenantPermissions;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class TenantGroupController extends Controller
{
    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->assertTenantContext($request, $tenant);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'external_reference' => ['nullable', 'string', 'max:255'],
            'parent_ids' => ['nullable', 'array'],
            'parent_ids.*' => ['integer', 'exists:tenant_groups,id'],
        ]);

        $group = TenantGroup::create([
            'tenant_id' => $tenant->id,
            'name' => $data['name'],
            'slug' => TenantGroup::generateUniqueSlug($data['name'], $tenant->id),
            'description' => $data['description'] ?? null,
            'external_reference' => $data['external_reference'] ?? null,
        ]);

        $parentIds = $this->filterTenantGroupIds($data['parent_ids'] ?? [], $tenant);
        $parentIds = array_values(array_filter($parentIds, static fn (int $id): bool => $id !== $group->id));
        $group->parents()->sync($parentIds);

        return Redirect::route('tenants.pages.show', ['page' => 'permissions_groups'])
            ->with('status', 'Group created.');
    }

    public function update(Request $request, Tenant $tenant, TenantGroup $group): RedirectResponse
    {
        $this->assertTenantContext($request, $tenant);
        abort_unless($group->tenant_id === $tenant->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'external_reference' => ['nullable', 'string', 'max:255'],
            'parent_ids' => ['nullable', 'array'],
            'parent_ids.*' => ['integer', 'exists:tenant_groups,id'],
        ]);

        if ($group->name !== $data['name']) {
            $group->slug = TenantGroup::generateUniqueSlug($data['name'], $tenant->id);
        }

        $group->fill([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'external_reference' => $data['external_reference'] ?? null,
        ]);

        $group->save();

        $parentIds = $this->filterTenantGroupIds($data['parent_ids'] ?? [], $tenant);
        $parentIds = array_values(array_filter($parentIds, static fn (int $id): bool => $id !== $group->id));
        $group->parents()->sync($parentIds);

        return Redirect::route('tenants.pages.show', ['page' => 'permissions_groups'])
            ->with('status', 'Group updated.');
    }

    public function destroy(Request $request, Tenant $tenant, TenantGroup $group): RedirectResponse
    {
        $this->assertTenantContext($request, $tenant);
        abort_unless($group->tenant_id === $tenant->id, 404);

        $group->delete();

        return Redirect::route('tenants.pages.show', ['page' => 'permissions_groups'])
            ->with('status', 'Group removed.');
    }

    protected function assertTenantContext(Request $request, Tenant $tenant): void
    {
        $selectedTenantId = (int) $request->session()->get('tenant_id');
        abort_unless($selectedTenantId === $tenant->id, 403);
    }

    /**
     * @param array<int, int> $ids
     * @return array<int, int>
     */
    protected function filterTenantGroupIds(array $ids, Tenant $tenant): array
    {
        if ($ids === []) {
            return [];
        }

        return TenantGroup::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->values()
            ->all();
    }
}
