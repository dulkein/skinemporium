@extends('layouts.app')

@section('content')
<section class="page-hero market-hero">
    <p class="eyebrow">MARKETPLACE</p>
    <h1>Find skins by category and weapon</h1>
    <p class="hero-copy">CSFloat-style tab filters: pick a class (Rifles, Pistols, SMGs, Heavy, Knives, Gloves), then narrow by weapon.</p>

    <form class="market-search-form" method="GET" action="{{ route('market.index') }}">
        @if ($selectedCategory !== 'all')
            <input type="hidden" name="category" value="{{ $selectedCategory }}">
        @endif
        @if ($selectedWeapon !== '')
            <input type="hidden" name="weapon" value="{{ $selectedWeapon }}">
        @endif
        <input type="text" name="q" value="{{ $search }}" placeholder="Search skins or weapon name...">
        <button class="button button-primary" type="submit">Search</button>
    </form>
</section>

<section class="market-tab-row">
    @foreach ($categories as $categoryKey => $categoryLabel)
        <a class="market-tab {{ $selectedCategory === $categoryKey ? 'is-active' : '' }}" href="{{ $categoryLinks[$categoryKey] }}">
            <span>{{ $categoryLabel }}</span>
            <span class="market-tab-count">{{ $categoryCounts[$categoryKey] ?? 0 }}</span>
        </a>
    @endforeach
</section>

@if ($selectedCategory !== 'all' && count($availableWeapons) > 0)
    <section class="weapon-tab-row">
        <a class="weapon-tab {{ $selectedWeapon === '' ? 'is-active' : '' }}" href="{{ $weaponLinks['__all'] }}">
            All {{ $categories[$selectedCategory] }}
        </a>

        @foreach ($availableWeapons as $weapon)
            <a class="weapon-tab {{ $selectedWeapon === $weapon ? 'is-active' : '' }}" href="{{ $weaponLinks[$weapon] }}">
                {{ $weapon }}
            </a>
        @endforeach
    </section>
@endif

<section class="market-results-meta">
    <p>
        Showing <strong>{{ count($listings) }}</strong> of <strong>{{ $totalCount }}</strong> listings
        @if ($selectedCategory !== 'all')
            in <strong>{{ $categories[$selectedCategory] }}</strong>
        @endif
        @if ($selectedWeapon !== '')
            for <strong>{{ $selectedWeapon }}</strong>
        @endif
    </p>
</section>

@if (count($listings) === 0)
    <section class="empty-panel">
        <h2>No listings found</h2>
        <p>Try a different category or clear your weapon/search filters.</p>
        <a class="button button-outline" href="{{ route('market.index') }}">Reset filters</a>
    </section>
@else
    <section class="card-grid card-grid-featured">
        @foreach ($listings as $listing)
            <article class="card card-featured">
                <img src="{{ $listing['image'] }}" alt="{{ $listing['name'] }}">
                <h3>{{ $listing['name'] }}</h3>
                <div class="chip-row">
                    <span class="chip">{{ $listing['category_label'] ?? ucfirst($listing['category']) }}</span>
                    @if (!empty($listing['weapon']))
                        <span class="chip">{{ $listing['weapon'] }}</span>
                    @endif
                    @if (!empty($listing['rarity']))
                        <span class="chip chip-rarity">{{ $listing['rarity'] }}</span>
                    @endif
                    @if (!empty($listing['condition']))
                        <span class="chip">{{ $listing['condition'] }}</span>
                    @endif
                    <span class="chip">Float {{ number_format($listing['float'], 3) }}</span>
                </div>
                <p class="price">${{ number_format($listing['price_usd'], 2) }}</p>
                <a class="text-link" href="{{ route('market.show', $listing['id']) }}">View details</a>
            </article>
        @endforeach
    </section>
@endif
@endsection
