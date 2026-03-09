@extends('layouts.app')

@section('content')
<section>
    <h1>Market Listings</h1>
    <p>All available items in this basic marketplace demo.</p>

    <div class="card-grid">
        @foreach ($listings as $listing)
            <article class="card">
                <img src="{{ $listing['image'] }}" alt="{{ $listing['name'] }}">
                <h3>{{ $listing['name'] }}</h3>
                <p>{{ $listing['condition'] }}</p>
                <p>Float {{ number_format($listing['float'], 2) }}</p>
                <p class="price">${{ number_format($listing['price_usd'], 2) }}</p>
                <a href="{{ route('market.show', $listing['id']) }}">View details</a>
            </article>
        @endforeach
    </div>
</section>
@endsection
