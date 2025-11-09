@php
    $canManagePermissions = Auth::user() && Auth::user()->hasPermission('manage_tenant_permissions');
@endphp

@if ($canManagePermissions)
    <div class="row">
        <div class="col-lg-6">
            <div class="card card-outline card-success">
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
                            <small class="form-text text-muted">Inherited groups share their permission flags with this group.</small>
                        </div>
                        <div class="form-group">
                            <label for="group-create-external">External reference <span class="text-muted">(optional)</span></label>
                            <input type="text" id="group-create-external" name="external_reference" class="form-control" placeholder="ULX or ServerGuard identifier">
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-success">Create group</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

<div class="row">
    <div class="col-12">
        @if ($permissionGroups->isEmpty())
            <div class="alert alert-info mb-0">
                No tenant groups found yet. {{ $canManagePermissions ? 'Create the first group to start syncing with your Garry\'s Mod server.' : 'Check back later once an administrator provisions groups.' }}
            </div>
        @else
            @foreach ($permissionGroups as $group)
                <div class="card card-outline card-primary mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">{{ $group->name }}</h3>
                        <span class="badge badge-secondary">slug: {{ $group->slug }}</span>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">{{ $group->description ?? 'No description provided.' }}</p>
                        <ul class="list-inline mb-3">
                            <li class="list-inline-item"><strong>Permissions:</strong> {{ $group->permissions->count() }}</li>
                            <li class="list-inline-item">
                                <strong>Inherits:</strong>
                                @if ($group->parents->isEmpty())
                                    <span class="text-muted">None</span>
                                @else
                                    {{ $group->parents->pluck('name')->join(', ') }}
                                @endif
                            </li>
                            <li class="list-inline-item">
                                <strong>Used by players:</strong> {{ $group->players_count ?? 0 }}
                            </li>
                        </ul>

                        @if ($canManagePermissions)
                            <form method="POST" action="{{ route('tenants.permissions.groups.update', ['tenant' => $tenant, 'group' => $group]) }}" class="mb-3">
                                @csrf
                                @method('PUT')
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="group-name-{{ $group->id }}">Name</label>
                                        <input type="text" id="group-name-{{ $group->id }}" name="name" class="form-control" value="{{ $group->name }}" required>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="group-external-{{ $group->id }}">External reference</label>
                                        <input type="text" id="group-external-{{ $group->id }}" name="external_reference" class="form-control" value="{{ $group->external_reference }}">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="group-description-{{ $group->id }}">Description</label>
                                    <input type="text" id="group-description-{{ $group->id }}" name="description" class="form-control" value="{{ $group->description }}">
                                </div>
                                <div class="form-group">
                                    <label for="group-parents-{{ $group->id }}">Inherits from</label>
                                    <select id="group-parents-{{ $group->id }}" name="parent_ids[]" class="form-control" multiple>
                                        @foreach ($permissionGroups as $existingGroup)
                                            @continue($existingGroup->id === $group->id)
                                            <option value="{{ $existingGroup->id }}" {{ $group->parents->contains('id', $existingGroup->id) ? 'selected' : '' }}>
                                                {{ $existingGroup->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">Save changes</button>
                                </div>
                            </form>
                            <form method="POST" action="{{ route('tenants.permissions.groups.destroy', ['tenant' => $tenant, 'group' => $group]) }}" onsubmit="return confirm('Delete {{ $group->name }}?');" class="text-right">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Delete group</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
