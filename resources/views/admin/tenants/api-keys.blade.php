<x-app-layout>
    <x-slot name="header">
        <h1 class="m-0 text-dark">Tenant Data Collector Keys</h1>
    </x-slot>

    @if (session('new_key'))
        @php($newKey = session('new_key'))
        <div class="alert alert-warning">
            <h5 class="mb-2">New API key for {{ $newKey['tenant'] }}</h5>
            <p class="mb-2">Copy this value now. It will not be shown again.</p>
            <div class="bg-dark text-monospace text-light p-3 rounded">
                <code class="text-wrap">{{ $newKey['value'] }}</code>
            </div>
        </div>
    @endif

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title mb-0">Per-tenant API credentials</h3>
        </div>
        <div class="card-body">
            <p class="text-muted">Generate a data collector API key for each tenant to allow Garry's Mod integrations to read and push data. Keys are tied to tenants and can be rotated or revoked at any time.</p>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Tenant</th>
                            <th>Status</th>
                            <th>Last used</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tenants as $tenant)
                            @php($key = $tenant->dataCollectorKey)
                            <tr>
                                <td>
                                    <strong>{{ $tenant->displayName() }}</strong>
                                    <div class="text-muted small">{{ $tenant->slug }}</div>
                                </td>
                                <td>
                                    @if ($key)
                                        <span class="badge badge-success">Active</span>
                                        <div class="text-muted small">Last four: {{ $key->last_four ?? 'N/A' }}</div>
                                    @else
                                        <span class="badge badge-secondary">Not created</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($key && $key->last_used_at)
                                        <span class="text-muted">{{ $key->last_used_at->diffForHumans() }}</span>
                                    @else
                                        <span class="text-muted">Never</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <div class="d-inline-flex">
                                        <form method="POST" action="{{ route('admin.tenants.api-keys.store', $tenant) }}" class="mr-2">
                                            @csrf
                                            <input type="hidden" name="action" value="{{ $key ? 'regenerate' : 'create' }}">
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                {{ $key ? 'Regenerate' : 'Create' }}
                                            </button>
                                        </form>
                                        @if ($key)
                                            <form method="POST" action="{{ route('admin.tenants.api-keys.destroy', $tenant) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Revoke the current API key for {{ $tenant->displayName() }}? Integrations will stop working until a new key is issued.');">
                                                    Revoke
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">No tenants found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
