<?php

namespace App\Http\Controllers\TenantPermissions;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantPlayer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantPlayerController extends Controller
{
    public function edit(Request $request, Tenant $tenant, TenantPlayer $player): View
    {
        $this->assertTenantContext($request, $tenant);
        abort_unless($player->tenant_id === $tenant->id, 404);

    $player->load('groups:id,name');
    $player->setRelation('groups', $player->groups->sortBy('name')->values());

        $groupOptions = $tenant->permissionGroups()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('tenants.permissions.players.edit', [
            'tenant' => $tenant,
            'player' => $player,
            'groupOptions' => $groupOptions,
        ]);
    }

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
            'group_id' => [
                'nullable',
                'integer',
                Rule::exists('tenant_groups', 'id')->where('tenant_id', $tenant->id),
            ],
        ]);

        $steamId = isset($data['steam_id']) && $data['steam_id'] !== '' ? trim($data['steam_id']) : null;
        $avatarUrl = isset($data['avatar_url']) && $data['avatar_url'] !== '' ? trim($data['avatar_url']) : null;

        $player = TenantPlayer::create([
            'tenant_id' => $tenant->id,
            'display_name' => $data['display_name'],
            'steam_id' => $steamId,
            'avatar_url' => $avatarUrl,
        ]);

        if (! empty($data['group_id'])) {
            $player->groups()->sync([$data['group_id']]);
        }

        return Redirect::route('tenants.permissions.players.edit', ['tenant' => $tenant, 'player' => $player])
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
            'group_id' => [
                'nullable',
                'integer',
                Rule::exists('tenant_groups', 'id')->where('tenant_id', $tenant->id),
            ],
        ]);

        $steamId = isset($data['steam_id']) && $data['steam_id'] !== '' ? trim($data['steam_id']) : null;
        $avatarUrl = isset($data['avatar_url']) && $data['avatar_url'] !== '' ? trim($data['avatar_url']) : null;

        $player->fill([
            'display_name' => $data['display_name'],
            'steam_id' => $steamId,
            'avatar_url' => $avatarUrl,
        ]);

        $player->save();

        if (array_key_exists('group_id', $data)) {
            $groupId = $data['group_id'];

            if ($groupId) {
                $player->groups()->sync([$groupId]);
            } else {
                $player->groups()->detach();
            }
        }

        return Redirect::route('tenants.permissions.players.edit', ['tenant' => $tenant, 'player' => $player])
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
