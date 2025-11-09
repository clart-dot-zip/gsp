<div class="row">
    <div class="col-lg-4 col-md-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $tenant->displayName() }}</h3>
                <p>Current Tenant</p>
            </div>
            <div class="icon">
                <i class="fas fa-building"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $tenant->contact_email ? 'Connected' : 'Pending' }}</h3>
                <p>Support Alignment</p>
            </div>
            <div class="icon">
                <i class="fas fa-headset"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ now()->subMinutes(15)->diffForHumans(null, true) }}</h3>
                <p>Last Sync</p>
            </div>
            <div class="icon">
                <i class="fas fa-sync"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8">
        <div class="card card-outline card-primary">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Tenant Snapshot</h3>
                <span class="badge badge-primary">{{ strtoupper(substr($tenant->slug, 0, 3)) }}</span>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Description</dt>
                    <dd class="col-sm-8">{{ $tenant->description ?? 'Add a short description to share context with other operators.' }}</dd>

                    <dt class="col-sm-4">Primary Contact</dt>
                    <dd class="col-sm-8">{{ $tenant->contact_email ?? 'Not provided yet' }}</dd>

                    <dt class="col-sm-4">Website</dt>
                    <dd class="col-sm-8">
                        @if ($tenant->website_url)
                            <a href="{{ $tenant->website_url }}" target="_blank" rel="noopener">{{ $tenant->website_url }}</a>
                        @else
                            —
                        @endif
                    </dd>

                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        <span class="badge badge-success">Operational</span>
                        <small class="text-muted ml-2">No incidents reported</small>
                    </dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Upcoming Checks</h3>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-3"><i class="fas fa-clipboard-check text-primary mr-2"></i> Quarterly security review <span class="badge badge-light ml-2">Due in 10 days</span></li>
                    <li class="mb-3"><i class="fas fa-chart-area text-success mr-2"></i> Adoption health score update</li>
                    <li class="mb-0"><i class="fas fa-plug text-warning mr-2"></i> Integration catalogue refresh</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">Lifecycle Timeline</h3>
            </div>
            <div class="card-body">
                <div class="timeline timeline-inverse">
                    <div class="time-label">
                        <span class="bg-primary">Launch Phase</span>
                    </div>
                    <div>
                        <i class="fas fa-rocket bg-primary"></i>
                        <div class="timeline-item">
                            <h3 class="timeline-header">Tenant onboarding completed</h3>
                            <div class="timeline-body">
                                Provisioned core services and validated baseline policies for {{ $tenant->displayName() }}.
                            </div>
                        </div>
                    </div>
                    <div>
                        <i class="fas fa-cogs bg-secondary"></i>
                        <div class="timeline-item">
                            <h3 class="timeline-header">Automation enabled</h3>
                            <div class="timeline-body">
                                Scheduled health checks and KPI scorecards now available in the workspace overview.
                            </div>
                        </div>
                    </div>
                    <div>
                        <i class="fas fa-flag-checkered bg-success"></i>
                        <div class="timeline-item">
                            <h3 class="timeline-header">Next milestone</h3>
                            <div class="timeline-body">
                                Prepare executive briefing materials using the latest adoption metrics and support commitments.
                            </div>
                        </div>
                    </div>
                    <div>
                        <i class="far fa-clock bg-gray"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
