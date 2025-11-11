<x-app-layout>
    <x-slot name="header">
        <h1 class="m-0 text-dark">{{ __('Manage Tenants') }}</h1>
    </x-slot>

    @php
        $currentUser = Auth::user();
    @endphp

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
                                    <th scope="col" class="d-none d-md-table-cell">Primary Email</th>
                                    <th scope="col" class="text-center">Contacts</th>
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
                                        <td class="text-center">
                                            <span class="badge badge-primary">{{ $tenant->contacts_count ?? 0 }}</span>
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
                                                @if ($currentUser && $currentUser->hasPermission('manage_contacts'))
                                                    <a href="{{ route('tenants.contacts.index', $tenant) }}" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-address-book mr-1"></i>Contacts
                                                    </a>
                                                @endif
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    data-toggle="collapse"
                                                    data-target="#delete-tenant-{{ $tenant->id }}"
                                                    aria-expanded="{{ old('delete_confirmation_tenant_id') == $tenant->id ? 'true' : 'false' }}"
                                                    aria-controls="delete-tenant-{{ $tenant->id }}">
                                                    <i class="fas fa-trash-alt mr-1"></i>Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @php
                                        $tenantDeletionErrors = $errors->getBag('tenantDeletion');
                                        $showDeletionForm = old('delete_confirmation_tenant_id') == $tenant->id;
                                        $hasDeleteFieldError = $tenantDeletionErrors->has('delete_confirmation_name') && $showDeletionForm;
                                        $deleteFieldError = $hasDeleteFieldError ? $tenantDeletionErrors->first('delete_confirmation_name') : null;
                                    @endphp
                                    <tr id="delete-tenant-{{ $tenant->id }}" class="collapse {{ $showDeletionForm ? 'show' : '' }}">
                                        <td colspan="5" class="bg-light">
                                            <form method="POST" action="{{ route('tenants.destroy', $tenant) }}" class="border rounded p-3">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="delete_confirmation_tenant_id" value="{{ $tenant->id }}">
                                                <p class="mb-3 text-danger">
                                                    Deleting <strong>{{ $tenant->name }}</strong> removes all associated contacts, permissions, players, bans, tickets, and logs. This action cannot be undone.
                                                </p>
                                                <div class="form-row align-items-center">
                                                    <div class="col-sm-8 col-md-9 mb-2">
                                                        <label for="delete-confirmation-{{ $tenant->id }}" class="sr-only">Confirm tenant name</label>
                                                        <input
                                                            type="text"
                                                            id="delete-confirmation-{{ $tenant->id }}"
                                                            name="delete_confirmation_name"
                                                            class="form-control form-control-sm {{ $hasDeleteFieldError ? 'is-invalid' : '' }}"
                                                            placeholder="Type '{{ $tenant->name }}' to confirm"
                                                            value="{{ $showDeletionForm ? old('delete_confirmation_name') : '' }}"
                                                            required
                                                            data-tenant-delete-input="{{ e($tenant->name) }}"
                                                            data-submit-button="delete-tenant-submit-{{ $tenant->id }}">
                                                        @if ($hasDeleteFieldError)
                                                            <span class="invalid-feedback d-block">{{ $deleteFieldError }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="col-sm-4 col-md-3 mb-2 text-right">
                                                        <button type="submit" class="btn btn-sm btn-danger" id="delete-tenant-submit-{{ $tenant->id }}">
                                                            Delete Tenant
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
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
    @once
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var inputs = document.querySelectorAll('[data-tenant-delete-input]');

                inputs.forEach(function (input) {
                    var expected = input.getAttribute('data-tenant-delete-input');
                    var buttonId = input.getAttribute('data-submit-button');
                    var submitButton = document.getElementById(buttonId);

                    if (!submitButton) {
                        return;
                    }

                    var toggleState = function () {
                        if (input.value.trim() === expected) {
                            submitButton.removeAttribute('disabled');
                        } else {
                            submitButton.setAttribute('disabled', 'disabled');
                        }
                    };

                    input.addEventListener('input', toggleState);
                    toggleState();
                });
            });
        </script>
    @endonce
</x-app-layout>
