@extends('layouts.app')

@section('content')
<section>
    <h1>Sell Your Skin</h1>
    <p>Submit your item info. This basic version only validates and shows a confirmation.</p>

    <form class="form" method="POST" action="{{ route('sell.store') }}">
        @csrf

        <label for="item_name">Item name</label>
        <input id="item_name" name="item_name" type="text" value="{{ old('item_name') }}" required>
        @error('item_name')
            <p class="error">{{ $message }}</p>
        @enderror

        <label for="wear">Condition / wear</label>
        <input id="wear" name="wear" type="text" value="{{ old('wear') }}" required>
        @error('wear')
            <p class="error">{{ $message }}</p>
        @enderror

        <label for="float_value">Float value (0 to 1)</label>
        <input id="float_value" name="float_value" type="number" step="0.01" min="0" max="1" value="{{ old('float_value') }}" required>
        @error('float_value')
            <p class="error">{{ $message }}</p>
        @enderror

        <label for="price_usd">Price (USD)</label>
        <input id="price_usd" name="price_usd" type="number" step="0.01" min="0.5" value="{{ old('price_usd') }}" required>
        @error('price_usd')
            <p class="error">{{ $message }}</p>
        @enderror

        <label for="trade_link">Steam trade link</label>
        <input id="trade_link" name="trade_link" type="url" value="{{ old('trade_link') }}" required>
        @error('trade_link')
            <p class="error">{{ $message }}</p>
        @enderror

        <button class="button" type="submit">Submit listing</button>
    </form>
</section>
@endsection
