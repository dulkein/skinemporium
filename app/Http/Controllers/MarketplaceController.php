<?php

namespace App\Http\Controllers;

use App\Models\Listing;

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
     * Show all market listings.
     */
    public function index()
    {
        return view('market.index', [
            'listings' => $this->getListings()->values()->all(),
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

        $listing = collect(config('marketplace.listings'))->firstWhere('id', $id);

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

        return collect(config('marketplace.listings'));
    }

    private function mapDatabaseListing(Listing $listing): array
    {
        return [
            'id' => $listing->id,
            'name' => $listing->skin?->market_hash_name ?? 'Unknown skin',
            'condition' => $listing->condition ?? 'Unknown',
            'price_usd' => (float) $listing->price_usd,
            'float' => (float) ($listing->float_value ?? 0),
            'image' => $listing->skin?->image_url ?? 'https://placehold.co/640x360?text=No+Image',
            'seller' => $listing->seller?->name ?? 'Market Seller',
        ];
    }
}
