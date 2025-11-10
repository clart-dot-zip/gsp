<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\TenantContact;
use App\Models\TenantPlayer;
use App\Models\User;
use App\Services\SteamOpenIdService;
use App\Support\TenantAccessManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\View\View;
use function collect;

class AuthController extends Controller
{
    protected SteamOpenIdService $steamOpenId;

    public function __construct(SteamOpenIdService $steamOpenId)
    {
        $this->steamOpenId = $steamOpenId;
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function redirect()
    {
        return Socialite::driver('authentik')->redirect();
    }

    public function callback()
    {
        try {
            $authentikUser = Socialite::driver('authentik')->user();

            $user = User::updateOrCreate([
                'email' => $authentikUser->getEmail(),
            ], [
                'name' => $authentikUser->getName(),
                'authentik_id' => $authentikUser->getId(),
                'avatar' => $authentikUser->getAvatar(),
            ]);

            Auth::login($user, true);

            return Redirect::route('dashboard');
        } catch (\Exception $e) {
            return Redirect::to('/login')->withErrors(['error' => 'Authentication failed.']);
        }
    }

    public function redirectToSteam(Request $request)
    {
        $mode = $this->resolveSteamMode($request->query('mode'));
        $request->session()->put('steam_login_mode', $mode);

        $redirectUrl = $this->steamOpenId->getRedirectUrl($request, ['mode' => $mode]);

        Log::debug('Redirecting user to Steam OpenID provider.', [
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'redirect_url' => $redirectUrl,
            'mode' => $mode,
        ]);

        return Redirect::away($redirectUrl);
    }

    public function handleSteamCallback(Request $request)
    {
        try {
            $queryKeys = array_keys($request->query());
            $hasOpenIdParams = ! empty(array_filter($queryKeys, static fn ($key) => Str::startsWith($key, 'openid')));
            $mode = $this->resolveSteamMode($request->query('mode', $request->session()->pull('steam_login_mode')));

            Log::debug('Handling Steam OpenID callback.', [
                'ip' => $request->ip(),
                'has_openid_params' => $hasOpenIdParams,
                'query_keys' => $queryKeys,
                'mode' => $mode,
            ]);

            $steamId = $this->steamOpenId->validate($request);

            if (! $steamId) {
                Log::warning('Steam OpenID validation returned no Steam ID.', [
                    'ip' => $request->ip(),
                    'query' => $request->query(),
                    'mode' => $mode,
                ]);

                return Redirect::route('login')->withErrors([
                    'error' => 'Steam authentication could not be validated.',
                ]);
            }

            return $mode === 'player'
                ? $this->completePlayerSteamLogin($request, $steamId)
                : $this->completeContactSteamLogin($request, $steamId);
        } catch (\Throwable $e) {
            Log::error('Steam authentication callback threw an exception.', [
                'message' => $e->getMessage(),
                'trace_id' => $request->header('X-Request-ID'),
                'ip' => $request->ip(),
            ]);

            return Redirect::route('login')->withErrors([
                'error' => 'Steam authentication failed.',
            ]);
        }
    }

    protected function completeContactSteamLogin(Request $request, string $steamId)
    {
        $contacts = TenantContact::where('steam_id', $steamId)->with(['tenant', 'role'])->get();

        if ($contacts->isEmpty()) {
            Log::warning('Steam OpenID matched Steam ID with no tenant contact.', [
                'steam_id' => $steamId,
            ]);

            return Redirect::route('login')->withErrors([
                'error' => 'No tenant contact is linked to this Steam account.',
            ]);
        }

        $primaryContact = $contacts->first();
        $preferredName = $primaryContact && $primaryContact->name ? $primaryContact->name : null;
        $preferredEmail = $primaryContact && $primaryContact->email ? $primaryContact->email : null;
        $fallbackEmail = 'steam-' . $steamId . '@contacts.auth.local';

        $user = User::where('steam_id', $steamId)->first();

        if (! $user && $preferredEmail) {
            $user = User::where('email', $preferredEmail)->first();

            if ($user && $user->steam_id && $user->steam_id !== $steamId) {
                Log::warning('Steam contact login email matched a different Steam user. Falling back to generated email.', [
                    'steam_id' => $steamId,
                    'conflicting_user_id' => $user->id,
                    'conflicting_steam_id' => $user->steam_id,
                ]);

                $user = null;
                $preferredEmail = null;
            }
        }

        if (! $user) {
            $user = new User();
            $user->email = $preferredEmail ?: $fallbackEmail;
        } else {
            if ($preferredEmail && $user->email !== $preferredEmail) {
                $user->email = $preferredEmail;
            } elseif (! $user->email) {
                $user->email = $fallbackEmail;
            }
        }

        if ($preferredName && $user->name !== $preferredName) {
            $user->name = $preferredName;
        } elseif (! $user->name) {
            $user->name = 'Steam Contact';
        }

        $user->steam_id = $steamId;

        if ($primaryContact) {
            $user->tenant_contact_id = $primaryContact->id;
        }

        $user->save();

        $group = Group::firstWhere('slug', 'tenant-contact');
        if ($group) {
            $user->groups()->syncWithoutDetaching([$group->id]);
        }

        Auth::login($user, true);

        $options = $contacts->map(function (TenantContact $contact): array {
            $tenant = $contact->tenant;
            $tenantName = $tenant ? $tenant->displayName() : 'Tenant #'.$contact->tenant_id;
            $role = $contact->role;
            $roleName = $role ? $role->name : null;

            return [
                'id' => $contact->tenant_id,
                'name' => $tenantName,
                'type' => 'contact',
                'tenant_contact_id' => $contact->id,
                'roles' => $roleName ? [$roleName] : [],
            ];
        })->unique('id')->values()->all();

        TenantAccessManager::storeOptions($request, $options);

        $storedOptions = TenantAccessManager::options($request);
        if ($storedOptions->isNotEmpty()) {
            $activeOption = $storedOptions->firstWhere('id', (int) $request->session()->get('tenant_id'))
                ?? $storedOptions->first();

            if ($activeOption) {
                TenantAccessManager::activateSelection($request, $user, $activeOption);
            }
        }

        Log::info('Steam contact login completed successfully.', [
            'user_id' => $user->id,
            'steam_id' => $steamId,
            'tenant_ids' => $storedOptions->pluck('id')->all(),
        ]);

        if ($storedOptions->count() > 1) {
            return Redirect::route('tenant-access.show');
        }

        return Redirect::route('tenants.pages.show', ['page' => 'support_tickets']);
    }

    protected function completePlayerSteamLogin(Request $request, string $steamId)
    {
    $players = TenantPlayer::where('steam_id', $steamId)->with(['tenant', 'groups'])->get();

        if ($players->isEmpty()) {
            Log::warning('Steam OpenID matched Steam ID with no tenant player.', [
                'steam_id' => $steamId,
            ]);

            return Redirect::route('login')->withErrors([
                'error' => 'No player record is linked to this Steam account yet.',
            ]);
        }

        $primaryPlayer = $players->first();

        $user = User::updateOrCreate([
            'steam_id' => $steamId,
        ], [
            'name' => ($primaryPlayer && $primaryPlayer->display_name) ? $primaryPlayer->display_name : 'Steam Player',
            'email' => 'steam-'.$steamId.'@players.auth.local',
        ]);

        if ($user->tenant_contact_id) {
            $user->tenant_contact_id = null;
            $user->save();
        }

    $group = Group::firstWhere('slug', 'player');
        if ($group) {
            $user->groups()->syncWithoutDetaching([$group->id]);
        }

        Auth::login($user, true);

        $options = $players->map(function (TenantPlayer $player): array {
            $tenant = $player->tenant;
            $tenantName = $tenant ? $tenant->displayName() : 'Tenant #'.$player->tenant_id;
            $groups = $player->groups ?? collect();

            return [
                'id' => $player->tenant_id,
                'name' => $tenantName,
                'type' => 'player',
                'tenant_player_id' => $player->id,
                'roles' => $groups->pluck('name')->values()->all(),
                'player_note' => $player->last_synced_at ? 'Last synced '.$player->last_synced_at->diffForHumans() : null,
            ];
        })->unique('id')->values()->all();

        TenantAccessManager::storeOptions($request, $options);

        $storedOptions = TenantAccessManager::options($request);
        if ($storedOptions->isNotEmpty()) {
            $activeOption = $storedOptions->firstWhere('id', (int) $request->session()->get('tenant_id'))
                ?? $storedOptions->first();

            if ($activeOption) {
                TenantAccessManager::activateSelection($request, $user, $activeOption);
            }
        }

        Log::info('Steam player login completed successfully.', [
            'user_id' => $user->id,
            'steam_id' => $steamId,
            'tenant_ids' => $storedOptions->pluck('id')->all(),
        ]);

        if ($storedOptions->count() > 1) {
            return Redirect::route('tenant-access.show');
        }

        return Redirect::route('tenants.pages.show', ['page' => 'support_tickets']);
    }

    private function resolveSteamMode(?string $mode): string
    {
        return in_array($mode, ['contact', 'player'], true) ? $mode : 'contact';
    }

    public function logout()
    {
        Auth::logout();
        Session::forget([
            'tenant_id',
            'tenant_access_options',
            'active_contact_id',
            'active_player_id',
            'steam_login_mode',
        ]);

        return Redirect::to('/');
    }
}
