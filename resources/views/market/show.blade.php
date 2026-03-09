@extends('layouts.app')

@section('content')
<section class="listing-detail">
    <img src="{{ $listing['image'] }}" alt="{{ $listing['name'] }}">

    <div>
        <h1>{{ $listing['name'] }}</h1>
        <p><strong>Condition:</strong> {{ $listing['condition'] }}</p>
        <p><strong>Float:</strong> {{ number_format($listing['float'], 2) }}</p>
        <p><strong>Seller:</strong> {{ $listing['seller'] }}</p>
        <p class="price">${{ number_format($listing['price_usd'], 2) }}</p>

        <button class="button" type="button">Buy now (demo)</button>
        <a class="text-link" href="{{ route('market.index') }}">Back to market</a>
    </div>
</section>
@endsection
