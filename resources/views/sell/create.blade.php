@extends('layouts.app')

@section('content')
@php
    $steamConnected = is_array($steamUser ?? null) && ! empty($steamUser['steam_id']);
    $hasValidTradeLink = $tradeLinkInfo && ! $tradeLinkError;
@endphp

<section class="page-hero">
    <p class="eyebrow">SELL PORTAL</p>
    <h1>Sell in 3 steps</h1>
    <p class="hero-copy">Connect Steam, save your trade link, then load inventory and select the item to list.</p>
</section>

<section class="sell-stack">
    <article class="form-panel sell-step">
        <p class="eyebrow">STEP 1</p>
        <h2>Connect Steam</h2>
        @if ($steamConnected)
            <div class="steam-user">
                @if (! empty($steamUser['avatar_url']))
                    <img src="{{ $steamUser['avatar_url'] }}" alt="Steam avatar">
                @endif
                <div>
                    <p><strong>{{ $steamUser['name'] ?? 'Steam user' }}</strong></p>
                    <p class="muted">Steam ID: {{ $steamUser['steam_id'] }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('steam.logout') }}">
                @csrf
                <button class="button button-outline" type="submit">Disconnect Steam</button>
            </form>
        @else
            <a class="button button-primary" href="{{ route('steam.redirect') }}">Connect via Steam OpenID</a>
        @endif
    </article>

    <article class="form-panel sell-step">
        <p class="eyebrow">STEP 2</p>
        <h2>Save trade link</h2>
        <form class="form" method="POST" action="{{ route('sell.trade-link') }}">
            @csrf

            <label for="trade_link">Steam trade link</label>
            <input
                id="trade_link"
                name="trade_link"
                type="url"
                value="{{ old('trade_link', $tradeLink) }}"
                placeholder="https://steamcommunity.com/tradeoffer/new/?partner=...&token=..."
                required
                @disabled(! $steamConnected)
            >
            @error('trade_link')
                <p class="error">{{ $message }}</p>
            @enderror
            @if ($tradeLinkError)
                <p class="error">{{ $tradeLinkError }}</p>
            @endif
            @if ($tradeLinkInfo)
                <p class="muted">Validated partner {{ $tradeLinkInfo['partner_id'] }}. Link is bound to your connected Steam account.</p>
            @endif

            <button class="button button-primary" type="submit" @disabled(! $steamConnected)>Save Trade Link</button>
        </form>
    </article>

    <article class="form-panel sell-step">
        <p class="eyebrow">STEP 3</p>
        <h2>Load inventory and list</h2>
        <div class="hero-actions">
            <button
                class="button button-outline"
                id="refreshInventory"
                type="button"
                data-url="{{ route('sell.inventory') }}"
                @disabled(! $steamConnected || ! $hasValidTradeLink)
            >
                Refresh Steam Inventory
            </button>
            <a class="button button-outline" href="{{ route('market.index') }}">View market</a>
        </div>

        <p id="inventoryStatus" class="muted">
            @if (! $steamConnected)
                Connect Steam first.
            @elseif (! $hasValidTradeLink)
                Save a valid trade link before loading inventory.
            @elseif ($inventoryError)
                {{ $inventoryError }}
            @elseif (count($inventoryItems) === 0)
                No eligible tradable skins found.
            @else
                Showing {{ count($inventoryItems) }} tradable inventory items.
            @endif
        </p>

        <form class="form sell-listing-form" method="POST" action="{{ route('sell.store') }}">
            @csrf

            <div id="inventoryItems" class="inventory-grid">
                @foreach ($inventoryItems as $item)
                    <label class="inventory-card">
                        <input
                            type="radio"
                            name="selected_asset_id"
                            value="{{ $item['asset_id'] }}"
                            @checked(old('selected_asset_id') === $item['asset_id'])
                        >
                        <img src="{{ $item['image_url'] }}" alt="{{ $item['market_hash_name'] }}">
                        <strong>{{ $item['market_hash_name'] }}</strong>
                        <p class="muted">{{ $item['category_label'] }} | {{ $item['weapon'] }}</p>
                        @if (! empty($item['condition']))
                            <p class="muted">{{ $item['condition'] }}</p>
                        @endif
                    </label>
                @endforeach
            </div>
            @error('selected_asset_id')
                <p class="error">{{ $message }}</p>
            @enderror

            <label for="price_usd">Price (USD)</label>
            <input id="price_usd" name="price_usd" type="number" step="0.01" min="0.5" value="{{ old('price_usd') }}" required>
            @error('price_usd')
                <p class="error">{{ $message }}</p>
            @enderror

            <button class="button button-primary" type="submit" @disabled(count($inventoryItems) === 0)>Submit listing</button>
        </form>
    </article>
</section>

<script>
    (() => {
        const refreshButton = document.getElementById('refreshInventory');
        const statusEl = document.getElementById('inventoryStatus');
        const gridEl = document.getElementById('inventoryItems');

        if (!refreshButton || !statusEl || !gridEl) {
            return;
        }

        const escapeHtml = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        const renderItems = (items) => {
            if (!Array.isArray(items) || items.length === 0) {
                gridEl.innerHTML = '';
                return;
            }

            gridEl.innerHTML = items.map((item, index) => `
                <label class="inventory-card">
                    <input type="radio" name="selected_asset_id" value="${escapeHtml(item.asset_id)}" ${index === 0 ? 'checked' : ''}>
                    <img src="${escapeHtml(item.image_url)}" alt="${escapeHtml(item.market_hash_name)}">
                    <strong>${escapeHtml(item.market_hash_name)}</strong>
                    <p class="muted">${escapeHtml(item.category_label)} | ${escapeHtml(item.weapon)}</p>
                    ${item.condition ? `<p class="muted">${escapeHtml(item.condition)}</p>` : ''}
                </label>
            `).join('');
        };

        refreshButton.addEventListener('click', async () => {
            refreshButton.disabled = true;
            statusEl.textContent = 'Loading inventory from Steam...';

            try {
                const response = await fetch(refreshButton.dataset.url, {
                    method: 'GET',
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                });

                const payload = await response.json();

                if (!response.ok || !payload.ok) {
                    statusEl.textContent = payload.message || 'Failed to load Steam inventory.';
                    return;
                }

                renderItems(payload.items);
                statusEl.textContent = `Loaded ${payload.count} tradable items.`;
            } catch (error) {
                statusEl.textContent = 'Could not reach inventory endpoint.';
            } finally {
                refreshButton.disabled = false;
            }
        });
    })();
</script>
@endsection
