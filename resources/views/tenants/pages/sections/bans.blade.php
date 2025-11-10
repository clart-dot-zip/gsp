@php
    $searchValue = $banFilters['search'] ?? '';
    $canManageBans = $banPermissions['can_manage'] ?? false;
    $canViewAdminReason = $banPermissions['can_view_admin_reason'] ?? false;
@endphp

<div class="card">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-3 mb-4">
            <div>
                <h5 class="card-title mb-1">Bans</h5>
                <p class="text-muted mb-0">View historical bans recorded for this tenant.</p>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <form method="GET" action="{{ route('tenants.pages.show', ['page' => 'bans']) }}" class="d-flex" role="search">
                    <input type="search" name="search" value="{{ $searchValue }}" class="form-control" placeholder="Search players or reasons">
                    <button type="submit" class="btn btn-secondary ms-2">Search</button>
                    @if($searchValue !== '')
                        <a href="{{ route('tenants.pages.show', ['page' => 'bans']) }}" class="btn btn-link text-decoration-none ms-2">Clear</a>
                    @endif
                </form>
                @if($canManageBans)
                    <a href="{{ route('tenants.bans.create', $tenant) }}" class="btn btn-primary">
                        <i class="fas fa-ban me-1"></i>
                        Add Ban
                    </a>
                @endif
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Player</th>
                        <th scope="col">Steam ID</th>
                        <th scope="col">Length</th>
                        <th scope="col">Reason</th>
                        <th scope="col">Time Banned</th>
                        <th scope="col">Banning Admin</th>
                        @if($canViewAdminReason)
                            <th scope="col">Admin Notes</th>
                        @endif
                        @if($canManageBans)
                            <th scope="col" class="text-end">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($tenantBans as $ban)
                        <tr>
                            <td>{{ $ban->id }}</td>
                            <td>{{ $ban->player_name }}</td>
                            <td>{{ $ban->player_steam_id ?? 'Unknown' }}</td>
                            <td>{{ $ban->lengthLabel() }}</td>
                            <td>{{ $ban->reason }}</td>
                            <td>
                                @php
                                    $timestamp = $ban->banned_at ?? $ban->created_at;
                                @endphp
                                @if($timestamp)
                                    {{ $timestamp->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                    <div class="text-muted small">{{ $timestamp->diffForHumans() }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $ban->banningAdminLabel() }}</td>
                            @if($canViewAdminReason)
                                <td>{{ $ban->admin_reason ?? '—' }}</td>
                            @endif
                            @if($canManageBans)
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('tenants.bans.edit', [$tenant, $ban]) }}" class="btn btn-sm btn-outline-secondary me-2">Edit</a>
                                    <form method="POST" action="{{ route('tenants.bans.destroy', [$tenant, $ban]) }}" class="d-inline" onsubmit="return confirm('Unban this player? This will remove the record.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Unban</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ ($canViewAdminReason ? 8 : 7) + ($canManageBans ? 1 : 0) }}" class="text-center text-muted">
                                No bans recorded for this tenant yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tenantBans instanceof \Illuminate\Contracts\Pagination\Paginator)
            <div class="mt-3">
                {{ $tenantBans->links() }}
            </div>
        @endif
    </div>
</div>
