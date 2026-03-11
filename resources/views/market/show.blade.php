@extends('layouts.app')

@section('content')
<section class="page-hero">
    <p class="eyebrow">LISTING DETAIL</p>
    <h1>{{ $listing['name'] }}</h1>
    <p class="hero-copy">Review the core values before buying: condition, float, seller, and final price.</p>
</section>

<section class="listing-detail listing-detail-panel">
    <img src="{{ $listing['image'] }}" alt="{{ $listing['name'] }}">

    <div class="detail-card">
        <h2>Item data</h2>
        <div class="chip-row">
            <span class="chip">{{ $listing['condition'] }}</span>
            <span class="chip">Float {{ number_format($listing['float'], 2) }}</span>
            <span class="chip">Seller {{ $listing['seller'] }}</span>
        </div>

        <p class="price detail-price">${{ number_format($listing['price_usd'], 2) }}</p>

        <div class="hero-actions">
            <button class="button button-primary" type="button">Buy now (demo)</button>
            <a class="button button-outline" href="{{ route('market.index') }}">Back to market</a>
        </div>
    </div>
</section>
@endsection
