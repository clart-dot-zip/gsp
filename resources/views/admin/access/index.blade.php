<x-app-layout>
    <x-slot name="header">
        <h1 class="m-0 text-dark">Access Control</h1>
    </x-slot>

    @if ($errors->any())
        <div class="alert alert-danger">
            <p class="mb-2 font-weight-bold">Unable to complete your request:</p>
            <ul class="mb-0 pl-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <div class="row">
        <div class="col-lg-6">
            <div class="card card-outline card-primary">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">User Group Assignments</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Groups</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $user)
                                    <tr>
                                        <td>
                                            <strong>{{ $user->name }}</strong>
                                            <div class="text-muted small">{{ $user->email ?? 'No email' }}</div>
                                        </td>
                                        <td>
                                            @if ($user->groups->isEmpty())
                                                <span class="badge badge-secondary">No groups</span>
                                            @else
                                                @foreach ($user->groups as $group)
                                                    <form method="POST" action="{{ route('admin.access.users.groups.detach', [$user, $group]) }}" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="badge badge-primary border-0" onclick="return confirm('Remove this group from the user?');">
                                                            {{ $group->name }} &times;
                                                        </button>
                                                    </form>
                                                @endforeach
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <form method="POST" action="{{ route('admin.access.users.groups.attach', $user) }}" class="form-inline justify-content-end">
                                                @csrf
                                                <div class="form-group mr-2 mb-2">
                                                    <label for="attach-group-{{ $user->id }}" class="sr-only">Group</label>
                                                    <select id="attach-group-{{ $user->id }}" name="group_id" class="form-control form-control-sm">
                                                        <option value="">Select group</option>
                                                        @foreach ($groups as $groupOption)
                                                            <option value="{{ $groupOption->id }}">{{ $groupOption->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <button type="submit" class="btn btn-sm btn-primary mb-2">Add</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card card-outline card-secondary mb-4">
                <div class="card-header">
                    <h3 class="card-title mb-0">Group Permissions</h3>
                </div>
                <div class="card-body p-0">
                    <div class="accordion" id="group-permissions">
                        @foreach ($groups as $group)
                            <div class="card mb-0">
                                <div class="card-header" id="heading-{{ $group->id }}">
                                    <h3 class="card-title mb-0">
                                        <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse-{{ $group->id }}" aria-expanded="false" aria-controls="collapse-{{ $group->id }}">
                                            {{ $group->name }}
                                        </button>
                                    </h3>
                                </div>
                                <div id="collapse-{{ $group->id }}" class="collapse" data-parent="#group-permissions">
                                    <div class="card-body text-body">
                                        <form method="POST" action="{{ route('admin.access.groups.permissions.sync', $group) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="form-group">
                                                @foreach ($permissions as $permission)
                                                    <div class="custom-control custom-checkbox mb-2">
                                                        <input type="checkbox" class="custom-control-input" id="perm-{{ $group->id }}-{{ $permission->id }}" name="permissions[]" value="{{ $permission->id }}" {{ $group->permissions->contains('id', $permission->id) ? 'checked' : '' }}>
                                                        <label class="custom-control-label" for="perm-{{ $group->id }}-{{ $permission->id }}">
                                                            {{ $permission->name }}
                                                            <span class="text-muted small d-block">{{ $permission->slug }}</span>
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="text-right">
                                                <button type="submit" class="btn btn-primary btn-sm">Save Permissions</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="card card-outline card-light">
                <div class="card-header">
                    <h3 class="card-title mb-0">Create Group</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.access.groups.store') }}">
                        @csrf
                        <div class="form-group">
                            <label for="group-name">Group Name</label>
                            <input type="text" id="group-name" name="name" class="form-control" required>
                        </div>
                        <div class="text-right">
                            <button type="submit" class="btn btn-primary">Create Group</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
