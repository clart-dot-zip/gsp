<?php

namespace App\Services;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use function config;

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

        Log::debug('Steam OpenID redirect URL composed.', [
            'return_to' => $returnTo,
            'realm' => $realm,
        ]);

        return self::OPENID_ENDPOINT.'?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    private function buildReturnUrl(Request $request): string
    {
        $path = $this->url->route('login.steam.callback', [], false);
        $scheme = $this->resolveScheme($request);
        $host = $this->resolveHost($request);
        $port = $this->resolvePort($request, $scheme);

        $authority = rtrim($host, '/');

        if ($port !== null) {
            $authority .= ':'.$port;
        }

        $returnUrl = $scheme.'://'.$authority.$path;

        Log::debug('Steam OpenID return URL constructed.', [
            'scheme' => $scheme,
            'host' => $host,
            'port' => $port,
            'return_url' => $returnUrl,
        ]);

        return $returnUrl;
    }

    private function determineRealm(Request $request, string $returnTo): string
    {
        $parsed = parse_url($returnTo);

        if (! $parsed || ! isset($parsed['scheme'], $parsed['host'])) {
            $fallback = rtrim($this->url->to('/'), '/');
            Log::debug('Steam OpenID realm fell back to base URL.', [
                'fallback' => $fallback,
            ]);

            return $fallback;
        }

        $scheme = $parsed['scheme'];
        $realm = $scheme.'://'.$parsed['host'];

        if (isset($parsed['port']) && ! $this->isStandardPort($scheme, (int) $parsed['port'])) {
            $realm .= ':'.$parsed['port'];
        }

        Log::debug('Steam OpenID realm determined.', [
            'realm' => $realm,
            'parsed' => [
                'scheme' => $parsed['scheme'] ?? null,
                'host' => $parsed['host'] ?? null,
                'port' => $parsed['port'] ?? null,
            ],
        ]);

        return $realm;
    }

    /**
     * Validate the Steam OpenID response and return the 64-bit Steam ID.
     */
    public function validate(Request $request): ?string
    {
        $params = $this->extractOpenIdParameters($request);

        Log::debug('Steam OpenID callback parameters normalised.', [
            'normalized_keys' => array_keys($params),
            'original_keys' => array_keys($request->all()),
        ]);

        if ($params === []) {
            Log::warning('Steam OpenID callback contained no OpenID parameters.', [
                'query' => $request->query(),
            ]);

            return null;
        }

        $params['openid.mode'] = 'check_authentication';

        try {
            $response = Http::asForm()
                ->retry(2, 200)
                ->withHeaders([
                    'User-Agent' => config('app.name', 'Laravel').' SteamOpenID/1.0',
                    'Accept' => 'text/plain',
                ])->post(self::OPENID_ENDPOINT, $params);

            Log::debug('Steam OpenID validation request dispatched.', [
                'status' => $response->status(),
            ]);
        } catch (ConnectionException $e) {
            Log::warning('Steam OpenID validation failed to connect.', ['exception' => $e->getMessage()]);

            return null;
        } catch (Throwable $e) {
            Log::warning('Steam OpenID validation encountered an unexpected error.', ['exception' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Steam OpenID response was not successful.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        if (! Str::contains($response->body(), 'is_valid:true')) {
            Log::debug('Steam OpenID response missing is_valid:true flag.', [
                'body' => $response->body(),
            ]);

            return null;
        }

        $claimedId = $request->input('openid.claimed_id') ?: $request->input('openid.identity');

        if (! $claimedId) {
            Log::debug('Steam OpenID payload missing claimed ID.', []);

            return null;
        }

        if (! preg_match('/\d+$/', $claimedId, $matches)) {
            Log::debug('Steam OpenID claimed ID did not end with digits.', [
                'claimed_id' => $claimedId,
            ]);

            return null;
        }

        $steamId = $matches[0];

        Log::debug('Steam OpenID validation extracted Steam ID.', [
            'steam_id' => $steamId,
        ]);

        return $steamId;
    }

    private function extractOpenIdParameters(Request $request): array
    {
        $params = [];

        foreach ($request->all() as $key => $value) {
            if (! Str::startsWith($key, 'openid')) {
                continue;
            }

            $normalizedKey = str_replace('_', '.', $key);
            $params[$normalizedKey] = $value;
        }

        return $params;
    }

    private function resolveScheme(Request $request): string
    {
        if ($forwardedProto = $request->headers->get('X-Forwarded-Proto')) {
            $scheme = strtolower(trim(explode(',', $forwardedProto)[0]));
        } else {
            $scheme = $request->getScheme() ?: 'https';
        }

        Log::debug('Steam OpenID scheme resolved.', [
            'scheme' => $scheme,
            'forwarded_proto' => $request->headers->get('X-Forwarded-Proto'),
        ]);

        return $scheme;
    }

    private function resolveHost(Request $request): string
    {
        $forwardedHost = $request->headers->get('X-Forwarded-Host');
        $host = null;
        $source = 'fallback';

        if ($forwardedHost) {
            $host = trim(explode(',', $forwardedHost)[0]);
            $source = 'forwarded-host';
        } else {
            $host = $request->getHost();
            if ($host) {
                $source = 'request';
            } else {
                $host = parse_url($this->url->to('/'), PHP_URL_HOST) ?? 'localhost';
            }
        }

        Log::debug('Steam OpenID host resolved.', [
            'host' => $host,
            'source' => $source,
            'forwarded_host' => $forwardedHost,
        ]);

        return $host;
    }

    private function resolvePort(Request $request, string $scheme): ?int
    {
        if ($forwardedPort = $request->headers->get('X-Forwarded-Port')) {
            $port = (int) trim(explode(',', $forwardedPort)[0]);
            $normalized = $this->isStandardPort($scheme, $port) ? null : $port;

            Log::debug('Steam OpenID port resolved from forwarded header.', [
                'raw_port' => $port,
                'normalized_port' => $normalized,
            ]);

            return $normalized;
        }

        $port = $request->getPort();

        if ($port && ! $this->isStandardPort($scheme, $port)) {
            Log::debug('Steam OpenID port resolved from request.', [
                'port' => $port,
            ]);

            return $port;
        }

        $configured = parse_url($this->url->to('/'), PHP_URL_PORT);

        if ($configured && ! $this->isStandardPort($scheme, (int) $configured)) {
            Log::debug('Steam OpenID port resolved from app URL.', [
                'configured_port' => (int) $configured,
            ]);

            return (int) $configured;
        }

        return null;
    }

    private function isStandardPort(string $scheme, int $port): bool
    {
        return ($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80);
    }
}
