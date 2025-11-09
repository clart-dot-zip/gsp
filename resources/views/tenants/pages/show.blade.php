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

        @case('permissions_users')
            @include('tenants.pages.sections.permissions-users', [
                'tenant' => $tenant,
                'tenantPlayers' => $tenantPlayers,
                'permissionGroups' => $permissionGroups,
            ])
            @break

        @case('support_tickets')
            @include('tenants.pages.sections.support-tickets', [
                'tenant' => $tenant,
                'supportTickets' => $supportTickets,
                'supportTicketFilters' => $supportTicketFilters,
                'supportTicketHighlightId' => $supportTicketHighlightId,
                'supportAgents' => $supportAgents,
                'supportContacts' => $supportContacts,
                'supportPlayers' => $supportPlayers,
                'supportTicketPermissions' => $supportTicketPermissions,
                'isPlayerSession' => $isPlayerSession ?? false,
            ])
            @break

        @case('support_performance')
            @include('tenants.pages.sections.support-performance', [
                'tenant' => $tenant,
                'supportTicketStats' => $supportTicketStats,
            ])
            @break

        @default
            @include('tenants.pages.sections.placeholder', [
                'tenant' => $tenant,
                'pageTitle' => $pageTitle,
            ])
    @endswitch
</x-app-layout>
