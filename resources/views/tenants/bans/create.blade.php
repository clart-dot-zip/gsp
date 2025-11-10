<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-3">
            <div>
                <h1 class="m-0">Record Ban</h1>
                <p class="text-muted mb-0">Tenant: {{ $tenant->displayName() }}</p>
            </div>
            <a href="{{ route('tenants.pages.show', ['page' => 'bans']) }}" class="btn btn-outline-secondary">Back to Bans</a>
        </div>
    </x-slot>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <form method="POST" action="{{ route('tenants.bans.store', $tenant) }}">
                    @csrf
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="tenant_player_id" class="form-label">Player<span class="text-danger">*</span></label>
                            <select id="tenant_player_id" name="tenant_player_id" class="form-select" required>
                                <option value="">Select a player...</option>
                                @foreach ($players as $player)
                                    <option value="{{ $player->id }}" {{ (int) old('tenant_player_id') === $player->id ? 'selected' : '' }}>
                                        {{ $player->display_name }}
                                        @if($player->steam_id)
                                            ({{ $player->steam_id }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('tenant_player_id')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="banned_at" class="form-label">Ban Time</label>
                            <input type="datetime-local" id="banned_at" name="banned_at" class="form-control" value="{{ old('banned_at', now()->format('Y-m-d\TH:i')) }}">
                            <div class="form-text">Defaults to the current time if left blank.</div>
                            @error('banned_at')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="reason" class="form-label">Public Reason<span class="text-danger">*</span></label>
                            <textarea id="reason" name="reason" class="form-control" rows="3" required>{{ old('reason') }}</textarea>
                            <div class="form-text">Visible to all players.</div>
                            @error('reason')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        @if($canViewAdminReason)
                            <div class="mb-3">
                                <label for="admin_reason" class="form-label">Admin Notes</label>
                                <textarea id="admin_reason" name="admin_reason" class="form-control" rows="3">{{ old('admin_reason') }}</textarea>
                                <div class="form-text">Restricted to tenant contacts and staff with elevated access.</div>
                                @error('admin_reason')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif
                    </div>
                    <div class="card-footer d-flex justify-content-end gap-2">
                        <a href="{{ route('tenants.pages.show', ['page' => 'bans']) }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Ban</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
