@extends('layouts.app')

@section('content')
<section class="page-hero">
    <p class="eyebrow">SUBMITTED</p>
    <h1>Listing request sent</h1>
    <p class="hero-copy">Your listing was received in this demo flow. Here is a quick summary.</p>
</section>

<section class="summary-panel">
    <div class="summary-box">
        @if (! empty($listing['image_url']))
            <img src="{{ $listing['image_url'] }}" alt="{{ $listing['item_name'] }}">
        @endif
        <p><strong>Item:</strong> {{ $listing['item_name'] }}</p>
        @if (! empty($listing['weapon']))
            <p><strong>Weapon:</strong> {{ $listing['weapon'] }}</p>
        @endif
        @if (! empty($listing['category']))
            <p><strong>Category:</strong> {{ $listing['category'] }}</p>
        @endif
        <p><strong>Condition:</strong> {{ $listing['condition'] ?? 'Unknown' }}</p>
        <p><strong>Price:</strong> ${{ number_format((float) $listing['price_usd'], 2) }}</p>
        @if (! empty($listing['listing_id']))
            <p><strong>Listing ID:</strong> #{{ $listing['listing_id'] }}</p>
        @endif
    </div>

    <div class="hero-actions">
        <a class="button button-primary" href="{{ route('market.index') }}">Go to market</a>
        <a class="button button-outline" href="{{ route('sell.create') }}">Submit another</a>
    </div>
</section>
@endsection
