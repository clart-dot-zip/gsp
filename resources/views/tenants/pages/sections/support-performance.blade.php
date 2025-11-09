@php
    $totals = $supportTicketStats['totals'] ?? [];
    $thisMonth = $supportTicketStats['this_month'] ?? [];
    $averageResolution = $supportTicketStats['average_resolution_hours'] ?? null;
    $resolutionRate = $supportTicketStats['resolution_rate'] ?? null;
    $trend = $supportTicketStats['trend'] ?? [];
    $topAssignees = $supportTicketStats['top_assignees'] ?? [];
@endphp

<div class="row">
    <div class="col-md-3 col-sm-6 mb-4">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $totals['open'] ?? 0 }}</h3>
                <p>Open Tickets</p>
            </div>
            <div class="icon"><i class="fas fa-lock-open"></i></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-4">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $totals['in_progress'] ?? 0 }}</h3>
                <p>In Progress</p>
            </div>
            <div class="icon"><i class="fas fa-spinner"></i></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-4">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $totals['resolved'] ?? 0 }}</h3>
                <p>Resolved</p>
            </div>
            <div class="icon"><i class="fas fa-check"></i></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-4">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3>{{ $totals['closed'] ?? 0 }}</h3>
                <p>Closed</p>
            </div>
            <div class="icon"><i class="fas fa-archive"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card card-outline card-primary h-100">
            <div class="card-header"><h3 class="card-title mb-0">This Month</h3></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-6">Tickets Created</dt>
                    <dd class="col-sm-6">{{ $thisMonth['created'] ?? 0 }}</dd>
                    <dt class="col-sm-6">Tickets Resolved</dt>
                    <dd class="col-sm-6">{{ $thisMonth['resolved'] ?? 0 }}</dd>
                    <dt class="col-sm-6">Resolution Rate</dt>
                    <dd class="col-sm-6">{{ $resolutionRate !== null ? $resolutionRate.'%' : 'N/A' }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card card-outline card-success h-100">
            <div class="card-header"><h3 class="card-title mb-0">Resolution Time</h3></div>
            <div class="card-body d-flex flex-column justify-content-center align-items-center text-center">
                @if ($averageResolution !== null)
                    <p class="display-4 mb-2">{{ $averageResolution }}</p>
                    <p class="text-muted mb-0">Average hours from open to resolution</p>
                @else
                    <p class="text-muted mb-0">Not enough resolved tickets to calculate an average.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-7 mb-4">
        <div class="card card-outline card-info h-100">
            <div class="card-header"><h3 class="card-title mb-0">Ticket Volume Trend</h3></div>
            <div class="card-body">
                @if (empty($trend))
                    <p class="text-muted mb-0">Trend data will appear once tickets have been raised.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Month</th>
                                    <th scope="col" class="text-right">Tickets</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($trend as $entry)
                                    <tr>
                                        <td>{{ $entry['month'] }}</td>
                                        <td class="text-right">{{ $entry['total'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-5 mb-4">
        <div class="card card-outline card-secondary h-100">
            <div class="card-header"><h3 class="card-title mb-0">Top Assignees</h3></div>
            <div class="card-body">
                @if (empty($topAssignees))
                    <p class="text-muted mb-0">Assignments will appear once tickets are being claimed.</p>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach ($topAssignees as $entry)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>{{ $entry['label'] }}</span>
                                <span class="badge badge-primary badge-pill">{{ $entry['tickets'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
