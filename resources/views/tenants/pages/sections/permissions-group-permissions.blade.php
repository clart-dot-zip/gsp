@php
    $canManagePermissions = Auth::user() && Auth::user()->hasPermission('manage_tenant_permissions');
@endphp

<div class="row">
    <div class="col-lg-7">
        <h2 class="h5 mb-3">Permission flags</h2>
        <div class="row">
            @if ($canManagePermissions)
                <div class="col-md-6 col-xl-4 mb-4">
                    <div class="card h-100 card-outline card-success">
                        <div class="card-header">
                            <h3 class="card-title mb-0">Add permission</h3>
                        </div>
                        <form method="POST" action="{{ route('tenants.permissions.definitions.store', ['tenant' => $tenant]) }}">
                            @csrf
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="permission-create-name">Name</label>
                                    <input type="text" id="permission-create-name" name="name" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="permission-create-description">Description <span class="text-muted">(optional)</span></label>
                                    <input type="text" id="permission-create-description" name="description" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="permission-create-external">External reference <span class="text-muted">(optional)</span></label>
                                    <input type="text" id="permission-create-external" name="external_reference" class="form-control" placeholder="Flag id in ULX/ServerGuard/etc">
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <button type="submit" class="btn btn-success">Create</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            @forelse ($permissionDefinitions as $permission)
                <div class="col-md-6 col-xl-4 mb-4">
                    <div class="card h-100 card-outline card-primary position-relative">
                        <div class="card-header border-0 pb-0">
                            <h3 class="card-title mb-1">{{ $permission->name }}</h3>
                            <p class="text-muted text-sm mb-0">slug: {{ $permission->slug }}</p>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">{{ $permission->description ?? 'No description provided yet.' }}</p>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Groups using</span>
                                <span>{{ $permission->groups_count ?? 0 }}</span>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center">
                            <span class="badge badge-light">ID {{ $permission->id }}</span>
                            @if ($canManagePermissions)
                                <a href="{{ route('tenants.permissions.definitions.edit', ['tenant' => $tenant, 'permission' => $permission]) }}" class="btn btn-primary btn-sm">Manage</a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info mb-0">No permission flags yet. {{ $canManagePermissions ? 'Create one to start mapping your in-game permissions.' : 'Ask your administrator to provision permission flags.' }}</div>
                </div>
            @endforelse
        </div>
    </div>

    <div class="col-lg-5">
        <h2 class="h5 mb-3">Groups overview</h2>
        <div class="row">
            @forelse ($permissionGroups as $group)
                <div class="col-md-6 col-lg-12 mb-4">
                    <div class="card h-100 card-outline card-info position-relative">
                        <div class="card-header border-0 pb-0">
                            <h3 class="card-title mb-1">{{ $group->name }}</h3>
                            <p class="text-muted text-sm mb-0">{{ $group->players_count ?? 0 }} player(s)</p>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Assigned flags</span>
                                <span>{{ $group->permissions_count ?? 0 }}</span>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top-0 text-right">
                            @if ($canManagePermissions)
                                <a href="{{ route('tenants.permissions.groups.edit', ['tenant' => $tenant, 'group' => $group]) }}" class="btn btn-info btn-sm">Manage assignments</a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-light border mb-0">No groups available yet. Set up groups first to manage their permission flags.</div>
                </div>
            @endforelse
        </div>
    </div>
</div>
