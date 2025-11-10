<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-3">
            <div>
                <h1 class="m-0">Edit Ban</h1>
                <p class="text-muted mb-0">Tenant: {{ $tenant->displayName() }} &middot; Player: {{ $ban->player_name }}</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('tenants.pages.show', ['page' => 'bans']) }}" class="btn btn-outline-secondary">Back to Bans</a>
                <form method="POST" action="{{ route('tenants.bans.destroy', [$tenant, $ban]) }}" onsubmit="return confirm('Unban this player? This will remove the record.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">Unban</button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <form method="POST" action="{{ route('tenants.bans.update', [$tenant, $ban]) }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Player</label>
                            <div class="form-control-plaintext fw-semibold">{{ $ban->player_name }}</div>
                            @if($ban->player_steam_id)
                                <div class="text-muted small">Steam ID: {{ $ban->player_steam_id }}</div>
                            @endif
                        </div>
                        <div class="mb-3">
                            <label for="banned_at" class="form-label">Ban Time</label>
                            <input type="datetime-local" id="banned_at" name="banned_at" class="form-control" value="{{ old('banned_at', $bannedAtValue) }}">
                            <div class="form-text">Adjust when the ban was issued. Leave blank to clear the timestamp.</div>
                            @error('banned_at')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="length_code" class="form-label">Ban Length<span class="text-danger">*</span></label>
                            <input type="text" id="length_code" name="length_code" class="form-control" value="{{ old('length_code', $ban->length_code) }}" placeholder="e.g. 1d, 12h, 30m or 0 for permanent">
                            <div class="form-text">Use <code>0</code> for permanent bans or provide a value like <code>30m</code>, <code>4h</code>, <code>7d</code>, <code>1w</code>, <code>1y</code>.</div>
                            @error('length_code')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="reason" class="form-label">Public Reason<span class="text-danger">*</span></label>
                            <textarea id="reason" name="reason" class="form-control" rows="3" required>{{ old('reason', $ban->reason) }}</textarea>
                            <div class="form-text">Visible to all players.</div>
                            @error('reason')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        @if($canViewAdminReason)
                            <div class="mb-3">
                                <label for="admin_reason" class="form-label">Admin Notes</label>
                                <textarea id="admin_reason" name="admin_reason" class="form-control" rows="3">{{ old('admin_reason', $ban->admin_reason) }}</textarea>
                                <div class="form-text">Restricted to tenant contacts and staff with elevated access.</div>
                                @error('admin_reason')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif
                    </div>
                    <div class="card-footer d-flex justify-content-end gap-2">
                        <a href="{{ route('tenants.pages.show', ['page' => 'bans']) }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
