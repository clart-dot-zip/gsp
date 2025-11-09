<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div>
                <h1 class="m-0 text-dark">Manage permission: {{ $permission->name }}</h1>
                <p class="text-muted mb-0">Tenant: {{ $tenant->displayName() }}</p>
            </div>
            <div class="mt-2 mt-md-0">
                <a href="{{ route('tenants.pages.show', ['page' => 'permissions_group_permissions']) }}" class="btn btn-outline-secondary">Back to permissions</a>
            </div>
        </div>
    </x-slot>

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-primary mb-4">
                <div class="card-header">
                    <h2 class="card-title h5 mb-0">Permission details</h2>
                </div>
                <form method="POST" action="{{ route('tenants.permissions.definitions.update', ['tenant' => $tenant, 'permission' => $permission]) }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <label for="permission-name">Name</label>
                            <input type="text" id="permission-name" name="name" class="form-control" value="{{ old('name', $permission->name) }}" required>
                        </div>
                        <div class="form-group">
                            <label for="permission-description">Description <span class="text-muted">(optional)</span></label>
                            <input type="text" id="permission-description" name="description" class="form-control" value="{{ old('description', $permission->description) }}">
                        </div>
                        <div class="form-group">
                            <label for="permission-external">External reference <span class="text-muted">(optional)</span></label>
                            <input type="text" id="permission-external" name="external_reference" class="form-control" value="{{ old('external_reference', $permission->external_reference) }}" placeholder="Flag id in ULX/ServerGuard/etc">
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
                            <dt class="text-muted">Slug</dt>
                            <dd class="mb-0">{{ $permission->slug }}</dd>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <dt class="text-muted">Groups using</dt>
                            <dd class="mb-0">{{ $permission->groups->count() }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="card card-outline card-secondary mb-4">
                <div class="card-header">
                    <h2 class="card-title h5 mb-0">Groups with this permission</h2>
                </div>
                <div class="card-body">
                    @if ($permission->groups->isEmpty())
                        <p class="text-muted mb-0">No groups are currently linked to this permission.</p>
                    @else
                        <ul class="list-unstyled mb-0">
                            @foreach ($permission->groups as $group)
                                <li class="mb-1">{{ $group->name }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="card card-outline card-danger">
                <div class="card-header">
                    <h2 class="card-title h5 mb-0">Danger zone</h2>
                </div>
                <div class="card-body">
                    <p class="text-muted">Deleting this permission automatically removes it from any groups currently using it.</p>
                    <form method="POST" action="{{ route('tenants.permissions.definitions.destroy', ['tenant' => $tenant, 'permission' => $permission]) }}" onsubmit="return confirm('Delete permission {{ $permission->name }}? This action cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-block">Delete permission</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
