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

        @case('overview')
            @include('tenants.pages.sections.overview', ['tenant' => $tenant])
            @break

        @default
            @include('tenants.pages.sections.placeholder', [
                'tenant' => $tenant,
                'pageTitle' => $pageTitle,
            ])
    @endswitch
</x-app-layout>
