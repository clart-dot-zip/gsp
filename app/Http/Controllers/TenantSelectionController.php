<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TenantSelectionController extends Controller
{
    /**
     * Update the current tenant selection stored in the session.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'origin' => ['nullable', 'url'],
        ]);

        $request->session()->put('tenant_id', $data['tenant_id']);
        $request->session()->flash('status', 'Tenant changed.');

        $redirectUrl = $data['origin'] ?? route('dashboard');

        return redirect()->to($redirectUrl);
    }
}
