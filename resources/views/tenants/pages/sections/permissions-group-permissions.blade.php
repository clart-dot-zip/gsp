@php
    $canManagePermissions = Auth::user() && Auth::user()->hasPermission('manage_tenant_permissions');
@endphp

<div class="row">
    <div class="col-lg-5">
        @if ($canManagePermissions)
            <div class="card card-outline card-success mb-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">Add permission flag</h3>
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
                        <button type="submit" class="btn btn-success">Create permission</button>
                    </div>
                </form>
            </div>
        @endif

        @if ($permissionDefinitions->isEmpty())
            <div class="alert alert-info">No permission flags yet. {{ $canManagePermissions ? 'Create one to start mapping your in-game permissions.' : 'Ask your administrator to provision permission flags.' }}</div>
        @else
            @foreach ($permissionDefinitions as $permission)
                <div class="card card-outline card-primary mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">{{ $permission->name }}</h3>
                        <span class="badge badge-secondary">slug: {{ $permission->slug }}</span>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">{{ $permission->description ?? 'No description provided.' }}</p>
                        <p class="text-muted mb-3">Assigned to {{ $permission->groups_count ?? 0 }} group(s).</p>
                        @if ($canManagePermissions)
                            <form method="POST" action="{{ route('tenants.permissions.definitions.update', ['tenant' => $tenant, 'permission' => $permission]) }}" class="mb-2">
                                @csrf
                                @method('PUT')
                                <div class="form-group">
                                    <label for="permission-name-{{ $permission->id }}">Name</label>
                                    <input type="text" id="permission-name-{{ $permission->id }}" name="name" class="form-control" value="{{ $permission->name }}" required>
                                </div>
                                <div class="form-group">
                                    <label for="permission-description-{{ $permission->id }}">Description</label>
                                    <input type="text" id="permission-description-{{ $permission->id }}" name="description" class="form-control" value="{{ $permission->description }}">
                                </div>
                                <div class="form-group">
                                    <label for="permission-external-{{ $permission->id }}">External reference</label>
                                    <input type="text" id="permission-external-{{ $permission->id }}" name="external_reference" class="form-control" value="{{ $permission->external_reference }}">
                                </div>
                                <div class="text-right">
                                    <button type="submit" class="btn btn-primary">Save changes</button>
                                </div>
                            </form>
                            <form method="POST" action="{{ route('tenants.permissions.definitions.destroy', ['tenant' => $tenant, 'permission' => $permission]) }}" onsubmit="return confirm('Delete permission {{ $permission->name }}?');" class="text-right">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Delete permission</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif
    </div>
    <div class="col-lg-7">
        @if ($permissionGroups->isEmpty())
            <div class="alert alert-light border">No groups available yet. Set up groups first to manage their permission flags.</div>
        @else
            @foreach ($permissionGroups as $group)
                <div class="card card-outline card-info mb-3">
                    <div class="card-header">
                        <h3 class="card-title mb-0">{{ $group->name }}</h3>
                    </div>
                    <div class="card-body">
                        @if ($permissionDefinitions->isEmpty())
                            <p class="text-muted mb-0">Add permission flags to start assigning them to groups.</p>
                        @elseif ($canManagePermissions)
                            <form method="POST" action="{{ route('tenants.permissions.groups.permissions.sync', ['tenant' => $tenant, 'group' => $group]) }}">
                                @csrf
                                <div class="form-group">
                                    @foreach ($permissionDefinitions as $permission)
                                        <div class="custom-control custom-checkbox mb-1">
                                            <input type="checkbox" class="custom-control-input" id="group-{{ $group->id }}-permission-{{ $permission->id }}" name="permissions[]" value="{{ $permission->id }}" {{ $group->permissions->contains('id', $permission->id) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="group-{{ $group->id }}-permission-{{ $permission->id }}">{{ $permission->name }}</label>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="text-right">
                                    <button type="submit" class="btn btn-info">Save assignments</button>
                                </div>
                            </form>
                        @else
                            <ul class="mb-0">
                                @forelse ($group->permissions as $permission)
                                    <li>{{ $permission->name }}</li>
                                @empty
                                    <li class="text-muted">No permissions assigned</li>
                                @endforelse
                            </ul>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
