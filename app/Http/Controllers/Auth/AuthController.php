<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\TenantContact;
use App\Models\User;
use App\Services\SteamOpenIdService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\View\View;

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
        $redirectUrl = $this->steamOpenId->getRedirectUrl($request);

        Log::debug('Redirecting user to Steam OpenID provider.', [
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'redirect_url' => $redirectUrl,
        ]);

        return Redirect::away($redirectUrl);
    }

    public function handleSteamCallback(Request $request)
    {
        try {
            Log::debug('Handling Steam OpenID callback.', [
                'ip' => $request->ip(),
                'has_openid_params' => (bool) $request->query('openid.claimed_id'),
                'query_keys' => array_keys($request->query()),
            ]);

            $steamId = $this->steamOpenId->validate($request);

            if (! $steamId) {
                Log::warning('Steam OpenID validation returned no Steam ID.', [
                    'ip' => $request->ip(),
                    'query' => $request->query(),
                ]);

                return Redirect::route('login')->withErrors([
                    'error' => 'Steam authentication could not be validated.',
                ]);
            }

            Log::debug('Steam OpenID validation succeeded.', [
                'steam_id' => $steamId,
            ]);

            $contact = TenantContact::where('steam_id', $steamId)->with('tenant')->first();

            if (! $contact) {
                Log::warning('Steam OpenID matched Steam ID with no tenant contact.', [
                    'steam_id' => $steamId,
                ]);

                return Redirect::route('login')->withErrors([
                    'error' => 'No tenant contact is linked to this Steam account.',
                ]);
            }

            Log::debug('Steam tenant contact located.', [
                'steam_id' => $steamId,
                'contact_id' => $contact->id,
                'tenant_id' => $contact->tenant_id,
            ]);

            $user = User::updateOrCreate([
                'steam_id' => $steamId,
            ], [
                'name' => $contact->name ?: 'Steam User',
                'email' => $contact->email ?: 'steam-'.$steamId.'@auth.local',
            ]);

            Log::debug('Steam user record synchronised.', [
                'user_id' => $user->id,
                'steam_id' => $steamId,
            ]);

            $user->tenant_contact_id = $contact->id;
            $user->save();

            $group = Group::firstWhere('slug', 'tenant-contact');
            if ($group && ! $user->groups()->whereKey($group->id)->exists()) {
                $user->groups()->attach($group->id);
                Log::debug('Tenant-contact group attached to Steam user.', [
                    'user_id' => $user->id,
                    'group_id' => $group->id,
                ]);
            }

            Auth::login($user, true);

            if ($contact->tenant_id) {
                Session::put('tenant_id', $contact->tenant_id);
                Log::debug('Tenant context stored in session after Steam login.', [
                    'user_id' => $user->id,
                    'tenant_id' => $contact->tenant_id,
                ]);
            }

            Log::info('Steam login completed successfully.', [
                'user_id' => $user->id,
                'steam_id' => $steamId,
            ]);

            return Redirect::route('tenants.pages.show', ['page' => 'overview']);
        } catch (\Exception $e) {
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

    public function logout()
    {
        Auth::logout();
        Session::forget('tenant_id');

        return Redirect::to('/');
    }
}
