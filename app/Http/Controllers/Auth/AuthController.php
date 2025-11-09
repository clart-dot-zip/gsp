<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\TenantContact;
use App\Models\User;
use App\Services\SteamOpenIdService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function redirectToSteam()
    {
        return Redirect::away($this->steamOpenId->getRedirectUrl());
    }

    public function handleSteamCallback(Request $request)
    {
        try {
            $steamId = $this->steamOpenId->validate($request);

            if (! $steamId) {
                return Redirect::route('login')->withErrors([
                    'error' => 'Steam authentication could not be validated.',
                ]);
            }

            $contact = TenantContact::where('steam_id', $steamId)->with('tenant')->first();

            if (! $contact) {
                return Redirect::route('login')->withErrors([
                    'error' => 'No tenant contact is linked to this Steam account.',
                ]);
            }

            $user = User::updateOrCreate([
                'steam_id' => $steamId,
            ], [
                'name' => $contact->name ?: 'Steam User',
                'email' => $contact->email ?: 'steam-'.$steamId.'@auth.local',
            ]);

            $user->tenant_contact_id = $contact->id;
            $user->save();

            $group = Group::firstWhere('slug', 'tenant-contact');
            if ($group && ! $user->groups()->whereKey($group->id)->exists()) {
                $user->groups()->attach($group->id);
            }

            Auth::login($user, true);

            if ($contact->tenant_id) {
                Session::put('tenant_id', $contact->tenant_id);
            }

            return Redirect::route('tenants.pages.show', ['page' => 'overview']);
        } catch (\Exception $e) {
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
