<?php

namespace App\Services;

use Throwable;
use function config;

class SteamOpenIdService
{
    private const OPENID_ENDPOINT = 'https://steamcommunity.com/openid/login';

    protected \Illuminate\Contracts\Routing\UrlGenerator $url;

    public function __construct(\Illuminate\Contracts\Routing\UrlGenerator $url)
    {
        $this->url = $url;
    }

    /**
     * Build the Steam OpenID login URL.
     */
    public function getRedirectUrl(\Illuminate\Http\Request $request): string
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

    private function buildReturnUrl(\Illuminate\Http\Request $request): string
    {
        $path = $this->url->route('login.steam.callback', [], false);
        $scheme = $this->resolveScheme($request);
        $host = $this->resolveHost($request);
        $port = $this->resolvePort($request, $scheme);

        $authority = rtrim($host, '/');

        if ($port !== null) {
            $authority .= ':'.$port;
        }

        return $scheme.'://'.$authority.$path;
    }

    private function determineRealm(\Illuminate\Http\Request $request, string $returnTo): string
    {
        $parsed = parse_url($returnTo);

        if (! $parsed || ! isset($parsed['scheme'], $parsed['host'])) {
            return rtrim($this->url->to('/'), '/');
        }

        $scheme = $parsed['scheme'];
        $realm = $scheme.'://'.$parsed['host'];

        if (isset($parsed['port']) && ! $this->isStandardPort($scheme, (int) $parsed['port'])) {
            $realm .= ':'.$parsed['port'];
        }

        return $realm;
    }

    /**
     * Validate the Steam OpenID response and return the 64-bit Steam ID.
     */
    public function validate(\Illuminate\Http\Request $request): ?string
    {
        $params = [];

        foreach ($request->all() as $key => $value) {
            if (\Illuminate\Support\Str::startsWith($key, 'openid.')) {
                $params[$key] = $value;
            }
        }

        if ($params === []) {
            return null;
        }

        $params['openid.mode'] = 'check_authentication';

        try {
            $response = \Illuminate\Support\Facades\Http::asForm()
                ->retry(2, 200)
                ->withHeaders([
                    'User-Agent' => config('app.name', 'Laravel').' SteamOpenID/1.0',
                    'Accept' => 'text/plain',
                ])->post(self::OPENID_ENDPOINT, $params);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            \Illuminate\Support\Facades\Log::warning('Steam OpenID validation failed to connect.', ['exception' => $e]);

            return null;
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Steam OpenID validation encountered an unexpected error.', ['exception' => $e]);

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        if (! \Illuminate\Support\Str::contains($response->body(), 'is_valid:true')) {
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

    private function resolveScheme(\Illuminate\Http\Request $request): string
    {
        if ($forwardedProto = $request->headers->get('X-Forwarded-Proto')) {
            return strtolower(trim(explode(',', $forwardedProto)[0]));
        }

        return $request->getScheme() ?: 'https';
    }

    private function resolveHost(\Illuminate\Http\Request $request): string
    {
        if ($forwardedHost = $request->headers->get('X-Forwarded-Host')) {
            return trim(explode(',', $forwardedHost)[0]);
        }

        $host = $request->getHost();

        if ($host) {
            return $host;
        }

        return parse_url($this->url->to('/'), PHP_URL_HOST) ?? 'localhost';
    }

    private function resolvePort(\Illuminate\Http\Request $request, string $scheme): ?int
    {
        if ($forwardedPort = $request->headers->get('X-Forwarded-Port')) {
            $port = (int) trim(explode(',', $forwardedPort)[0]);

            return $this->isStandardPort($scheme, $port) ? null : $port;
        }

        $port = $request->getPort();

        if ($port && ! $this->isStandardPort($scheme, $port)) {
            return $port;
        }

        $configured = parse_url($this->url->to('/'), PHP_URL_PORT);

        if ($configured && ! $this->isStandardPort($scheme, (int) $configured)) {
            return (int) $configured;
        }

        return null;
    }

    private function isStandardPort(string $scheme, int $port): bool
    {
        return ($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80);
    }
}
