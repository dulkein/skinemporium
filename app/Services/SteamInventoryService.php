<?php

namespace App\Services;

use App\Support\CsMarketTaxonomy;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class SteamInventoryService
{
    /**
     * Load user's CS2 inventory and keep only tradable weapon skins / knives / gloves.
     *
     * @return list<array<string, mixed>>
     */
    public function fetchTradableCs2Items(string $steamId, int $count = 2000): array
    {
        $response = Http::acceptJson()
            ->timeout(20)
            ->get("https://steamcommunity.com/inventory/{$steamId}/730/2", [
                'l' => 'english',
                'count' => max(1, min(5000, $count)),
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Unable to load Steam inventory right now.');
        }

        $payload = $response->json();

        if (! is_array($payload) || ($payload['success'] ?? 0) !== 1) {
            throw new \RuntimeException('Steam inventory is private or unavailable.');
        }

        $assets = collect($payload['assets'] ?? []);
        $descriptions = $this->indexDescriptions($payload['descriptions'] ?? []);

        $items = $assets
            ->map(function ($asset) use ($descriptions) {
                if (! is_array($asset)) {
                    return null;
                }

                $key = (string) ($asset['classid'] ?? '').'::'.(string) ($asset['instanceid'] ?? '0');
                $description = $descriptions->get($key);

                if (! is_array($description)) {
                    return null;
                }

                if ((int) ($description['tradable'] ?? 0) !== 1) {
                    return null;
                }

                $marketHashName = (string) ($description['market_hash_name'] ?? $description['name'] ?? '');
                if ($marketHashName === '') {
                    return null;
                }

                $weapon = CsMarketTaxonomy::normalizeWeaponName(
                    CsMarketTaxonomy::weaponFromMarketHashName($marketHashName)
                );

                $category = CsMarketTaxonomy::categoryFromItem(
                    $marketHashName,
                    $weapon,
                    is_numeric($description['def_index'] ?? null) ? (int) $description['def_index'] : null
                );

                // Keep only core sellable categories for this MVP.
                if (! in_array($category, ['rifles', 'pistols', 'smgs', 'heavy', 'knives', 'gloves'], true)) {
                    return null;
                }

                $condition = $this->extractConditionFromName($marketHashName);

                return [
                    'asset_id' => (string) ($asset['assetid'] ?? ''),
                    'class_id' => (string) ($asset['classid'] ?? ''),
                    'instance_id' => (string) ($asset['instanceid'] ?? ''),
                    'market_hash_name' => $marketHashName,
                    'weapon' => $weapon,
                    'category' => $category,
                    'category_label' => CsMarketTaxonomy::DATA_CATEGORY_LABELS[$category] ?? ucfirst($category),
                    'condition' => $condition,
                    'rarity' => $this->extractRarity($description),
                    'image_url' => $this->buildImageUrl(
                        (string) ($description['icon_url_large'] ?? $description['icon_url'] ?? '')
                    ),
                ];
            })
            ->filter()
            ->values();

        return $items
            ->sortBy([
                ['category_label', 'asc'],
                ['weapon', 'asc'],
                ['market_hash_name', 'asc'],
            ])
            ->values()
            ->all();
    }

    private function indexDescriptions(array $descriptions): Collection
    {
        return collect($descriptions)
            ->filter(fn ($item) => is_array($item))
            ->mapWithKeys(function (array $item) {
                $classId = (string) ($item['classid'] ?? '');
                $instanceId = (string) ($item['instanceid'] ?? '0');

                return ["{$classId}::{$instanceId}" => $item];
            });
    }

    private function buildImageUrl(string $icon): string
    {
        if ($icon === '') {
            return 'https://placehold.co/512x384?text=No+Image';
        }

        if (str_starts_with($icon, 'http://') || str_starts_with($icon, 'https://')) {
            return $icon;
        }

        return 'https://community.cloudflare.steamstatic.com/economy/image/'.ltrim($icon, '/');
    }

    private function extractConditionFromName(string $marketHashName): ?string
    {
        if (preg_match('/\((Factory New|Minimal Wear|Field-Tested|Well-Worn|Battle-Scarred)\)\s*$/i', $marketHashName, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function extractRarity(array $description): ?string
    {
        $tags = Arr::get($description, 'tags', []);
        if (! is_array($tags)) {
            return null;
        }

        foreach ($tags as $tag) {
            if (! is_array($tag)) {
                continue;
            }

            $category = strtolower((string) ($tag['category'] ?? $tag['category_name'] ?? ''));
            if (! str_contains($category, 'rarity')) {
                continue;
            }

            $label = trim((string) ($tag['localized_tag_name'] ?? $tag['name'] ?? ''));
            if ($label !== '') {
                return $label;
            }
        }

        return null;
    }
}
