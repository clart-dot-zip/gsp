<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Routing\UrlGenerator;

class SteamOpenIdService
{
    private const OPENID_ENDPOINT = 'https://steamcommunity.com/openid/login';

    protected UrlGenerator $url;

    public function __construct(UrlGenerator $url)
    {
        $this->url = $url;
    }

    /**
     * Build the Steam OpenID login URL.
     */
    public function getRedirectUrl(Request $request): string
    {
        $returnTo = $this->buildReturnUrl($request);
        $realm = $this->determineRealm($request, $returnTo);

        $params = [
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
            'openid.mode' => 'checkid_setup',
            'openid.return_to' => $returnTo,
            'openid.realm' => rtrim($realm, '/'),
            'openid.identity' => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
        ];

        return self::OPENID_ENDPOINT.'?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    private function buildReturnUrl(Request $request): string
    {
        $relative = $this->url->route('login.steam.callback', [], false);

        return rtrim($request->getSchemeAndHttpHost(), '/').$relative;
    }

    private function determineRealm(Request $request, string $returnTo): string
    {
        $host = $request->getSchemeAndHttpHost();

        if ($host) {
            return $host;
        }

        $parsed = parse_url($returnTo);

        if (! $parsed || ! isset($parsed['scheme'], $parsed['host'])) {
            return $this->url->to('/');
        }

        $realm = $parsed['scheme'].'://'.$parsed['host'];

        if (isset($parsed['port'])) {
            $realm .= ':'.$parsed['port'];
        }

        return $realm;
    }

    /**
     * Validate the Steam OpenID response and return the 64-bit Steam ID.
     */
    public function validate(Request $request): ?string
    {
        $params = [];

        foreach ($request->all() as $key => $value) {
            if (Str::startsWith($key, 'openid.')) {
                $params[$key] = $value;
            }
        }

        if ($params === []) {
            return null;
        }

        $params['openid.mode'] = 'check_authentication';

        $response = Http::asForm()->post(self::OPENID_ENDPOINT, $params);

        if (! $response->successful()) {
            return null;
        }

        if (! Str::contains($response->body(), 'is_valid:true')) {
            return null;
        }

        $claimedId = $request->input('openid.claimed_id') ?: $request->input('openid.identity');

        if (! $claimedId) {
            return null;
        }

        if (! preg_match('/\d+$/', $claimedId, $matches)) {
            return null;
        }

        return $matches[0] ?? null;
    }
}
