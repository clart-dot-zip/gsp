@php
    $logs = $activityLogs;
@endphp

<div class="card card-outline card-warning">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Tenant Activity</h3>
        <span class="badge badge-light">{{ $logs->total() }} entries</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th class="text-nowrap">When</th>
                        <th class="text-nowrap">User</th>
                        <th>Event</th>
                        <th class="text-nowrap d-none d-lg-table-cell">Route</th>
                        <th class="text-nowrap">Method</th>
                        <th class="text-nowrap d-none d-md-table-cell">Status</th>
                        <th class="text-nowrap d-none d-md-table-cell">IP</th>
                        <th class="d-none d-xl-table-cell">Payload</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="text-nowrap align-middle">
                                {{ optional($log->created_at)->format('Y-m-d H:i:s') ?? '—' }}
                            </td>
                            <td class="align-middle">
                                {{ $log->user?->name ?? 'System' }}
                            </td>
                            <td class="align-middle">
                                <strong>{{ $log->event }}</strong>
                                <div class="small text-muted">{{ $log->path }}</div>
                            </td>
                            <td class="align-middle d-none d-lg-table-cell">
                                {{ $log->route_name ?? '—' }}
                            </td>
                            <td class="align-middle text-nowrap">
                                <span class="badge badge-secondary">{{ $log->method }}</span>
                            </td>
                            <td class="align-middle text-nowrap d-none d-md-table-cell">
                                {{ $log->status_code ?? '—' }}
                            </td>
                            <td class="align-middle text-nowrap d-none d-md-table-cell">
                                {{ $log->ip_address ?? '—' }}
                            </td>
                            <td class="align-middle d-none d-xl-table-cell">
                                @if ($log->payload)
                                    <code class="d-block text-break">
                                        {{ \Illuminate\Support\Str::limit(json_encode($log->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 120) }}
                                    </code>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <em>No activity recorded for this tenant yet. Actions will appear here as they happen.</em>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($logs->hasPages())
        <div class="card-footer">
            {{ $logs->links() }}
        </div>
    @endif
</div>
