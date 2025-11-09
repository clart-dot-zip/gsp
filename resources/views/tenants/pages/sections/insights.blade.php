@php
    $cards = [
        ['title' => 'Monthly Active Users', 'value' => number_format(random_int(420, 960)), 'icon' => 'fa-users', 'color' => 'bg-primary'],
        ['title' => 'Support Tickets', 'value' => number_format(random_int(4, 32)), 'icon' => 'fa-life-ring', 'color' => 'bg-warning'],
        ['title' => 'Avg. Response Time', 'value' => random_int(2, 6).' hrs', 'icon' => 'fa-clock', 'color' => 'bg-success'],
    ];
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
    <div class="col-xl-8">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Usage Trends</h3>
            </div>
            <div class="card-body">
                <p class="text-muted">Visualizations can live here. For now, this is a placeholder illustrating where charts would sit inside the tenant workspace.</p>
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><i class="fas fa-chart-line text-primary mr-2"></i> 18% growth in active users over the past month</li>
                    <li class="mb-2"><i class="fas fa-play text-success mr-2"></i> Automation run rate steady at 96%</li>
                    <li class="mb-0"><i class="fas fa-bolt text-warning mr-2"></i> Peak usage window: Weekdays, 9 AM – 4 PM</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Operational Signals</h3>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        SLA Compliance
                        <span class="badge badge-success">99.1%</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Feature Adoption
                        <span class="badge badge-primary">High</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Support Volume
                        <span class="badge badge-warning">Medium</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">Recommendations</h3>
            </div>
            <div class="card-body">
                <p class="mb-0">Use these insights to guide conversations with {{ $tenant->displayName() }}:</p>
                <ol class="mt-3 mb-0">
                    <li>Schedule a joint working session to review automation guardrails.</li>
                    <li>Share adoption best practices to maintain the active user growth trend.</li>
                    <li>Confirm quarterly business review agenda and attendees.</li>
                </ol>
            </div>
        </div>
    </div>
</div>
