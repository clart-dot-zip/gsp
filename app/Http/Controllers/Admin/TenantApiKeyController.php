<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantApiKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TenantApiKeyController extends Controller
{
    public function index(): View
    {
        return view('admin.tenants.api-keys', [
            'tenants' => Tenant::with('dataCollectorKey')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $request->validate([
            'action' => ['nullable', 'in:create,regenerate'],
        ]);

        $plainKey = Str::random(64);
        $keyHash = hash('sha256', $plainKey);

        $apiKey = TenantApiKey::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'key_type' => TenantApiKey::TYPE_DATA_COLLECTOR,
            ],
            [
                'name' => 'Data Collector',
                'key_hash' => $keyHash,
                'last_four' => substr($plainKey, -4),
                'last_used_at' => null,
            ],
        );

        $message = $apiKey->wasRecentlyCreated
            ? sprintf('Data collector API key created for %s.', $tenant->displayName())
            : sprintf('Data collector API key regenerated for %s.', $tenant->displayName());

        return Redirect::route('admin.tenants.api-keys.index')
            ->with('status', $message)
            ->with('new_key', [
                'tenant' => $tenant->displayName(),
                'value' => $plainKey,
            ]);
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        TenantApiKey::where('tenant_id', $tenant->id)
            ->where('key_type', TenantApiKey::TYPE_DATA_COLLECTOR)
            ->delete();

        return Redirect::route('admin.tenants.api-keys.index')
            ->with('status', sprintf('Data collector API key revoked for %s.', $tenant->displayName()));
    }
}
