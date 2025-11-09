<?php

namespace App\Http\Controllers\TenantPermissions;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantGroup;
use App\Models\TenantPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class TenantGroupPermissionController extends Controller
{
    public function sync(Request $request, Tenant $tenant, TenantGroup $group): RedirectResponse
    {
        $this->assertTenantContext($request, $tenant);
        abort_unless($group->tenant_id === $tenant->id, 404);

        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:tenant_permissions,id'],
        ]);

        $permissionIds = TenantPermission::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('id', $data['permissions'] ?? [])
            ->pluck('id')
            ->all();

        $group->permissions()->sync($permissionIds);

        return Redirect::route('tenants.pages.show', ['page' => 'permissions_group_permissions'])
            ->with('status', 'Group permissions updated.');
    }

    protected function assertTenantContext(Request $request, Tenant $tenant): void
    {
        $selectedTenantId = (int) $request->session()->get('tenant_id');
        abort_unless($selectedTenantId === $tenant->id, 403);
    }
}
