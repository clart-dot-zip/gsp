<?php

namespace App\Http\Controllers\TenantPermissions;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantPlayer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class TenantPlayerController extends Controller
{
    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->assertTenantContext($request, $tenant);

        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'steam_id' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('tenant_players', 'steam_id')->where('tenant_id', $tenant->id),
            ],
            'avatar_url' => ['nullable', 'url', 'max:255'],
        ]);

        $steamId = isset($data['steam_id']) && $data['steam_id'] !== '' ? trim($data['steam_id']) : null;
        $avatarUrl = isset($data['avatar_url']) && $data['avatar_url'] !== '' ? trim($data['avatar_url']) : null;

        TenantPlayer::create([
            'tenant_id' => $tenant->id,
            'display_name' => $data['display_name'],
            'steam_id' => $steamId,
            'avatar_url' => $avatarUrl,
        ]);

        return Redirect::route('tenants.pages.show', ['page' => 'permissions_users'])
            ->with('status', 'Player created.');
    }

    public function update(Request $request, Tenant $tenant, TenantPlayer $player): RedirectResponse
    {
        $this->assertTenantContext($request, $tenant);
        abort_unless($player->tenant_id === $tenant->id, 404);

        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'steam_id' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('tenant_players', 'steam_id')
                    ->where('tenant_id', $tenant->id)
                    ->ignore($player->id),
            ],
            'avatar_url' => ['nullable', 'url', 'max:255'],
        ]);

        $steamId = isset($data['steam_id']) && $data['steam_id'] !== '' ? trim($data['steam_id']) : null;
        $avatarUrl = isset($data['avatar_url']) && $data['avatar_url'] !== '' ? trim($data['avatar_url']) : null;

        $player->fill([
            'display_name' => $data['display_name'],
            'steam_id' => $steamId,
            'avatar_url' => $avatarUrl,
        ]);

        $player->save();

        return Redirect::route('tenants.pages.show', ['page' => 'permissions_users'])
            ->with('status', 'Player updated.');
    }

    public function destroy(Request $request, Tenant $tenant, TenantPlayer $player): RedirectResponse
    {
        $this->assertTenantContext($request, $tenant);
        abort_unless($player->tenant_id === $tenant->id, 404);

        $player->delete();

        return Redirect::route('tenants.pages.show', ['page' => 'permissions_users'])
            ->with('status', 'Player removed.');
    }

    protected function assertTenantContext(Request $request, Tenant $tenant): void
    {
        $selectedTenantId = (int) $request->session()->get('tenant_id');
        abort_unless($selectedTenantId === $tenant->id, 403);
    }
}
