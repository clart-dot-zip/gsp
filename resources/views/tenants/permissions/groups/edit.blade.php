<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div>
                <h1 class="m-0 text-dark">Manage group: {{ $group->name }}</h1>
                <p class="text-muted mb-0">Tenant: {{ $tenant->displayName() }}</p>
            </div>
            <div class="mt-2 mt-md-0">
                <a href="{{ route('tenants.pages.show', ['page' => 'permissions_groups']) }}" class="btn btn-outline-secondary">Back to groups</a>
            </div>
        </div>
    </x-slot>

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-primary mb-4">
                <div class="card-header">
                    <h2 class="card-title h5 mb-0">Group details</h2>
                </div>
                <form method="POST" action="{{ route('tenants.permissions.groups.update', ['tenant' => $tenant, 'group' => $group]) }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <label for="group-name">Name</label>
                            <input type="text" id="group-name" name="name" class="form-control" value="{{ old('name', $group->name) }}" required>
                        </div>
                        <div class="form-group">
                            <label for="group-description">Description <span class="text-muted">(optional)</span></label>
                            <input type="text" id="group-description" name="description" class="form-control" value="{{ old('description', $group->description) }}">
                        </div>
                        <div class="form-group">
                            <label for="group-external">External reference <span class="text-muted">(optional)</span></label>
                            <input type="text" id="group-external" name="external_reference" class="form-control" value="{{ old('external_reference', $group->external_reference) }}" placeholder="ULX or ServerGuard identifier">
                        </div>
                        <div class="form-group">
                            <label for="group-parents">Inherits from <span class="text-muted">(optional)</span></label>
                            <select id="group-parents" name="parent_ids[]" class="form-control" multiple>
                                @foreach ($parentOptions as $parent)
                                    <option value="{{ $parent->id }}" {{ $group->parents->contains('id', $parent->id) ? 'selected' : '' }}>{{ $parent->name }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Inherited groups share their permission flags with this group.</small>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>

            <div class="card card-outline card-info mb-4">
                <div class="card-header">
                    <h2 class="card-title h5 mb-0">Permission flags</h2>
                </div>
                @if ($permissionDefinitions->isEmpty())
                    <div class="card-body">
                        <p class="text-muted mb-0">Add permission flags first to start assigning them to this group.</p>
                    </div>
                @else
                    <form method="POST" action="{{ route('tenants.permissions.groups.permissions.sync', ['tenant' => $tenant, 'group' => $group]) }}">
                        @csrf
                        <div class="card-body">
                            <div class="row">
                                @foreach ($permissionDefinitions as $permission)
                                    <div class="col-sm-6 mb-2">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="permission-{{ $permission->id }}" name="permissions[]" value="{{ $permission->id }}" {{ $group->permissions->contains('id', $permission->id) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="permission-{{ $permission->id }}">{{ $permission->name }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <button type="submit" class="btn btn-info">Update assignments</button>
                        </div>
                    </form>
                @endif
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
                            <dt class="text-muted">Slug</dt>
                            <dd class="mb-0">{{ $group->slug }}</dd>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <dt class="text-muted">Permissions</dt>
                            <dd class="mb-0">{{ $group->permissions->count() }}</dd>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <dt class="text-muted">Players</dt>
                            <dd class="mb-0">{{ $group->players->count() }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted">Inherited from</dt>
                            <dd class="mb-0">
                                @if ($group->parents->isEmpty())
                                    <span class="text-muted">None</span>
                                @else
                                    {{ $group->parents->pluck('name')->join(', ') }}
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="card card-outline card-secondary mb-4">
                <div class="card-header">
                    <h2 class="card-title h5 mb-0">Players using this group</h2>
                </div>
                <div class="card-body">
                    @if ($group->players->isEmpty())
                        <p class="text-muted mb-0">No players are assigned to this group.</p>
                    @else
                        <ul class="list-unstyled mb-0">
                            @foreach ($group->players->take(8) as $player)
                                <li class="mb-1">
                                    <strong>{{ $player->display_name }}</strong>
                                    <span class="text-muted d-block text-sm">{{ $player->steam_id ?? 'No Steam ID' }}</span>
                                </li>
                            @endforeach
                        </ul>
                        @if ($group->players->count() > 8)
                            <p class="text-muted mt-2 mb-0">and {{ $group->players->count() - 8 }} more...</p>
                        @endif
                    @endif
                </div>
            </div>

            <div class="card card-outline card-danger">
                <div class="card-header">
                    <h2 class="card-title h5 mb-0">Danger zone</h2>
                </div>
                <div class="card-body">
                    <p class="text-muted">Removing this group will also remove it from any players currently assigned.</p>
                    <form method="POST" action="{{ route('tenants.permissions.groups.destroy', ['tenant' => $tenant, 'group' => $group]) }}" onsubmit="return confirm('Delete group {{ $group->name }}? This action cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-block">Delete group</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
