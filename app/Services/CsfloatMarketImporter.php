<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\Skin;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CsfloatMarketImporter
{
    public function importListings(int $pages = 1, int $limit = 50): array
    {
        $baseUrl = rtrim((string) config('services.csfloat.base_url'), '/');
        $path = (string) config('services.csfloat.listings_path', '/listings');
        $apiKey = (string) config('services.csfloat.api_key', '');

        if ($baseUrl === '') {
            throw new \RuntimeException('CSFLOAT_BASE_URL is missing.');
        }

        $stats = [
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
        ];

        for ($page = 1; $page <= $pages; $page++) {
            $request = Http::acceptJson()->timeout(20);

            if ($apiKey !== '') {
                // CSFloat expects a raw API key in Authorization header, not Bearer token.
                $request = $request->withHeaders([
                    'Authorization' => $apiKey,
                ]);
            }

            $response = $request->get($baseUrl.$path, [
                'limit' => $limit,
                'page' => $page,
            ]);

            $response->throw();

            $records = $this->extractRecords($response->json());

            foreach ($records as $record) {
                $result = $this->importRecord((array) $record);
                $stats[$result]++;
            }
        }

        return $stats;
    }

    private function extractRecords(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        if (isset($payload['data']) && is_array($payload['data'])) {
            return $payload['data'];
        }

        if (isset($payload['results']) && is_array($payload['results'])) {
            return $payload['results'];
        }

        if (array_is_list($payload)) {
            return $payload;
        }

        return [];
    }

    private function importRecord(array $record): string
    {
        $item = (array) Arr::get($record, 'item', []);

        $marketHashName = $this->firstString([
            Arr::get($record, 'market_hash_name'),
            Arr::get($item, 'market_hash_name'),
            Arr::get($item, 'name'),
            Arr::get($record, 'name'),
        ]);

        if ($marketHashName === null) {
            return 'skipped';
        }

        $priceUsd = $this->resolvePriceUsd($record);
        if ($priceUsd === null) {
            return 'skipped';
        }

        [$weaponName, $skinName] = $this->splitSkinName($marketHashName);

        $skin = Skin::updateOrCreate(
            ['market_hash_name' => $marketHashName],
            [
                'weapon_name' => $weaponName,
                'skin_name' => $skinName,
                'rarity' => $this->firstString([
                    Arr::get($item, 'rarity'),
                    Arr::get($record, 'rarity'),
                ]),
                'image_url' => $this->resolveImageUrl($record),
                'external_item_id' => $this->firstString([
                    Arr::get($item, 'id'),
                    Arr::get($record, 'item_id'),
                ]),
                'metadata' => $item,
            ]
        );

        $externalId = $this->firstString([
            Arr::get($record, 'id'),
            Arr::get($record, 'listing_id'),
            Arr::get($record, 'listingId'),
        ]);

        $lookup = [
            'external_id' => $externalId ?: $this->makeSyntheticExternalId($marketHashName, $record),
        ];

        $listing = Listing::where($lookup)->first();

        $attributes = [
            'source' => 'csfloat',
            'seller_id' => null,
            'skin_id' => $skin->id,
            'condition' => $this->firstString([
                Arr::get($item, 'exterior'),
                Arr::get($record, 'condition'),
                Arr::get($record, 'wear_name'),
            ]),
            'float_value' => $this->resolveFloatValue($record),
            'price_usd' => $priceUsd,
            'inspect_link' => $this->firstString([
                Arr::get($item, 'inspect_link'),
                Arr::get($record, 'inspect_link'),
            ]),
            'listing_url' => $this->firstString([
                Arr::get($record, 'url'),
                $externalId ? 'https://csfloat.com/item/'.$externalId : null,
            ]),
            'status' => $this->normalizeStatus($this->firstString([
                Arr::get($record, 'state'),
                Arr::get($record, 'status'),
                'active',
            ])),
            'listed_at' => $this->resolveTimestamp($record),
            'metadata' => $record,
        ];

        if ($listing) {
            $listing->update($attributes);
            return 'updated';
        }

        Listing::create($lookup + $attributes);

        return 'imported';
    }

    private function splitSkinName(string $marketHashName): array
    {
        $parts = explode(' | ', $marketHashName, 2);

        return [
            $parts[0] ?? null,
            $parts[1] ?? null,
        ];
    }

    private function resolveImageUrl(array $record): ?string
    {
        $item = (array) Arr::get($record, 'item', []);

        $raw = $this->firstString([
            Arr::get($item, 'image_url'),
            Arr::get($item, 'icon_url'),
            Arr::get($record, 'image_url'),
            Arr::get($record, 'icon_url'),
        ]);

        if ($raw === null) {
            return null;
        }

        if (Str::startsWith($raw, ['http://', 'https://'])) {
            return $raw;
        }

        return 'https://community.cloudflare.steamstatic.com/economy/image/'.ltrim($raw, '/');
    }

    private function resolveFloatValue(array $record): ?float
    {
        $item = (array) Arr::get($record, 'item', []);

        foreach ([
            Arr::get($record, 'float_value'),
            Arr::get($item, 'float_value'),
            Arr::get($item, 'float'),
        ] as $candidate) {
            if ($candidate === null || $candidate === '') {
                continue;
            }

            if (is_numeric($candidate)) {
                return max(0, min(1, (float) $candidate));
            }
        }

        return null;
    }

    private function resolvePriceUsd(array $record): ?float
    {
        $candidates = [
            Arr::get($record, 'price_usd'),
            Arr::get($record, 'price.value'),
            Arr::get($record, 'price.amount'),
            Arr::get($record, 'price'),
            Arr::get($record, 'min_offer_price'),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === null || $candidate === '') {
                continue;
            }

            if (is_array($candidate)) {
                continue;
            }

            $normalized = (float) preg_replace('/[^\d.]/', '', (string) $candidate);

            if ($normalized <= 0) {
                continue;
            }

            if ($normalized > 1000) {
                $normalized = $normalized / 100;
            }

            return round($normalized, 2);
        }

        return null;
    }

    private function resolveTimestamp(array $record): ?Carbon
    {
        foreach (['created_at', 'listed_at', 'createdAt'] as $field) {
            $value = Arr::get($record, $field);

            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            try {
                return Carbon::parse($value);
            } catch (\Throwable) {
                continue;
            }
        }

        return now();
    }

    private function makeSyntheticExternalId(string $marketHashName, array $record): string
    {
        return 'synth_'.md5($marketHashName.'|'.json_encode($record));
    }

    private function normalizeStatus(?string $status): string
    {
        $value = strtolower(trim((string) $status));

        if (in_array($value, ['listed', 'active', 'for_sale', 'onsale'], true)) {
            return 'active';
        }

        if (in_array($value, ['sold', 'completed'], true)) {
            return 'sold';
        }

        if (in_array($value, ['cancelled', 'canceled', 'removed'], true)) {
            return 'cancelled';
        }

        return $value !== '' ? $value : 'active';
    }

    private function firstString(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if ($candidate === null) {
                continue;
            }

            $value = trim((string) $candidate);

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }
}
