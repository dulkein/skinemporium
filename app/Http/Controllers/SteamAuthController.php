<?php

namespace App\Http\Controllers;

use App\Services\SteamOpenIdService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SteamAuthController extends Controller
{
    public function redirect(SteamOpenIdService $openIdService)
    {
        $returnTo = (string) config('services.steam.openid_return_to', route('steam.callback'));
        $realm = (string) config('services.steam.openid_realm', config('app.url'));

        $url = $openIdService->getAuthenticationUrl($returnTo, $realm);

        return redirect()->away($url);
    }

    public function callback(Request $request, SteamOpenIdService $openIdService)
    {
        $steamId = $openIdService->validateAndExtractSteamId($request->query());

        if (! $steamId) {
            return redirect()
                ->route('sell.create')
                ->with('status', 'Steam login failed. Please try again.');
        }

        $profile = $this->fetchSteamProfile($steamId);

        $user = User::firstOrCreate(
            ['steam_id' => $steamId],
            [
                'name' => $profile['name'] ?? 'Steam User '.substr($steamId, -6),
                'email' => "steam_{$steamId}@skinemporium.local",
                'password' => Str::random(40),
                'avatar_url' => $profile['avatar_url'] ?? null,
            ]
        );

        $updates = [];

        if (($profile['name'] ?? null) && $user->name !== $profile['name']) {
            $updates['name'] = $profile['name'];
        }

        if (($profile['avatar_url'] ?? null) && $user->avatar_url !== $profile['avatar_url']) {
            $updates['avatar_url'] = $profile['avatar_url'];
        }

        if (! empty($updates)) {
            $user->update($updates);
        }

        $request->session()->put('steam_user', [
            'steam_id' => $steamId,
            'name' => $user->name,
            'avatar_url' => $user->avatar_url,
        ]);

        return redirect()
            ->route('sell.create')
            ->with('status', 'Steam account connected successfully.');
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['steam_user', 'sell_inventory_assets']);

        return redirect()
            ->route('sell.create')
            ->with('status', 'Steam account disconnected.');
    }

    private function fetchSteamProfile(string $steamId): array
    {
        $apiKey = (string) config('services.steam.web_api_key', '');

        if ($apiKey === '') {
            return [];
        }

        $response = Http::acceptJson()
            ->timeout(15)
            ->get('https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/', [
                'key' => $apiKey,
                'steamids' => $steamId,
            ]);

        if (! $response->successful()) {
            return [];
        }

        $player = data_get($response->json(), 'response.players.0');

        if (! is_array($player)) {
            return [];
        }

        return [
            'name' => isset($player['personaname']) ? trim((string) $player['personaname']) : null,
            'avatar_url' => isset($player['avatarfull']) ? trim((string) $player['avatarfull']) : null,
        ];
    }
}
