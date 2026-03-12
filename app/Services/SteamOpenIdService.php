<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SteamOpenIdService
{
    public function getAuthenticationUrl(string $returnTo, string $realm): string
    {
        return 'https://steamcommunity.com/openid/login?'.http_build_query([
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
            'openid.mode' => 'checkid_setup',
            'openid.return_to' => $returnTo,
            'openid.realm' => $realm,
            'openid.identity' => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
        ]);
    }

    public function validateAndExtractSteamId(array $query): ?string
    {
        $params = $this->extractOpenIdParams($query);

        $claimedId = $params['openid.claimed_id'] ?? null;
        if (! is_string($claimedId) || $claimedId === '') {
            return null;
        }

        $verification = $params;
        $verification['openid.mode'] = 'check_authentication';

        $response = Http::asForm()
            ->timeout(20)
            ->post('https://steamcommunity.com/openid/login', $verification);

        if (! $response->successful()) {
            return null;
        }

        if (! Str::contains($response->body(), 'is_valid:true')) {
            return null;
        }

        if (! preg_match('#^https?://steamcommunity\.com/openid/id/(\d{17,25})$#', $claimedId, $matches)) {
            return null;
        }

        return $matches[1];
    }

    private function extractOpenIdParams(array $query): array
    {
        $normalized = [];

        foreach ($query as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            if (str_starts_with($key, 'openid.')) {
                $normalized[$key] = $value;
                continue;
            }

            if (str_starts_with($key, 'openid_')) {
                $normalized['openid.'.substr($key, 7)] = $value;
            }
        }

        return $normalized;
    }
}
