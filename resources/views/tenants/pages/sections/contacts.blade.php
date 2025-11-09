@php
    $contacts = [
        [
            'role' => 'Primary Contact',
            'name' => $tenant->displayName().' Ops',
            'email' => $tenant->contact_email ?? 'ops@'.$tenant->slug.'.example',
            'phone' => '+1 (555) '.random_int(200, 999).'-'.random_int(1000, 9999),
            'preferred' => 'Email',
        ],
        [
            'role' => 'Escalation Manager',
            'name' => 'Alex Morgan',
            'email' => 'alex.morgan@'.$tenant->slug.'.example',
            'phone' => '+1 (555) '.random_int(200, 999).'-'.random_int(1000, 9999),
            'preferred' => 'Phone',
        ],
        [
            'role' => 'Billing',
            'name' => 'Finance Team',
            'email' => 'billing@'.$tenant->slug.'.example',
            'phone' => '+1 (555) '.random_int(200, 999).'-'.random_int(1000, 9999),
            'preferred' => 'Email',
        ],
    ];
@endphp

<div class="row">
    <div class="col-lg-4">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Key Summary</h3>
            </div>
            <div class="card-body">
                <p class="text-muted">Use this panel to keep everyone aligned on who to contact for tenant-specific events.</p>
                <ul class="list-unstyled mb-0">
                    <li class="mb-3"><i class="fas fa-id-badge text-primary mr-2"></i> {{ count($contacts) }} named contacts</li>
                    <li class="mb-3"><i class="fas fa-life-ring text-success mr-2"></i> Primary contact reachable 24/7</li>
                    <li class="mb-0"><i class="fas fa-envelope text-info mr-2"></i> Preferred channel: Email</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card card-outline card-secondary">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Contact Directory</h3>
                <button class="btn btn-sm btn-outline-primary" type="button">
                    <i class="fas fa-plus mr-1"></i> Add Contact
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th class="d-none d-md-table-cell">Phone</th>
                                <th>Preferred</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($contacts as $contact)
                                <tr>
                                    <td><span class="badge badge-light font-weight-normal">{{ $contact['role'] }}</span></td>
                                    <td>{{ $contact['name'] }}</td>
                                    <td>
                                        <a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a>
                                    </td>
                                    <td class="d-none d-md-table-cell">{{ $contact['phone'] }}</td>
                                    <td>{{ $contact['preferred'] }}</td>
                                    <td class="text-right">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-default"><i class="fas fa-edit"></i></button>
                                            <button type="button" class="btn btn-default text-danger"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
