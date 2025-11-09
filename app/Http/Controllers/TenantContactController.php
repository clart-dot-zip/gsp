<?php

namespace App\Http\Controllers;

use App\Models\ContactRole;
use App\Models\Group;
use App\Models\Tenant;
use App\Models\TenantContact;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
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
            'steam_id' => ['nullable', 'string', 'max:64', 'unique:tenant_contacts,steam_id'],
            'preferred_method' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'contact_role_id' => ['nullable', 'integer', 'exists:contact_roles,id'],
        ]);

        $contact = $tenant->contacts()->create($data);

        $this->syncContactUser($contact);

        return Redirect::route('tenants.contacts.index', $tenant)
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
            'steam_id' => ['nullable', 'string', 'max:64', 'unique:tenant_contacts,steam_id,'.$contact->id],
            'preferred_method' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'contact_role_id' => ['nullable', 'integer', 'exists:contact_roles,id'],
        ]);

        $contact->update($data);

        $this->syncContactUser($contact);

        return Redirect::route('tenants.contacts.index', $tenant)
            ->with('status', 'Contact updated.');
    }

    /**
     * Remove a contact from the tenant.
     */
    public function destroy(Tenant $tenant, TenantContact $contact): RedirectResponse
    {
        abort_unless($contact->tenant_id === $tenant->id, 404);

        if ($contact->user) {
            $this->detachContactUser($contact);
        }

        $contact->delete();

        return Redirect::route('tenants.contacts.index', $tenant)
            ->with('status', 'Contact removed.');
    }

    /**
     * Ensure a user account is synchronised with the tenant contact's Steam details.
     */
    protected function syncContactUser(TenantContact $contact): void
    {
        $contact->loadMissing('user');
        $existingUser = $contact->user;

        if ($existingUser && $existingUser->steam_id !== $contact->steam_id) {
            $this->detachContactUser($contact);
            $existingUser = null;
        }

        if (empty($contact->steam_id)) {
            if ($existingUser) {
                $this->detachContactUser($contact);
            }

            return;
        }

    $user = $existingUser ?: User::firstOrNew(['steam_id' => $contact->steam_id]);
        $email = $contact->email ?: 'steam-'.$contact->steam_id.'@auth.local';
        $user->fill([
            'name' => $contact->name,
            'email' => $email,
        ]);
        $user->tenant_contact_id = $contact->id;
        $user->save();

    $group = Group::firstWhere('slug', 'tenant-contact');
        if ($group && ! $user->groups()->whereKey($group->id)->exists()) {
            $user->groups()->attach($group->id);
        }
    }

    /**
     * Detach an existing user link from a tenant contact.
     */
    protected function detachContactUser(TenantContact $contact): void
    {
    $contact->loadMissing('user');
    $user = $contact->user;

        if (! $user) {
            return;
        }

    $group = Group::firstWhere('slug', 'tenant-contact');
        if ($group) {
            $user->groups()->detach($group->id);
        }

        $user->update([
            'tenant_contact_id' => null,
            'steam_id' => null,
        ]);
    }
}
