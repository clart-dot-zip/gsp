<?php

namespace App\Http\Controllers;

use App\Support\TenantAccessManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantAccessController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $options = TenantAccessManager::options($request);

        if ($options->isEmpty()) {
            return redirect()
                ->route('dashboard')
                ->with('status', 'No tenant access has been granted to this account yet.');
        }

        $currentTenantId = (int) $request->session()->get('tenant_id');

        return view('auth.select-tenant', [
            'tenantOptions' => $options->sortBy('name')->values(),
            'currentTenantId' => $currentTenantId,
            'origin' => $request->query('origin'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $options = TenantAccessManager::options($request);

        if ($options->isEmpty()) {
            abort(403);
        }

        $data = $request->validate([
            'tenant_id' => ['required', 'integer'],
            'origin' => ['nullable', 'url'],
        ]);

        $tenantId = (int) $data['tenant_id'];

        $selection = $options->firstWhere('id', $tenantId);

        if (! $selection) {
            abort(403);
        }

        TenantAccessManager::activateSelection($request, $request->user(), $selection);

        $request->session()->flash('status', 'Tenant switched to '.$selection['name']);

        $destination = $data['origin'] ?? route('tenants.pages.show', ['page' => 'support_tickets']);

        return redirect()->to($destination);
    }
}
