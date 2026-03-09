@extends('layouts.app')

@section('content')
<section class="neo-hero">
    <div class="neo-grid-lines" aria-hidden="true"></div>

    <div class="neo-hero-left">
        <p class="eyebrow">NEXT-GEN SKIN MARKETPLACE</p>
        <h1>Trade skins in a cleaner, faster, futuristic flow.</h1>
        <p class="hero-copy">
            This MVP focuses on core experience: browse live listings, inspect float and condition,
            and submit your own skin in minutes.
        </p>

        <div class="hero-actions">
            <a class="button button-primary" href="{{ route('market.index') }}">Open Market</a>
            <a class="button button-outline" href="{{ route('sell.create') }}">List Your Skin</a>
        </div>

        <div class="hero-stats">
            <article>
                <p class="stat-number">6</p>
                <p class="stat-label">Demo Listings</p>
            </article>
            <article>
                <p class="stat-number">0.03</p>
                <p class="stat-label">Best Float</p>
            </article>
            <article>
                <p class="stat-number">$4.75</p>
                <p class="stat-label">Entry Price</p>
            </article>
        </div>
    </div>

    <aside class="neo-panel">
        <p class="panel-title">LIVE MARKET SNAPSHOT</p>
        @foreach ($featuredListings as $listing)
            <a class="ticker-row" href="{{ route('market.show', $listing['id']) }}">
                <span class="ticker-name">{{ $listing['name'] }}</span>
                <span class="ticker-float">{{ number_format($listing['float'], 2) }}</span>
                <span class="ticker-price">${{ number_format($listing['price_usd'], 2) }}</span>
            </a>
        @endforeach
    </aside>
</section>

<section class="section-head">
    <p class="eyebrow">FEATURED INVENTORY</p>
    <h2>Fresh listings from trusted sellers</h2>
</section>

<section class="card-grid card-grid-featured">
    @foreach ($featuredListings as $listing)
        <article class="card card-featured">
            <img src="{{ $listing['image'] }}" alt="{{ $listing['name'] }}">
            <h3>{{ $listing['name'] }}</h3>
            <p>{{ $listing['condition'] }} | Float {{ number_format($listing['float'], 2) }}</p>
            <p class="price">${{ number_format($listing['price_usd'], 2) }}</p>
            <a href="{{ route('market.show', $listing['id']) }}">View details</a>
        </article>
    @endforeach
</section>
@endsection
