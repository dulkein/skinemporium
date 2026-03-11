<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Support\CsMarketTaxonomy;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MarketplaceController extends Controller
{
    /**
     * Show the homepage.
     */
    public function home()
    {
        $listings = $this->getListings()
            ->take(3)
            ->values()
            ->all();

        return view('market.home', [
            'featuredListings' => $listings,
        ]);
    }

    /**
     * Show all market listings with category + weapon filtering.
     */
    public function index(Request $request)
    {
        $allListings = $this->getListings()->values();
        $search = trim((string) $request->query('q', ''));
        $selectedCategory = CsMarketTaxonomy::normalizeCategory((string) $request->query('category', 'all'));

        $availableWeapons = $allListings
            ->when($selectedCategory !== 'all', fn ($collection) => $collection->where('category', $selectedCategory))
            ->pluck('weapon')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $selectedWeapon = trim((string) $request->query('weapon', ''));
        if ($selectedWeapon !== '' && ! $availableWeapons->contains($selectedWeapon)) {
            $selectedWeapon = '';
        }

        $filteredListings = $allListings
            ->when($selectedCategory !== 'all', fn ($collection) => $collection->where('category', $selectedCategory))
            ->when($selectedWeapon !== '', fn ($collection) => $collection->where('weapon', $selectedWeapon))
            ->when($search !== '', function ($collection) use ($search) {
                $needle = Str::lower($search);

                return $collection->filter(function (array $listing) use ($needle) {
                    $haystack = Str::lower(implode(' ', [
                        $listing['name'] ?? '',
                        $listing['condition'] ?? '',
                        $listing['weapon'] ?? '',
                        $listing['category'] ?? '',
                    ]));

                    return Str::contains($haystack, $needle);
                });
            })
            ->values();

        $categories = CsMarketTaxonomy::categories();
        $categoryCounts = ['all' => $allListings->count()];

        foreach (array_keys($categories) as $key) {
            if ($key === 'all') {
                continue;
            }

            $categoryCounts[$key] = $allListings->where('category', $key)->count();
        }

        $categoryLinks = [];

        foreach (array_keys($categories) as $key) {
            $params = [];

            if ($key !== 'all') {
                $params['category'] = $key;
            }

            if ($search !== '') {
                $params['q'] = $search;
            }

            $categoryLinks[$key] = route('market.index', $params);
        }

        $weaponLinks = [];

        if ($selectedCategory !== 'all') {
            $baseParams = ['category' => $selectedCategory];

            if ($search !== '') {
                $baseParams['q'] = $search;
            }

            $weaponLinks['__all'] = route('market.index', $baseParams);

            foreach ($availableWeapons as $weapon) {
                $params = $baseParams;
                $params['weapon'] = $weapon;
                $weaponLinks[$weapon] = route('market.index', $params);
            }
        }

        return view('market.index', [
            'listings' => $filteredListings->all(),
            'categories' => $categories,
            'categoryCounts' => $categoryCounts,
            'categoryLinks' => $categoryLinks,
            'selectedCategory' => $selectedCategory,
            'availableWeapons' => $availableWeapons->all(),
            'selectedWeapon' => $selectedWeapon,
            'weaponLinks' => $weaponLinks,
            'search' => $search,
            'totalCount' => $allListings->count(),
        ]);
    }

    /**
     * Show one listing by id.
     */
    public function show(int $id)
    {
        $databaseListing = Listing::with(['skin', 'seller'])
            ->find($id);

        if ($databaseListing) {
            $listing = $this->mapDatabaseListing($databaseListing);

            return view('market.show', [
                'listing' => $listing,
            ]);
        }

        $listing = collect(config('marketplace.listings'))
            ->map(fn (array $item) => $this->mapConfigListing($item))
            ->firstWhere('id', $id);

        abort_if(! $listing, 404);

        return view('market.show', [
            'listing' => $listing,
        ]);
    }

    private function getListings()
    {
        $databaseListings = Listing::with(['skin', 'seller'])
            ->whereIn('status', ['active', 'listed'])
            ->orderByDesc('listed_at')
            ->orderByDesc('id')
            ->get();

        if ($databaseListings->isNotEmpty()) {
            return $databaseListings->map(fn (Listing $listing) => $this->mapDatabaseListing($listing));
        }

        return collect(config('marketplace.listings'))
            ->map(fn (array $listing) => $this->mapConfigListing($listing));
    }

    private function mapDatabaseListing(Listing $listing): array
    {
        $name = $listing->skin?->market_hash_name ?? 'Unknown skin';
        $weapon = CsMarketTaxonomy::normalizeWeaponName(
            $listing->skin?->weapon_name ?: CsMarketTaxonomy::weaponFromMarketHashName($name)
        );
        $defIndex = is_numeric(data_get($listing->metadata, 'item.def_index'))
            ? (int) data_get($listing->metadata, 'item.def_index')
            : null;
        $calculatedCategory = CsMarketTaxonomy::categoryFromItem($name, $weapon, $defIndex);
        $storedCategory = CsMarketTaxonomy::normalizeDataCategory($listing->skin?->market_category);
        $category = $calculatedCategory !== 'misc' ? $calculatedCategory : $storedCategory;
        $condition = $this->normalizeCondition(
            $listing->condition
            ?: data_get($listing->metadata, 'item.exterior')
            ?: data_get($listing->metadata, 'wear_name')
            ?: $this->extractWearFromMarketName($name)
        );
        $rarity = $this->normalizeRarity(
            $listing->skin?->rarity
            ?: data_get($listing->skin?->metadata, 'rarity_name')
            ?: data_get($listing->skin?->metadata, 'rarity')
        );

        return [
            'id' => $listing->id,
            'name' => $name,
            'weapon' => $weapon,
            'category' => $category,
            'category_label' => CsMarketTaxonomy::DATA_CATEGORY_LABELS[$category]
                ?? ucfirst(str_replace('_', ' ', $category)),
            'condition' => $condition,
            'rarity' => $rarity,
            'price_usd' => (float) $listing->price_usd,
            'float' => (float) ($listing->float_value ?? 0),
            'image' => $listing->skin?->image_url ?? 'https://placehold.co/640x360?text=No+Image',
            'seller' => $listing->seller?->name ?? 'Market Seller',
        ];
    }

    private function mapConfigListing(array $listing): array
    {
        $name = (string) ($listing['name'] ?? 'Unknown skin');
        $weapon = CsMarketTaxonomy::normalizeWeaponName(CsMarketTaxonomy::weaponFromMarketHashName($name));
        $category = CsMarketTaxonomy::categoryFromItem($name, $weapon, null);
        $condition = $this->normalizeCondition(
            isset($listing['condition']) ? (string) $listing['condition'] : $this->extractWearFromMarketName($name)
        );
        $rarity = $this->normalizeRarity(isset($listing['rarity']) ? (string) $listing['rarity'] : null);

        return $listing + [
            'weapon' => $weapon,
            'category' => $category,
            'category_label' => CsMarketTaxonomy::DATA_CATEGORY_LABELS[$category]
                ?? ucfirst(str_replace('_', ' ', $category)),
            'condition' => $condition,
            'rarity' => $rarity,
        ];
    }

    private function extractWearFromMarketName(string $marketName): ?string
    {
        if (preg_match('/\((Factory New|Minimal Wear|Field-Tested|Well-Worn|Battle-Scarred)\)\s*$/i', $marketName, $matches)) {
            return $this->normalizeCondition($matches[1]);
        }

        return null;
    }

    private function normalizeCondition(?string $condition): ?string
    {
        if (! is_string($condition)) {
            return null;
        }

        $value = trim($condition);
        if ($value === '') {
            return null;
        }

        $normalized = strtolower(str_replace(['_', '-'], [' ', '-'], $value));

        return match ($normalized) {
            'fn', 'factory new' => 'Factory New',
            'mw', 'minimal wear' => 'Minimal Wear',
            'ft', 'field-tested', 'field tested' => 'Field-Tested',
            'ww', 'well-worn', 'well worn' => 'Well-Worn',
            'bs', 'battle-scarred', 'battle scarred' => 'Battle-Scarred',
            'unknown', 'n/a', 'na', '-' => null,
            default => $value,
        };
    }

    private function normalizeRarity(?string $rarity): ?string
    {
        if (! is_string($rarity)) {
            return null;
        }

        $value = trim($rarity);
        if ($value === '' || is_numeric($value)) {
            return null;
        }

        $normalized = strtolower(str_replace(['_', '-'], ' ', $value));
        $normalized = preg_replace('/\s+/', ' ', (string) $normalized);

        if (in_array($normalized, ['unknown', 'n/a', 'na', '-'], true)) {
            return null;
        }

        return ucwords((string) $normalized);
    }
}
