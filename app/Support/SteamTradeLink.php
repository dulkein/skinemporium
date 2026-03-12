<?php

namespace App\Support;

class SteamTradeLink
{
    private const STEAM_ID64_BASE = 76561197960265728;

    public static function parse(?string $url): ?array
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $parts = parse_url(trim($url));

        if (! is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $host = strtolower((string) $parts['host']);
        if (! in_array($host, ['steamcommunity.com', 'www.steamcommunity.com'], true)) {
            return null;
        }

        $path = (string) ($parts['path'] ?? '');
        if (! in_array(rtrim($path, '/'), ['/tradeoffer/new'], true)) {
            return null;
        }

        parse_str((string) ($parts['query'] ?? ''), $query);

        $partner = $query['partner'] ?? null;
        $token = $query['token'] ?? null;

        if (! is_scalar($partner) || ! ctype_digit((string) $partner)) {
            return null;
        }

        if (! is_string($token) || trim($token) === '') {
            return null;
        }

        $partnerId = (int) $partner;
        if ($partnerId <= 0) {
            return null;
        }

        $steamId = (string) ($partnerId + self::STEAM_ID64_BASE);

        return [
            'partner_id' => (string) $partnerId,
            'token' => trim($token),
            'steam_id' => $steamId,
            'canonical_url' => 'https://steamcommunity.com/tradeoffer/new/?partner='.$partnerId.'&token='.trim($token),
        ];
    }
}
