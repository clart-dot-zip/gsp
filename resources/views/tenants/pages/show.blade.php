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

    @php
        $cards = match ($pageKey) {
            'insights' => [
                ['title' => 'Monthly Active Users', 'value' => number_format(random_int(420, 960)), 'icon' => 'fa-users', 'color' => 'bg-primary'],
                ['title' => 'Support Tickets', 'value' => number_format(random_int(4, 32)), 'icon' => 'fa-life-ring', 'color' => 'bg-warning'],
                ['title' => 'Avg. Response Time', 'value' => random_int(2, 6).' hrs', 'icon' => 'fa-clock', 'color' => 'bg-success'],
            ],
            'contacts' => [
                ['title' => 'Primary Contact', 'value' => $tenant->contact_email ?? 'Not set', 'icon' => 'fa-id-badge', 'color' => 'bg-teal'],
                ['title' => 'Website', 'value' => $tenant->website_url ?? 'Not provided', 'icon' => 'fa-globe', 'color' => 'bg-info'],
                ['title' => 'Status', 'value' => 'Operational', 'icon' => 'fa-signal', 'color' => 'bg-success'],
            ],
            default => [
                ['title' => 'Plan', 'value' => 'Managed', 'icon' => 'fa-briefcase', 'color' => 'bg-secondary'],
                ['title' => 'Last Sync', 'value' => now()->subMinutes(15)->format('M d, Y H:i'), 'icon' => 'fa-sync', 'color' => 'bg-primary'],
                ['title' => 'Region', 'value' => strtoupper(substr($tenant->slug, 0, 3)), 'icon' => 'fa-map-marker-alt', 'color' => 'bg-warning'],
            ],
        };
    @endphp

    <div class="row">
        @foreach($cards as $card)
            <div class="col-lg-4 col-md-6">
                <div class="info-box mb-4">
                    <span class="info-box-icon {{ $card['color'] }} elevation-1"><i class="fas {{ $card['icon'] }}"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ $card['title'] }}</span>
                        <span class="info-box-number">{{ $card['value'] }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Tenant Details</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Name</dt>
                        <dd class="col-sm-8">{{ $tenant->displayName() }}</dd>

                        <dt class="col-sm-4">Slug</dt>
                        <dd class="col-sm-8">{{ $tenant->slug }}</dd>

                        <dt class="col-sm-4">Description</dt>
                        <dd class="col-sm-8">{{ $tenant->description ?? '—' }}</dd>

                        <dt class="col-sm-4">Contact Email</dt>
                        <dd class="col-sm-8">{{ $tenant->contact_email ?? '—' }}</dd>

                        <dt class="col-sm-4">Website</dt>
                        <dd class="col-sm-8">
                            @if ($tenant->website_url)
                                <a href="{{ $tenant->website_url }}" target="_blank" rel="noopener">{{ $tenant->website_url }}</a>
                            @else
                                —
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Next Actions</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">These sample actions illustrate how you can attach workflow shortcuts per tenant.</p>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="fas fa-shield-alt text-success mr-2"></i> Review security baselines</li>
                        <li class="mb-2"><i class="fas fa-chart-line text-info mr-2"></i> Analyze usage insights</li>
                        <li class="mb-2"><i class="fas fa-life-ring text-warning mr-2"></i> Check support commitments</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
