<?php

namespace App\Http\Controllers;

use App\Models\ContactRole;
use App\Models\Tenant;
use App\Models\TenantContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantContactController extends Controller
{
    /**
     * Display the contacts assigned to a tenant.
     */
    public function index(Request $request, Tenant $tenant): View
    {
        $tenant->load(['contacts.role']);
        $contacts = $tenant->contacts->sortBy('name')->values();

        return view('tenants.contacts.index', [
            'tenant' => $tenant,
            'contacts' => $contacts,
            'roles' => ContactRole::orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created contact for the tenant.
     */
    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'preferred_method' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'contact_role_id' => ['nullable', 'integer', 'exists:contact_roles,id'],
        ]);

        $tenant->contacts()->create($data);

        return redirect()
            ->route('tenants.contacts.index', $tenant)
            ->with('status', 'Contact added to tenant.');
    }

    /**
     * Update an existing tenant contact.
     */
    public function update(Request $request, Tenant $tenant, TenantContact $contact): RedirectResponse
    {
        abort_unless($contact->tenant_id === $tenant->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'preferred_method' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'contact_role_id' => ['nullable', 'integer', 'exists:contact_roles,id'],
        ]);

        $contact->update($data);

        return redirect()
            ->route('tenants.contacts.index', $tenant)
            ->with('status', 'Contact updated.');
    }

    /**
     * Remove a contact from the tenant.
     */
    public function destroy(Tenant $tenant, TenantContact $contact): RedirectResponse
    {
        abort_unless($contact->tenant_id === $tenant->id, 404);

        $contact->delete();

        return redirect()
            ->route('tenants.contacts.index', $tenant)
            ->with('status', 'Contact removed.');
    }
}
