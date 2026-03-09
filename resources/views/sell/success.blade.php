@extends('layouts.app')

@section('content')
<section>
    <h1>Listing Submitted</h1>
    <p>Your listing request was received in this demo app.</p>

    <div class="summary-box">
        <p><strong>Item:</strong> {{ $listing['item_name'] }}</p>
        <p><strong>Condition:</strong> {{ $listing['wear'] }}</p>
        <p><strong>Float:</strong> {{ number_format((float) $listing['float_value'], 2) }}</p>
        <p><strong>Price:</strong> ${{ number_format((float) $listing['price_usd'], 2) }}</p>
    </div>

    <a class="button" href="{{ route('market.index') }}">Go to market</a>
</section>
@endsection
