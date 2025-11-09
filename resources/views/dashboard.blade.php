<x-app-layout>
    <x-slot name="header">
        <h1 class="m-0 text-dark">{{ __('Dashboard') }}</h1>
    </x-slot>

    @php
        $currentUser = Auth::user();
    @endphp

    @if (empty($currentTenant))
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-circle mr-2"></i>
            No tenant selected yet. Use the tenant picker in the top navigation or add a tenant from the admin menu to see contextual data here.
        </div>
    @endif

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ optional($currentTenant)->displayName() ?? 'None' }}</h3>
                    <p>Active Tenant</p>
                </div>
                <div class="icon">
                    <i class="fas fa-building"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ now()->format('M') }}</h3>
                    <p>Current Month</p>
                </div>
                <div class="icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ Auth::user()->name }}</h3>
                    <p>Signed In User</p>
                </div>
                <div class="icon">
                    <i class="fas fa-user-check"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ now()->format('H:i') }}</h3>
                    <p>Server Time</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ config('app.env') }}</h3>
                    <p>Environment</p>
                </div>
                <div class="icon">
                    <i class="fas fa-server"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-0 d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">Tenant Summary</h3>
                    @if ($currentTenant)
                        <span class="badge badge-primary">{{ $currentTenant->displayName() }}</span>
                    @endif
                </div>
                <div class="card-body">
                    @if ($currentTenant)
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Description</dt>
                            <dd class="col-sm-8">{{ $currentTenant->description ?? '—' }}</dd>

                            <dt class="col-sm-4">Contact Email</dt>
                            <dd class="col-sm-8">{{ $currentTenant->contact_email ?? '—' }}</dd>

                            <dt class="col-sm-4">Website</dt>
                            <dd class="col-sm-8">
                                @if ($currentTenant->website_url)
                                    <a href="{{ $currentTenant->website_url }}" target="_blank" rel="noopener">{{ $currentTenant->website_url }}</a>
                                @else
                                    —
                                @endif
                            </dd>
                        </dl>
                    @else
                        <p class="mb-0">Add or select a tenant to see its details here.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <a href="{{ route('profile.edit') }}" class="d-flex align-items-center">
                                <i class="fas fa-user-cog mr-2"></i> {{ __('Manage Profile') }}
                            </a>
                        </li>
                        <li class="list-group-item">
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="btn btn-link p-0 text-danger">
                                    <i class="fas fa-sign-out-alt mr-2"></i>{{ __('Sign Out') }}
                                </button>
                            </form>
                        </li>
                        @if ($currentUser && $currentUser->hasPermission('manage_tenants'))
                            <li class="list-group-item">
                                <a href="{{ route('tenants.manage') }}" class="d-flex align-items-center">
                                    <i class="fas fa-tools mr-2"></i> {{ __('Manage Tenants') }}
                                </a>
                            </li>
                        @endif
                        @if ($currentUser && $currentUser->hasPermission('manage_contacts'))
                            <li class="list-group-item">
                                @if ($currentTenant)
                                    <a href="{{ route('tenants.contacts.index', $currentTenant) }}" class="d-flex align-items-center">
                                        <i class="fas fa-address-book mr-2"></i> {{ __('Manage Contacts') }}
                                    </a>
                                @else
                                    <span class="text-muted d-flex align-items-center">
                                        <i class="fas fa-address-book mr-2"></i> {{ __('Select a tenant to manage contacts') }}
                                    </span>
                                @endif
                            </li>
                        @endif
                        @if ($currentUser && $currentUser->hasPermission('manage_access'))
                            <li class="list-group-item">
                                <a href="{{ route('admin.access.index') }}" class="d-flex align-items-center">
                                    <i class="fas fa-user-shield mr-2"></i> {{ __('Access Control') }}
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
