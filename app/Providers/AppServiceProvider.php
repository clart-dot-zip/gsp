<?php

namespace App\Providers;

use App\Models\Tenant;
use App\Support\TenantAccessManager;
use App\Support\TenantPageAuthorization;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('authentik', \SocialiteProviders\Authentik\Provider::class);
        });
        // Share Google Maps API key with all views
        View::share('googleMapsApiKey', env('GOOGLE_MAPS_API_KEY'));

        View::composer([
            'layouts.*',
            'dashboard',
            'tenants.*',
        ], function ($view) {
            /** @var Request $currentRequest */
            $currentRequest = App::make(Request::class);
            $tenantAccessOptions = TenantAccessManager::options($currentRequest);
            $isPlayerSession = Session::has('active_player_id');
            $currentUser = Auth::user();

            $tenantPages = TenantPageAuthorization::accessiblePages($currentUser);
            $tenantCategories = TenantPageAuthorization::accessibleCategories($currentUser);

            $view->with('tenantPages', $tenantPages);
            $view->with('tenantPageCategories', $tenantCategories);
            $view->with('canViewTenantPages', ! empty($tenantPages));
            $view->with('isPlayerSession', $isPlayerSession);

            if (! Schema::hasTable('tenants') || ! Auth::check()) {
                $view->with('availableTenants', Collection::make());
                $view->with('currentTenant', null);

                return;
            }

            $user = Auth::user();

            if ($tenantAccessOptions->isNotEmpty()) {
                $tenantIds = $tenantAccessOptions
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0)
                    ->unique()
                    ->values();

                $tenants = Tenant::whereIn('id', $tenantIds)->orderBy('name')->get();
                $selectedTenantId = (int) Session::get('tenant_id');

                if (! $tenants->contains('id', $selectedTenantId)) {
                    $firstTenant = $tenants->first();
                    $selectedTenantId = $firstTenant ? $firstTenant->id : 0;
                    if ($selectedTenantId > 0) {
                        Session::put('tenant_id', $selectedTenantId);
                    }
                }

                $currentTenant = $tenants->firstWhere('id', $selectedTenantId);

                $view->with('availableTenants', $tenants);
                $view->with('currentTenant', $currentTenant);

                return;
            }

            $tenants = Tenant::orderBy('name')->get();
            $selectedTenantId = (int) Session::get('tenant_id');
            $currentTenant = $tenants->firstWhere('id', $selectedTenantId);

            if (! $currentTenant && $tenants->isNotEmpty()) {
                $currentTenant = $tenants->first();
                Session::put('tenant_id', $currentTenant->id);
            }

            $view->with('availableTenants', $tenants);
            $view->with('currentTenant', $currentTenant);
        });
    }
}
