@php
    $contactCount = $tenant->contacts?->count() ?? 0;
@endphp

<div class="row">
    <div class="col-lg-4 col-md-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $tenant->displayName() }}</h3>
                <p>Current Tenant</p>
            </div>
            <div class="icon">
                <i class="fas fa-building"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8">
        <div class="card card-outline card-primary">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Tenant Snapshot</h3>
                <span class="badge badge-primary">{{ strtoupper(substr($tenant->slug, 0, 3)) }}</span>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Description</dt>
                    <dd class="col-sm-8">{{ $tenant->description ?? 'Add a short description to share context with other operators.' }}</dd>

                    <dt class="col-sm-4">Primary Contact</dt>
                    <dd class="col-sm-8">{{ $tenant->contact_email ?? 'Not provided yet' }}</dd>

                    <dt class="col-sm-4">Website</dt>
                    <dd class="col-sm-8">
                        @if ($tenant->website_url)
                            <a href="{{ $tenant->website_url }}" target="_blank" rel="noopener">{{ $tenant->website_url }}</a>
                        @else
                            —
                        @endif
                    </dd>

                    <dt class="col-sm-4">Contacts on file</dt>
                    <dd class="col-sm-8">{{ $contactCount }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>