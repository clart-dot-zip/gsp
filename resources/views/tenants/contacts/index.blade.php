<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center justify-content-between w-100">
            <div>
                <h1 class="m-0 text-dark">Manage Contacts</h1>
                <p class="text-muted mb-0">Tenant: {{ $tenant->displayName() }}</p>
            </div>
            <a href="{{ route('tenants.manage') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Back to Tenants
            </a>
        </div>
    </x-slot>

    @if ($errors->any())
        <div class="alert alert-danger">
            <p class="mb-2 font-weight-bold">We hit a problem:</p>
            <ul class="mb-0 pl-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <div class="row">
        <div class="col-lg-5">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Add Contact</h3>
                </div>
                <form method="POST" action="{{ route('tenants.contacts.store', $tenant) }}">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="contact-name">Name</label>
                            <input type="text" id="contact-name" name="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="contact-email">Email</label>
                            <input type="email" id="contact-email" name="email" class="form-control" value="{{ old('email') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="contact-role">Role</label>
                            <select id="contact-role" name="contact_role_id" class="form-control">
                                <option value="">-- Select role --</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" {{ (string)old('contact_role_id') === (string)$role->id ? 'selected' : '' }}>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="contact-phone">Phone</label>
                            <input type="text" id="contact-phone" name="phone" class="form-control" value="{{ old('phone') }}">
                        </div>
                        <div class="form-group">
                            <label for="contact-preferred">Preferred Method</label>
                            <input type="text" id="contact-preferred" name="preferred_method" class="form-control" value="{{ old('preferred_method') }}" placeholder="Email, Phone, Teams…">
                        </div>
                        <div class="form-group">
                            <label for="contact-notes">Notes</label>
                            <textarea id="contact-notes" name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">Save Contact</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card card-outline card-secondary mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Contact Roles</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($roles as $role)
                                    <tr>
                                        <td>{{ $role->name }}</td>
                                        <td>{{ $role->description ?? '—' }}</td>
                                        <td class="text-right">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-default" data-toggle="collapse" data-target="#edit-role-{{ $role->id }}" aria-expanded="false" aria-controls="edit-role-{{ $role->id }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" action="{{ route('contact-roles.destroy', $role) }}" onsubmit="return confirm('Delete this role?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-default text-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr id="edit-role-{{ $role->id }}" class="collapse">
                                        <td colspan="3">
                                            <form method="POST" action="{{ route('contact-roles.update', $role) }}" class="p-3 border-top">
                                                @csrf
                                                @method('PUT')
                                                <div class="form-row">
                                                    <div class="form-group col-md-4">
                                                        <label class="sr-only" for="role-name-{{ $role->id }}">Name</label>
                                                            <input type="text" id="role-name-{{ $role->id }}" name="name" class="form-control" value="{{ $role->name }}" required>
                                                    </div>
                                                    <div class="form-group col-md-6">
                                                        <label class="sr-only" for="role-description-{{ $role->id }}">Description</label>
                                                            <input type="text" id="role-description-{{ $role->id }}" name="description" class="form-control" value="{{ $role->description }}">
                                                    </div>
                                                    <div class="form-group col-md-2 text-right">
                                                        <button type="submit" class="btn btn-primary btn-block">Save</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <form method="POST" action="{{ route('contact-roles.store') }}" class="form-inline">
                        @csrf
                        <div class="form-group mr-2 mb-2">
                            <label for="new-role-name" class="sr-only">Role name</label>
                            <input type="text" id="new-role-name" name="name" class="form-control" placeholder="Role name" required>
                        </div>
                        <div class="form-group mr-2 mb-2 flex-grow-1" style="min-width: 200px;">
                            <label for="new-role-description" class="sr-only">Description</label>
                            <input type="text" id="new-role-description" name="description" class="form-control w-100" placeholder="Optional description">
                        </div>
                        <button type="submit" class="btn btn-primary mb-2">Add Role</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Assigned Contacts</h3>
                    <span class="badge badge-pill badge-primary">{{ $contacts->count() }} total</span>
                </div>
                <div class="card-body">
                    @forelse ($contacts as $contact)
                        <div class="card card-outline card-light mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $contact->name }}</strong>
                                    @if ($contact->role)
                                        <span class="badge badge-secondary ml-2">{{ $contact->role->name }}</span>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('tenants.contacts.destroy', [$tenant, $contact]) }}" onsubmit="return confirm('Remove this contact?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash mr-1"></i>Remove
                                    </button>
                                </form>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('tenants.contacts.update', [$tenant, $contact]) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-row">
                                        <div class="form-group col-md-4">
                                            <label for="contact-name-{{ $contact->id }}">Name</label>
                                                <input type="text" id="contact-name-{{ $contact->id }}" name="name" class="form-control" value="{{ $contact->name }}" required>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="contact-email-{{ $contact->id }}">Email</label>
                                                <input type="email" id="contact-email-{{ $contact->id }}" name="email" class="form-control" value="{{ $contact->email }}" required>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="contact-role-{{ $contact->id }}">Role</label>
                                            <select id="contact-role-{{ $contact->id }}" name="contact_role_id" class="form-control">
                                                <option value="">-- Select role --</option>
                                                @foreach ($roles as $role)
                                                        <option value="{{ $role->id }}" {{ (string)$contact->contact_role_id === (string)$role->id ? 'selected' : '' }}>
                                                        {{ $role->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-4">
                                            <label for="contact-phone-{{ $contact->id }}">Phone</label>
                                            <input type="text" id="contact-phone-{{ $contact->id }}" name="phone" class="form-control" value="{{ $contact->phone }}">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="contact-preferred-{{ $contact->id }}">Preferred Method</label>
                                            <input type="text" id="contact-preferred-{{ $contact->id }}" name="preferred_method" class="form-control" value="{{ $contact->preferred_method }}">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="contact-notes-{{ $contact->id }}">Notes</label>
                                            <textarea id="contact-notes-{{ $contact->id }}" name="notes" rows="2" class="form-control">{{ $contact->notes }}</textarea>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save mr-1"></i>Update Contact
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="mb-0 text-muted">No contacts yet. Add the first contact using the form on the left.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
