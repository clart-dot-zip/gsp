@php
    $canManagePermissions = Auth::user() && Auth::user()->hasPermission('manage_tenant_permissions');
@endphp

<div class="row">
    <div class="col-12">
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
