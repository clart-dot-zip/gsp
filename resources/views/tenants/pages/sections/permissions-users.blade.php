@php
    $canManagePermissions = Auth::user() && Auth::user()->hasPermission('manage_tenant_permissions');
@endphp

<div class="row">
    @if ($canManagePermissions)
        <div class="col-md-4 col-lg-3 mb-4">
            <div class="card h-100 card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title mb-0">Add player</h3>
                </div>
                <form method="POST" action="{{ route('tenants.permissions.players.store', ['tenant' => $tenant]) }}">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="player-create-name">Display name</label>
                            <input type="text" id="player-create-name" name="display_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="player-create-steam">Steam ID <span class="text-muted">(optional)</span></label>
                            <input type="text" id="player-create-steam" name="steam_id" class="form-control" placeholder="STEAM_0:1:XXXXXX or 7656119...">
                        </div>
                        <div class="form-group">
                            <label for="player-create-avatar">Avatar URL <span class="text-muted">(optional)</span></label>
                            <input type="url" id="player-create-avatar" name="avatar_url" class="form-control" placeholder="https://...">
                        </div>
                        @if ($permissionGroups->isNotEmpty())
                            <div class="form-group">
                                <label for="player-create-group">Group <span class="text-muted">(optional)</span></label>
                                <select id="player-create-group" name="group_id" class="form-control">
                                    <option value="">No group</option>
                                    @foreach ($permissionGroups as $groupOption)
                                        <option value="{{ $groupOption->id }}">{{ $groupOption->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-success">Create</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @forelse ($tenantPlayers as $player)
        <div class="col-md-4 col-lg-3 mb-4">
            <div class="card h-100 card-outline card-primary position-relative">
                <div class="card-header border-0 pb-0">
                    <h3 class="card-title mb-1">{{ $player->display_name }}</h3>
                    <p class="text-muted text-sm mb-0">Steam: {{ $player->steam_id ?? 'Not linked yet' }}</p>
                </div>
                <div class="card-body">
                    @if ($player->avatar_url)
                        <img src="{{ $player->avatar_url }}" alt="Avatar" class="img-thumbnail mb-3" style="max-height: 80px;">
                    @endif
                    <div>
                        <span class="text-muted">Group</span>
                        <p class="mb-0">
                            @if ($player->groups->isEmpty())
                                <span class="text-muted">None assigned</span>
                            @else
                                {{ $player->groups->first()->name }}
                            @endif
                        </p>
                    </div>
                </div>
                <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center">
                    <span class="badge badge-light">ID {{ $player->id }}</span>
                    @if ($canManagePermissions)
                        <a href="{{ route('tenants.permissions.players.edit', ['tenant' => $tenant, 'player' => $player]) }}" class="btn btn-primary btn-sm">Manage</a>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-info mb-0">No players have been synced yet. {{ $canManagePermissions ? 'Players will appear here after the Garry\'s Mod connector sends its first sync payload.' : 'Check back later once the connector populates this list.' }}</div>
        </div>
    @endforelse
</div>
