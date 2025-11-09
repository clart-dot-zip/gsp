@php
    $canManagePermissions = Auth::user() && Auth::user()->hasPermission('manage_tenant_permissions');
@endphp

@if ($canManagePermissions)
    <div class="row">
        <div class="col-lg-6">
            <div class="card card-outline card-success mb-3">
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
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-success">Create player</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

<div class="row">
    <div class="col-12">
        @if ($tenantPlayers->isEmpty())
            <div class="alert alert-info">No players have been synced yet. {{ $canManagePermissions ? 'Players will appear here after the Garry\'s Mod connector sends its first sync payload.' : 'Check back later once the connector populates this list.' }}</div>
        @else
            @foreach ($tenantPlayers as $player)
                <div class="card card-outline card-primary mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title mb-0">{{ $player->display_name }}</h3>
                            <p class="text-sm text-muted mb-0">Steam ID: {{ $player->steam_id ?? 'Not linked yet' }}</p>
                        </div>
                        <span class="badge badge-secondary">Groups: {{ $player->groups->count() }}</span>
                    </div>
                    <div class="card-body">
                        @if ($player->avatar_url)
                            <div class="mb-3">
                                <img src="{{ $player->avatar_url }}" alt="Avatar" class="img-thumbnail" style="max-height: 80px;">
                            </div>
                        @endif

                        @if ($canManagePermissions)
                            <form method="POST" action="{{ route('tenants.permissions.players.update', ['tenant' => $tenant, 'player' => $player]) }}" class="mb-3">
                                @csrf
                                @method('PUT')
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="player-name-{{ $player->id }}">Display name</label>
                                        <input type="text" id="player-name-{{ $player->id }}" name="display_name" class="form-control" value="{{ $player->display_name }}" required>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="player-steam-{{ $player->id }}">Steam ID</label>
                                        <input type="text" id="player-steam-{{ $player->id }}" name="steam_id" class="form-control" value="{{ $player->steam_id }}">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="player-avatar-{{ $player->id }}">Avatar URL</label>
                                        <input type="url" id="player-avatar-{{ $player->id }}" name="avatar_url" class="form-control" value="{{ $player->avatar_url }}">
                                    </div>
                                </div>
                                <div class="text-right">
                                    <button type="submit" class="btn btn-primary">Save player</button>
                                </div>
                            </form>
                        @endif

                        <div class="mb-3">
                            <h5>Groups</h5>
                            @if ($player->groups->isEmpty())
                                <p class="text-muted mb-2">No groups assigned yet.</p>
                            @else
                                <div class="d-flex flex-wrap">
                                    @foreach ($player->groups as $group)
                                        <div class="badge badge-info mr-2 mb-2 d-flex align-items-center">
                                            <span>{{ $group->name }}</span>
                                            @if ($canManagePermissions)
                                                <form method="POST" action="{{ route('tenants.permissions.players.groups.detach', ['tenant' => $tenant, 'player' => $player, 'group' => $group]) }}" class="ml-2" onsubmit="return confirm('Remove {{ $group->name }} from {{ $player->display_name }}?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-link text-white p-0"><i class="fas fa-times"></i></button>
                                                </form>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        @if ($canManagePermissions && $permissionGroups->isNotEmpty())
                            @php
                                $availableGroups = $permissionGroups->reject(fn ($group) => $player->groups->contains('id', $group->id));
                            @endphp
                            <form method="POST" action="{{ route('tenants.permissions.players.groups.attach', ['tenant' => $tenant, 'player' => $player]) }}" class="form-inline">
                                @csrf
                                <div class="form-group mb-2 mr-2">
                                    <label class="sr-only" for="player-group-select-{{ $player->id }}">Group</label>
                                    <select id="player-group-select-{{ $player->id }}" name="group_id" class="form-control" {{ $availableGroups->isEmpty() ? 'disabled' : '' }}>
                                        @foreach ($availableGroups as $groupOption)
                                            <option value="{{ $groupOption->id }}">{{ $groupOption->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-info mb-2" {{ $availableGroups->isEmpty() ? 'disabled' : '' }}>Assign group</button>
                            </form>
                        @endif
                    </div>
                    @if ($canManagePermissions)
                        <div class="card-footer text-right">
                            <form method="POST" action="{{ route('tenants.permissions.players.destroy', ['tenant' => $tenant, 'player' => $player]) }}" onsubmit="return confirm('Remove player {{ $player->display_name }} from this tenant?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Delete player</button>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        @endif
    </div>
</div>
