<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantController extends Controller
{
    /**
     * Display the manage tenants screen.
     */
    public function index(Request $request): View
    {
        $tenants = Tenant::with('contacts')->orderBy('name')->get();
        $selectedTenantId = (int) $request->session()->get('tenant_id');
        $selectedTenant = $tenants->firstWhere('id', $selectedTenantId);

        return view('tenants.manage', [
            'tenants' => $tenants,
            'selectedTenant' => $selectedTenant,
        ]);
    }

    /**
     * Persist a newly created tenant.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:tenants,name'],
            'description' => ['nullable', 'string'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
        ]);

        $data['slug'] = Tenant::generateUniqueSlug($data['name']);

        $tenant = Tenant::create($data);

        $request->session()->put('tenant_id', $tenant->id);

        return redirect()->route('tenants.manage')
            ->with('status', 'Tenant created successfully.');
    }
}
