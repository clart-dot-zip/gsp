<x-app-layout>
    <x-slot name="header">
        <h1 class="m-0 text-dark">{{ __('Manage Tenants') }}</h1>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <div class="row">
        <div class="col-lg-5">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Add Tenant</h3>
                </div>
                <form method="POST" action="{{ route('tenants.store') }}">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="tenant-name">Name</label>
                            <input type="text" id="tenant-name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                            @error('name')
                                <span class="invalid-feedback" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="tenant-description">Description</label>
                            <textarea id="tenant-description" name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                            @error('description')
                                <span class="invalid-feedback" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="tenant-contact-email">Contact Email</label>
                            <input type="email" id="tenant-contact-email" name="contact_email" class="form-control @error('contact_email') is-invalid @enderror" value="{{ old('contact_email') }}">
                            @error('contact_email')
                                <span class="invalid-feedback" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="tenant-website-url">Website URL</label>
                            <input type="url" id="tenant-website-url" name="website_url" class="form-control @error('website_url') is-invalid @enderror" value="{{ old('website_url') }}">
                            @error('website_url')
                                <span class="invalid-feedback" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">Save Tenant</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Existing Tenants</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Name</th>
                                    <th scope="col" class="d-none d-md-table-cell">Contact</th>
                                    <th scope="col" class="d-none d-md-table-cell">Website</th>
                                    <th scope="col" class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tenants as $tenant)
                                    <tr class="{{ optional($selectedTenant)->id === $tenant->id ? 'table-active' : '' }}">
                                        <td>
                                            <strong>{{ $tenant->displayName() }}</strong>
                                            @if ($tenant->description)
                                                <div class="text-muted small">{{ \Illuminate\Support\Str::limit($tenant->description, 90) }}</div>
                                            @endif
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            {{ $tenant->contact_email ?? '—' }}
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            @if ($tenant->website_url)
                                                <a href="{{ $tenant->website_url }}" target="_blank" rel="noopener">{{ parse_url($tenant->website_url, PHP_URL_HOST) ?? $tenant->website_url }}</a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <div class="btn-group" role="group" aria-label="Tenant actions">
                                                <form method="POST" action="{{ route('tenants.select') }}">
                                                    @csrf
                                                    <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">
                                                    <input type="hidden" name="origin" value="{{ request()->fullUrl() }}">
                                                    <button type="submit" class="btn btn-sm btn-primary {{ optional($selectedTenant)->id === $tenant->id ? 'disabled' : '' }}">
                                                        {{ optional($selectedTenant)->id === $tenant->id ? 'Selected' : 'Select' }}
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            <em>No tenants yet. Add one using the form.</em>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
