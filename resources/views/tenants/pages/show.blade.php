<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="m-0 text-dark">{{ $pageTitle }}</h1>
            <p class="text-muted mb-0">Tenant: {{ $tenant->displayName() }}</p>
        </div>
    </x-slot>

    <div class="row">
        <div class="col-12">
            <div class="alert alert-info">
                You are viewing data scoped to <strong>{{ $tenant->displayName() }}</strong>. Select a different tenant from the top navigation to view another customer's workspace.
            </div>
        </div>
    </div>

    {{-- Sub navigation for permission pages to keep things tidy --}}
    @if (str_starts_with($pageKey, 'permissions_'))
        <div class="row mb-3">
            <div class="col-12">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link {{ $pageKey === 'permissions_overview' ? 'active' : '' }}" href="{{ route('tenants.pages.show', ['page' => 'permissions_overview']) }}">Overview</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $pageKey === 'permissions_groups' ? 'active' : '' }}" href="{{ route('tenants.pages.show', ['page' => 'permissions_groups']) }}">Groups</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $pageKey === 'permissions_definitions' ? 'active' : '' }}" href="{{ route('tenants.pages.show', ['page' => 'permissions_definitions']) }}">Definitions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $pageKey === 'permissions_users' ? 'active' : '' }}" href="{{ route('tenants.pages.show', ['page' => 'permissions_users']) }}">Players</a>
                    </li>
                </ul>
            </div>
        </div>
    @endif

    @switch($pageKey)
        @case('contacts')
            @include('tenants.pages.sections.contacts', [
                'tenant' => $tenant,
                'contacts' => $contacts ?? collect(),
            ])
            @break

        @case('players')
        @case('bans')
        @case('blacklists')
        @case('warnings')
        @case('logs')
            @include('tenants.pages.sections.placeholder', [
                'tenant' => $tenant,
                'pageTitle' => $pageTitle,
            ])
            @break

        @case('activity_logs')
            @include('tenants.pages.sections.activity-logs', [
                'tenant' => $tenant,
                'activityLogs' => $activityLogs,
            ])
            @break

        @case('overview')
            @include('tenants.pages.sections.overview', ['tenant' => $tenant])
            @break

        @case('permissions_overview')
            @include('tenants.pages.sections.permissions-overview', [
                'tenant' => $tenant,
                'permissionsOverview' => $permissionsOverview,
            ])
            @break

        @case('permissions_groups')
            @include('tenants.pages.sections.permissions-groups', [
                'tenant' => $tenant,
                'permissionGroups' => $permissionGroups,
            ])
            @break

        @case('permissions_group_permissions')
            @include('tenants.pages.sections.permissions-group-permissions', [
                'tenant' => $tenant,
                'permissionGroups' => $permissionGroups,
                'permissionDefinitions' => $permissionDefinitions,
            ])
            @break

        @case('permissions_definitions')
            @include('tenants.pages.sections.permissions-definitions', [
                'tenant' => $tenant,
                'permissionDefinitions' => $permissionDefinitions,
            ])
            @break

        @case('permissions_users')
            @include('tenants.pages.sections.permissions-users', [
                'tenant' => $tenant,
                'tenantPlayers' => $tenantPlayers,
                'permissionGroups' => $permissionGroups,
            ])
            @break

        @default
            @include('tenants.pages.sections.placeholder', [
                'tenant' => $tenant,
                'pageTitle' => $pageTitle,
            ])
    @endswitch
</x-app-layout>
