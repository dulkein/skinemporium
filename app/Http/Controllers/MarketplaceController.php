<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    /**
     * Show the homepage.
     */
    public function home()
    {
        $listings = collect(config('marketplace.listings'))
            ->take(3)
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
            'listings' => config('marketplace.listings'),
        ]);
    }

    /**
     * Show one listing by id.
     */
    public function show(int $id)
    {
        $listing = collect(config('marketplace.listings'))
            ->firstWhere('id', $id);

        abort_if(! $listing, 404);

        return view('market.show', [
            'listing' => $listing,
        ]);
    }
}
