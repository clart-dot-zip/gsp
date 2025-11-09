<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantPageController extends Controller
{
    /**
     * Display a tenant contextual page.
     */
    public function show(Request $request, string $page): View|RedirectResponse
    {
        $tenantId = (int) $request->session()->get('tenant_id');

        if ($tenantId === 0) {
            return redirect()->route('tenants.manage')
                ->with('status', 'Please add or choose a tenant to continue.');
        }

        $tenant = Tenant::with(['contacts.role'])->find($tenantId);

        if (! $tenant) {
            $request->session()->forget('tenant_id');

            return redirect()->route('tenants.manage')
                ->with('status', 'The selected tenant could not be found.');
        }

        $pages = config('tenant.pages', []);

        abort_unless(array_key_exists($page, $pages), 404);

        return view('tenants.pages.show', [
            'tenant' => $tenant,
            'pageKey' => $page,
            'pageTitle' => $pages[$page],
            'contacts' => $tenant->contacts,
        ]);
    }
}
