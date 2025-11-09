@php
    $contacts = $contacts ?? collect();
    $primaryContact = $contacts->firstWhere('role.name', 'Primary Contact') ?? $contacts->first();
    $preferredChannel = $primaryContact?->preferred_method ?? $primaryContact?->role?->name ?? 'Not set';
    $steamEnabled = $contacts->whereNotNull('steam_id')->count();
@endphp

<div class="row">
    <div class="col-lg-4">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Key Summary</h3>
            </div>
            <div class="card-body">
                <p class="text-muted">Stay aligned on who to contact for {{ $tenant->displayName() }} when incidents or reviews pop up.</p>
                <ul class="list-unstyled mb-0">
                    <li class="mb-3"><i class="fas fa-id-badge text-primary mr-2"></i> {{ $contacts->count() }} named contacts</li>
                    <li class="mb-3"><i class="fas fa-user-shield text-success mr-2"></i> Primary contact: {{ $primaryContact?->name ?? 'Not defined' }}</li>
                    <li class="mb-3"><i class="fab fa-steam-symbol text-secondary mr-2"></i> Steam-enabled contacts: {{ $steamEnabled }}</li>
                    <li class="mb-0"><i class="fas fa-envelope text-info mr-2"></i> Preferred channel: {{ $preferredChannel }}</li>
                </ul>
            </div>
            <div class="card-footer">
                <a href="{{ route('tenants.contacts.index', $tenant) }}" class="btn btn-outline-primary btn-block">
                    <i class="fas fa-address-book mr-2"></i>Manage Contacts
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card card-outline card-secondary">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Contact Directory</h3>
                <a href="{{ route('tenants.contacts.index', $tenant) }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-plus mr-1"></i> Add Contact
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Email</th>
                                <th class="d-none d-lg-table-cell">Steam ID</th>
                                <th class="d-none d-md-table-cell">Phone</th>
                                <th>Preferred</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($contacts as $contact)
                                <tr>
                                    <td>{{ $contact->name }}</td>
                                    <td>
                                        <span class="badge badge-light font-weight-normal">
                                            {{ $contact->role?->name ?? '—' }}
                                        </span>
                                    </td>
                                    <td><a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a></td>
                                    <td class="d-none d-lg-table-cell">{{ $contact->steam_id ?? '—' }}</td>
                                    <td class="d-none d-md-table-cell">{{ $contact->phone ?? '—' }}</td>
                                    <td>{{ $contact->preferred_method ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <em>No contacts recorded yet. Use the manage contacts screen to add your first point of contact.</em>
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
