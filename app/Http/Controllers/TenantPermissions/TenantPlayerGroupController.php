<?php

namespace App\Http\Controllers\TenantPermissions;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantGroup;
use App\Models\TenantPlayer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class TenantPlayerGroupController extends Controller
{
    public function attach(Request $request, Tenant $tenant, TenantPlayer $player): RedirectResponse
    {
        $this->assertTenantContext($request, $tenant);
        abort_unless($player->tenant_id === $tenant->id, 404);

        $data = $request->validate([
            'group_id' => ['required', 'integer', 'exists:tenant_groups,id'],
        ]);

        $group = TenantGroup::query()
            ->where('tenant_id', $tenant->id)
            ->where('id', $data['group_id'])
            ->firstOrFail();

        $player->groups()->sync([$group->id]);

        return Redirect::route('tenants.pages.show', ['page' => 'permissions_users'])
            ->with('status', 'Group assigned to player.');
    }

    public function detach(Request $request, Tenant $tenant, TenantPlayer $player, TenantGroup $group): RedirectResponse
    {
        $this->assertTenantContext($request, $tenant);
        abort_unless($player->tenant_id === $tenant->id, 404);
        abort_unless($group->tenant_id === $tenant->id, 404);

        $player->groups()->detach($group->id);

        return Redirect::route('tenants.pages.show', ['page' => 'permissions_users'])
            ->with('status', 'Group removed from player.');
    }

    protected function assertTenantContext(Request $request, Tenant $tenant): void
    {
        $selectedTenantId = (int) $request->session()->get('tenant_id');
        abort_unless($selectedTenantId === $tenant->id, 403);
    }
}
