@php
    $canManagePermissions = Auth::user() && Auth::user()->hasPermission('manage_tenant_permissions');
@endphp

<div class="row">
    @if ($canManagePermissions)
        <div class="col-md-4 col-lg-3 mb-4">
            <div class="card h-100 card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title mb-0">Create group</h3>
                </div>
                <form method="POST" action="{{ route('tenants.permissions.groups.store', ['tenant' => $tenant]) }}">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="group-create-name">Name</label>
                            <input type="text" id="group-create-name" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="group-create-description">Description <span class="text-muted">(optional)</span></label>
                            <input type="text" id="group-create-description" name="description" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="group-create-parents">Inherits from <span class="text-muted">(optional)</span></label>
                            <select id="group-create-parents" name="parent_ids[]" class="form-control" multiple>
                                @foreach ($permissionGroups as $existingGroup)
                                    <option value="{{ $existingGroup->id }}">{{ $existingGroup->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="group-create-external">External reference <span class="text-muted">(optional)</span></label>
                            <input type="text" id="group-create-external" name="external_reference" class="form-control" placeholder="ULX or ServerGuard identifier">
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-success">Create</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @forelse ($permissionGroups as $group)
        <div class="col-md-4 col-lg-3 mb-4">
            <div class="card h-100 card-outline card-primary position-relative">
                <div class="card-header border-0 pb-0">
                    <h3 class="card-title mb-1">{{ $group->name }}</h3>
                    <p class="text-muted text-sm mb-0">slug: {{ $group->slug }}</p>
                </div>
                <div class="card-body">
                    <p class="mb-3 text-muted">{{ $group->description ?? 'No description provided yet.' }}</p>
                    <dl class="mb-0">
                        <div class="d-flex justify-content-between">
                            <dt class="text-muted">Permissions</dt>
                            <dd class="mb-0">{{ $group->permissions_count ?? 0 }}</dd>
                        </div>
                        <div class="d-flex justify-content-between">
                            <dt class="text-muted">Players</dt>
                            <dd class="mb-0">{{ $group->players_count ?? 0 }}</dd>
                        </div>
                        <div class="d-flex justify-content-between">
                            <dt class="text-muted">Inherits</dt>
                            <dd class="mb-0">
                                @if ($group->parents->isEmpty())
                                    <span class="text-muted">None</span>
                                @else
                                    {{ $group->parents->pluck('name')->take(2)->join(', ') }}@if ($group->parents->count() > 2){{ ' +' . ($group->parents->count() - 2) }}@endif
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
                <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center">
                    <span class="badge badge-light">ID {{ $group->id }}</span>
                    @if ($canManagePermissions)
                        <a href="{{ route('tenants.permissions.groups.edit', ['tenant' => $tenant, 'group' => $group]) }}" class="btn btn-primary btn-sm">Manage</a>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-info mb-0">
                No tenant groups found yet. {{ $canManagePermissions ? 'Create the first group to start syncing with your Garry\'s Mod server.' : 'Check back later once an administrator provisions groups.' }}
            </div>
        </div>
    @endforelse
</div>
