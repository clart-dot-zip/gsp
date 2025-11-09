@php
    $canManagePermissions = Auth::user() && Auth::user()->hasPermission('manage_tenant_permissions');
    $stats = $permissionsOverview ?? [
        'group_count' => 0,
        'permission_count' => 0,
        'player_count' => 0,
    ];
@endphp

<div class="row">
    <div class="col-lg-4 col-md-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $stats['group_count'] }}</h3>
                <p>Syncable Groups</p>
            </div>
            <div class="icon">
                <i class="fas fa-users-cog"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $stats['permission_count'] }}</h3>
                <p>Permission Flags</p>
            </div>
            <div class="icon">
                <i class="fas fa-key"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $stats['player_count'] }}</h3>
                <p>Linked Players</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-friends"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title mb-0">How syncing works</h3>
            </div>
            <div class="card-body">
                <p class="mb-3">Groups, permissions, and players are scoped to <strong>{{ $tenant->displayName() }}</strong>. The Garry's Mod connector will use these records to mirror whatever permissions system is active in-game (ULX, ServerGuard, CAMI, etc.).</p>
                <ol class="mb-0 pl-3">
                    <li>Create or import groups that match your in-game roles.</li>
                    <li>Attach permission flags to each group. Inheritance lets you build hierarchies without duplicating flags.</li>
                    <li>When the connector runs it will reconcile players and push the latest assignments to your server.</li>
                </ol>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title mb-0">Next steps</h3>
            </div>
            <div class="card-body">
                @if ($canManagePermissions)
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="fas fa-check text-success mr-2"></i><a href="{{ route('tenants.pages.show', ['page' => 'permissions_groups']) }}">Define tenant groups</a></li>
                        <li class="mb-2"><i class="fas fa-check text-success mr-2"></i><a href="{{ route('tenants.pages.show', ['page' => 'permissions_group_permissions']) }}">Assign permission flags</a></li>
                        <li><i class="fas fa-check text-success mr-2"></i><a href="{{ route('tenants.pages.show', ['page' => 'permissions_users']) }}">Review synced players</a></li>
                    </ul>
                @else
                    <p class="mb-0 text-muted">You can review the current configuration here. Ask an administrator for access to make changes.</p>
                @endif
            </div>
        </div>
    </div>
</div>
