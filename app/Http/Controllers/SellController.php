<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Skin;
use App\Models\User;
use App\Services\SteamInventoryService;
use App\Support\CsMarketTaxonomy;
use App\Support\SteamTradeLink;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SellController extends Controller
{
    /**
     * Show the Sell page with Steam integration steps.
     */
    public function create(Request $request, SteamInventoryService $inventoryService)
    {
        $steamUser = $request->session()->get('steam_user');
        $connectedUser = null;
        $tradeLink = null;
        $tradeLinkInfo = null;
        $tradeLinkError = null;
        $inventoryItems = [];
        $inventoryError = null;

        if (is_array($steamUser) && ! empty($steamUser['steam_id'])) {
            $steamId = (string) $steamUser['steam_id'];
            $connectedUser = User::query()->where('steam_id', $steamId)->first();
            $tradeLink = $connectedUser?->steam_trade_url;

            if (is_string($tradeLink) && trim($tradeLink) !== '') {
                $tradeLinkInfo = SteamTradeLink::parse($tradeLink);

                if (! $tradeLinkInfo) {
                    $tradeLinkError = 'Saved trade link looks invalid. Please update it.';
                } elseif ($tradeLinkInfo['steam_id'] !== $steamId) {
                    $tradeLinkError = 'Trade link Steam account does not match your connected Steam profile.';
                } else {
                    try {
                        $inventoryItems = $inventoryService->fetchTradableCs2Items($steamId);
                    } catch (\Throwable $e) {
                        $inventoryError = $e->getMessage();
                    }
                }
            }
        }

        $request->session()->put(
            'sell_inventory_assets',
            collect($inventoryItems)
                ->keyBy('asset_id')
                ->all()
        );

        return view('sell.create', [
            'steamUser' => $steamUser,
            'connectedUser' => $connectedUser,
            'tradeLink' => $tradeLink,
            'tradeLinkInfo' => $tradeLinkInfo,
            'tradeLinkError' => $tradeLinkError,
            'inventoryItems' => $inventoryItems,
            'inventoryError' => $inventoryError,
        ]);
    }

    /**
     * Save or update the trade link for the connected Steam account.
     */
    public function updateTradeLink(Request $request)
    {
        $steamUser = $request->session()->get('steam_user');

        if (! is_array($steamUser) || empty($steamUser['steam_id'])) {
            return redirect()
                ->route('sell.create')
                ->with('status', 'Connect Steam first before adding a trade link.');
        }

        $validated = $request->validate([
            'trade_link' => ['required', 'url', 'max:255'],
        ]);

        $parsed = SteamTradeLink::parse($validated['trade_link']);

        if (! $parsed) {
            return redirect()
                ->route('sell.create')
                ->withErrors(['trade_link' => 'Invalid Steam trade link format.'])
                ->withInput();
        }

        $steamId = (string) $steamUser['steam_id'];

        if ($parsed['steam_id'] !== $steamId) {
            return redirect()
                ->route('sell.create')
                ->withErrors(['trade_link' => 'This trade link belongs to a different Steam account.'])
                ->withInput();
        }

        $user = $this->upsertSteamUser($steamUser);

        $user->update([
            'steam_trade_url' => $parsed['canonical_url'],
        ]);

        return redirect()
            ->route('sell.create')
            ->with('status', 'Trade link saved. Inventory is now ready to load.');
    }

    /**
     * Inventory endpoint for frontend/AJAX usage.
     */
    public function inventory(Request $request, SteamInventoryService $inventoryService)
    {
        $steamUser = $request->session()->get('steam_user');

        if (! is_array($steamUser) || empty($steamUser['steam_id'])) {
            return response()->json([
                'ok' => false,
                'message' => 'Steam account is not connected.',
                'items' => [],
            ], 401);
        }

        $steamId = (string) $steamUser['steam_id'];
        $user = User::query()->where('steam_id', $steamId)->first();

        if (! $user || ! $user->steam_trade_url) {
            return response()->json([
                'ok' => false,
                'message' => 'Trade link is missing.',
                'items' => [],
            ], 422);
        }

        $parsed = SteamTradeLink::parse($user->steam_trade_url);

        if (! $parsed || $parsed['steam_id'] !== $steamId) {
            return response()->json([
                'ok' => false,
                'message' => 'Saved trade link is invalid for this account.',
                'items' => [],
            ], 422);
        }

        try {
            $items = $inventoryService->fetchTradableCs2Items($steamId);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
                'items' => [],
            ], 502);
        }

        $request->session()->put('sell_inventory_assets', collect($items)->keyBy('asset_id')->all());

        return response()->json([
            'ok' => true,
            'count' => count($items),
            'items' => $items,
        ]);
    }

    /**
     * Create a listing from selected Steam inventory asset.
     */
    public function store(Request $request)
    {
        $steamUser = $request->session()->get('steam_user');

        if (! is_array($steamUser) || empty($steamUser['steam_id'])) {
            return redirect()
                ->route('sell.create')
                ->with('status', 'Connect Steam first before listing an item.');
        }

        $validated = $request->validate([
            'selected_asset_id' => ['required', 'string'],
            'price_usd' => ['required', 'numeric', 'min:0.5'],
        ]);

        $inventoryAssets = $request->session()->get('sell_inventory_assets', []);
        $selected = $inventoryAssets[$validated['selected_asset_id']] ?? null;

        if (! is_array($selected)) {
            return redirect()
                ->route('sell.create')
                ->withErrors(['selected_asset_id' => 'Selected item is not available. Refresh and try again.'])
                ->withInput();
        }

        $user = $this->upsertSteamUser($steamUser);

        $parsedTradeLink = SteamTradeLink::parse($user->steam_trade_url);
        if (! $parsedTradeLink || $parsedTradeLink['steam_id'] !== (string) $steamUser['steam_id']) {
            return redirect()
                ->route('sell.create')
                ->withErrors(['trade_link' => 'Please save a valid Steam trade link before listing.']);
        }

        $marketHashName = (string) ($selected['market_hash_name'] ?? '');
        if ($marketHashName === '') {
            return redirect()
                ->route('sell.create')
                ->withErrors(['selected_asset_id' => 'Selected item data is incomplete.']);
        }

        $weaponName = CsMarketTaxonomy::normalizeWeaponName(
            $selected['weapon'] ?? CsMarketTaxonomy::weaponFromMarketHashName($marketHashName)
        );

        $skin = Skin::updateOrCreate(
            ['market_hash_name' => $marketHashName],
            [
                'weapon_name' => $weaponName,
                'skin_name' => $this->extractSkinName($marketHashName),
                'market_category' => CsMarketTaxonomy::normalizeDataCategory(
                    $selected['category'] ?? CsMarketTaxonomy::categoryFromItem($marketHashName, $weaponName)
                ),
                'rarity' => isset($selected['rarity']) && is_string($selected['rarity'])
                    ? trim($selected['rarity'])
                    : null,
                'image_url' => (string) ($selected['image_url'] ?? ''),
                'external_item_id' => (string) ($selected['class_id'] ?? ''),
                'metadata' => [
                    'inventory_asset' => $selected,
                ],
            ]
        );

        $listing = Listing::create([
            'external_id' => null,
            'source' => 'internal',
            'seller_id' => $user->id,
            'skin_id' => $skin->id,
            'condition' => isset($selected['condition']) && is_string($selected['condition'])
                ? trim($selected['condition'])
                : null,
            'float_value' => null,
            'price_usd' => round((float) $validated['price_usd'], 2),
            'inspect_link' => null,
            'listing_url' => null,
            'status' => 'active',
            'listed_at' => now(),
            'metadata' => [
                'inventory_asset' => $selected,
                'trade_link' => $parsedTradeLink['canonical_url'],
            ],
        ]);

        return redirect()
            ->route('sell.success')
            ->with('submitted_listing', [
                'listing_id' => $listing->id,
                'item_name' => $marketHashName,
                'weapon' => $weaponName,
                'category' => CsMarketTaxonomy::DATA_CATEGORY_LABELS[
                    CsMarketTaxonomy::normalizeDataCategory($selected['category'] ?? 'misc')
                ] ?? 'Item',
                'condition' => $selected['condition'] ?? null,
                'price_usd' => (float) $listing->price_usd,
                'image_url' => $selected['image_url'] ?? null,
            ]);
    }

    /**
     * Show a success page after form submission.
     */
    public function success(Request $request)
    {
        $listing = $request->session()->get('submitted_listing');

        if (! $listing) {
            return redirect()->route('sell.create');
        }

        return view('sell.success', [
            'listing' => $listing,
        ]);
    }

    private function upsertSteamUser(array $steamUser): User
    {
        $steamId = (string) ($steamUser['steam_id'] ?? '');

        $user = User::firstOrCreate(
            ['steam_id' => $steamId],
            [
                'name' => (string) ($steamUser['name'] ?? ('Steam User '.substr($steamId, -6))),
                'email' => "steam_{$steamId}@skinemporium.local",
                'password' => Str::random(40),
                'avatar_url' => isset($steamUser['avatar_url']) && is_string($steamUser['avatar_url'])
                    ? trim($steamUser['avatar_url'])
                    : null,
            ]
        );

        $updates = [];

        $sessionName = isset($steamUser['name']) && is_string($steamUser['name']) ? trim($steamUser['name']) : '';
        if ($sessionName !== '' && $sessionName !== $user->name) {
            $updates['name'] = $sessionName;
        }

        $sessionAvatar = isset($steamUser['avatar_url']) && is_string($steamUser['avatar_url'])
            ? trim($steamUser['avatar_url'])
            : '';

        if ($sessionAvatar !== '' && $sessionAvatar !== $user->avatar_url) {
            $updates['avatar_url'] = $sessionAvatar;
        }

        if (! empty($updates)) {
            $user->update($updates);
        }

        return $user;
    }

    private function extractSkinName(string $marketHashName): ?string
    {
        $withoutWear = preg_replace('/\s*\(([^()]*)\)\s*$/', '', trim($marketHashName));
        $parts = explode(' | ', (string) $withoutWear, 2);

        return isset($parts[1]) ? trim($parts[1]) : null;
    }
}
