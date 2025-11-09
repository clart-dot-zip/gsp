<?php

namespace App\Http\Controllers\TenantPermissions;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class TenantPermissionController extends Controller
{
    public function edit(Request $request, Tenant $tenant, TenantPermission $permission): View
    {
        $this->assertTenantContext($request, $tenant);
        abort_unless($permission->tenant_id === $tenant->id, 404);

    $permission->load(['groups:id,name']);
    $permission->setRelation('groups', $permission->groups->sortBy('name')->values());

        return view('tenants.permissions.definitions.edit', [
            'tenant' => $tenant,
            'permission' => $permission,
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->assertTenantContext($request, $tenant);

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

        return Redirect::route('tenants.permissions.definitions.edit', ['tenant' => $tenant, 'permission' => $permission])
            ->with('status', 'Permission created.');
    }

    public function update(Request $request, Tenant $tenant, TenantPermission $permission): RedirectResponse
    {
        $this->assertTenantContext($request, $tenant);
        abort_unless($permission->tenant_id === $tenant->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'external_reference' => ['nullable', 'string', 'max:255'],
        ]);

        if ($permission->name !== $data['name']) {
            $permission->slug = TenantPermission::generateUniqueSlug($data['name'], $tenant->id);
        }

        $permission->fill([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'external_reference' => $data['external_reference'] ?? null,
        ]);

        $permission->save();

        return Redirect::route('tenants.permissions.definitions.edit', ['tenant' => $tenant, 'permission' => $permission])
            ->with('status', 'Permission updated.');
    }

    public function destroy(Request $request, Tenant $tenant, TenantPermission $permission): RedirectResponse
    {
        $this->assertTenantContext($request, $tenant);
        abort_unless($permission->tenant_id === $tenant->id, 404);

        $permission->delete();

        return Redirect::route('tenants.pages.show', ['page' => 'permissions_group_permissions'])
            ->with('status', 'Permission removed.');
    }

    protected function assertTenantContext(Request $request, Tenant $tenant): void
    {
        $selectedTenantId = (int) $request->session()->get('tenant_id');
        abort_unless($selectedTenantId === $tenant->id, 403);
    }
}
