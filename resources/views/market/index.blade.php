@extends('layouts.app')

@section('content')
<section class="page-hero">
    <p class="eyebrow">MARKETPLACE</p>
    <h1>Browse all available skins</h1>
    <p class="hero-copy">Simple listing board with transparent price, condition, and float values.</p>
</section>

<section class="section-head">
    <p class="eyebrow">LIVE ITEMS</p>
    <h2>Current listings</h2>
</section>

<section class="card-grid card-grid-featured">
    @foreach ($listings as $listing)
        <article class="card card-featured">
            <img src="{{ $listing['image'] }}" alt="{{ $listing['name'] }}">
            <h3>{{ $listing['name'] }}</h3>
            <div class="chip-row">
                <span class="chip">{{ $listing['condition'] }}</span>
                <span class="chip">Float {{ number_format($listing['float'], 2) }}</span>
            </div>
            <p class="price">${{ number_format($listing['price_usd'], 2) }}</p>
            <a class="text-link" href="{{ route('market.show', $listing['id']) }}">View details</a>
        </article>
    @endforeach
</section>
@endsection
