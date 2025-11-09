<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantActivityLog;
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

        $categories = config('tenant.categories', []);
        $pages = [];

        foreach ($categories as $category) {
            foreach ($category['pages'] ?? [] as $pageKey => $pageTitle) {
                $pages[$pageKey] = $pageTitle;
            }
        }

        abort_unless(array_key_exists($page, $pages), 404);

        $user = $request->user();
        if ($user && method_exists($user, 'isTenantContact') && $user->isTenantContact()) {
            $contactTenantId = $user->tenantContact ? $user->tenantContact->tenant_id : null;
            if ($contactTenantId !== $tenant->id) {
                abort(403);
            }
        }

        $activityLogs = null;
        $permissionGroups = collect();
        $permissionDefinitions = collect();
        $tenantPlayers = collect();
        $permissionsOverview = null;

        if ($page === 'activity_logs') {
            $activityLogs = TenantActivityLog::with(['user'])
                ->where('tenant_id', $tenant->id)
                ->latest()
                ->paginate(25)
                ->appends($request->query());
        }

        switch ($page) {
            case 'permissions_overview':
                $permissionsOverview = [
                    'group_count' => $tenant->permissionGroups()->count(),
                    'permission_count' => $tenant->permissionDefinitions()->count(),
                    'player_count' => $tenant->players()->count(),
                ];
                break;

            case 'permissions_groups':
                $permissionGroups = $tenant->permissionGroups()
                    ->with(['parents', 'children', 'permissions'])
                    ->withCount('players')
                    ->orderBy('name')
                    ->get();
                break;

            case 'permissions_group_permissions':
                $permissionGroups = $tenant->permissionGroups()
                    ->with('permissions')
                    ->orderBy('name')
                    ->get();
                $permissionDefinitions = $tenant->permissionDefinitions()
                    ->withCount('groups')
                    ->orderBy('name')
                    ->get();
                break;
            case 'permissions_definitions':
                $permissionDefinitions = $tenant->permissionDefinitions()
                    ->withCount('groups')
                    ->orderBy('name')
                    ->get();
                break;

            case 'permissions_users':
                $tenantPlayers = $tenant->players()
                    ->with('groups')
                    ->orderBy('display_name')
                    ->get();
                $permissionGroups = $tenant->permissionGroups()->orderBy('name')->get();
                break;
        }

        return view('tenants.pages.show', [
            'tenant' => $tenant,
            'pageKey' => $page,
            'pageTitle' => $pages[$page],
            'contacts' => $tenant->contacts,
            'activityLogs' => $activityLogs,
            'permissionGroups' => $permissionGroups,
            'permissionDefinitions' => $permissionDefinitions,
            'tenantPlayers' => $tenantPlayers,
            'permissionsOverview' => $permissionsOverview,
        ]);
    }
}
