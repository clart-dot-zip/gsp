<?php

namespace App\Http\Controllers;

use App\Models\ContactRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ContactRoleController extends Controller
{
    /**
     * Store a newly created contact role.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:contact_roles,name'],
            'description' => ['nullable', 'string'],
        ]);

    ContactRole::create($data);

    return Redirect::back()->with('status', 'Role created.');
    }

    /**
     * Update an existing contact role.
     */
    public function update(Request $request, ContactRole $role): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:contact_roles,name,'.$role->id],
            'description' => ['nullable', 'string'],
        ]);

    $role->update($data);

    return Redirect::back()->with('status', 'Role updated.');
    }

    /**
     * Remove the specified role.
     */
    public function destroy(ContactRole $role): RedirectResponse
    {
        if ($role->contacts()->exists()) {
            return Redirect::back()->withErrors([
                'role' => 'Cannot delete a role that is currently assigned to contacts.',
            ]);
        }

        $role->delete();

        return Redirect::back()->with('status', 'Role removed.');
    }
}
