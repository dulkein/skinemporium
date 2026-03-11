@extends('layouts.app')

@section('content')
<section class="page-hero">
    <p class="eyebrow">SUBMITTED</p>
    <h1>Listing request sent</h1>
    <p class="hero-copy">Your listing was received in this demo flow. Here is a quick summary.</p>
</section>

<section class="summary-panel">
    <div class="summary-box">
        <p><strong>Item:</strong> {{ $listing['item_name'] }}</p>
        <p><strong>Condition:</strong> {{ $listing['wear'] }}</p>
        <p><strong>Float:</strong> {{ number_format((float) $listing['float_value'], 2) }}</p>
        <p><strong>Price:</strong> ${{ number_format((float) $listing['price_usd'], 2) }}</p>
    </div>

    <div class="hero-actions">
        <a class="button button-primary" href="{{ route('market.index') }}">Go to market</a>
        <a class="button button-outline" href="{{ route('sell.create') }}">Submit another</a>
    </div>
</section>
@endsection
