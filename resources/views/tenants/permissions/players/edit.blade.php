<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div>
                <h1 class="m-0 text-dark">Manage player: {{ $player->display_name }}</h1>
                <p class="text-muted mb-0">Tenant: {{ $tenant->displayName() }}</p>
            </div>
            <div class="mt-2 mt-md-0">
                <a href="{{ route('tenants.pages.show', ['page' => 'permissions_users']) }}" class="btn btn-outline-secondary">Back to players</a>
            </div>
        </div>
    </x-slot>

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-primary mb-4">
                <div class="card-header">
                    <h2 class="card-title h5 mb-0">Player details</h2>
                </div>
                <form method="POST" action="{{ route('tenants.permissions.players.update', ['tenant' => $tenant, 'player' => $player]) }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="player-name">Display name</label>
                                <input type="text" id="player-name" name="display_name" class="form-control" value="{{ old('display_name', $player->display_name) }}" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="player-steam">Steam ID <span class="text-muted">(optional)</span></label>
                                <input type="text" id="player-steam" name="steam_id" class="form-control" value="{{ old('steam_id', $player->steam_id) }}" placeholder="STEAM_0:1:XXXXXX or 7656119...">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-8">
                                <label for="player-avatar">Avatar URL <span class="text-muted">(optional)</span></label>
                                <input type="url" id="player-avatar" name="avatar_url" class="form-control" value="{{ old('avatar_url', $player->avatar_url) }}" placeholder="https://...">
                            </div>
                            <div class="form-group col-md-4 d-flex align-items-end">
                                @if ($player->avatar_url)
                                    <img src="{{ $player->avatar_url }}" alt="Avatar" class="img-thumbnail w-100" style="max-height: 120px;">
                                @else
                                    <span class="text-muted">No avatar</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="player-group">Group assignment</label>
                            <select id="player-group" name="group_id" class="form-control">
                                <option value="">No group</option>
                                @php $currentGroupId = optional($player->groups->first())->id; @endphp
                                @foreach ($groupOptions as $groupOption)
                                    <option value="{{ $groupOption->id }}" {{ (string) old('group_id', $currentGroupId) === (string) $groupOption->id ? 'selected' : '' }}>{{ $groupOption->name }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">A player can be in at most one group at a time.</small>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-outline card-light mb-4">
                <div class="card-header">
                    <h2 class="card-title h5 mb-0">Summary</h2>
                </div>
                <div class="card-body">
                    <dl class="mb-0">
                        <div class="d-flex justify-content-between mb-2">
                            <dt class="text-muted">Player ID</dt>
                            <dd class="mb-0">{{ $player->id }}</dd>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <dt class="text-muted">Steam ID</dt>
                            <dd class="mb-0">{{ $player->steam_id ?? 'Not linked' }}</dd>
                        </div>
                        <div class="d-flex justify-content-between">
                            <dt class="text-muted">Group</dt>
                            <dd class="mb-0">{{ optional($player->groups->first())->name ?? 'None' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="card card-outline card-danger">
                <div class="card-header">
                    <h2 class="card-title h5 mb-0">Danger zone</h2>
                </div>
                <div class="card-body">
                    <p class="text-muted">Deleting this player removes their access for this tenant.</p>
                    <form method="POST" action="{{ route('tenants.permissions.players.destroy', ['tenant' => $tenant, 'player' => $player]) }}" onsubmit="return confirm('Remove player {{ $player->display_name }} from this tenant? This action cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-block">Delete player</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
