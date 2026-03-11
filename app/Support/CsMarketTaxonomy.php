<?php

namespace App\Support;

use Illuminate\Support\Str;

class CsMarketTaxonomy
{
    /**
     * Tabs shown on the Market page (basic CS weapon classes).
     */
    public const TAB_CATEGORY_LABELS = [
        'all' => 'All Items',
        'rifles' => 'Rifles',
        'pistols' => 'Pistols',
        'smgs' => 'SMGs',
        'heavy' => 'Heavy',
        'knives' => 'Knives',
        'gloves' => 'Gloves',
    ];

    /**
     * Extended categories stored in DB so non-weapon items don't get misfiled.
     */
    public const DATA_CATEGORY_LABELS = [
        'rifles' => 'Rifles',
        'pistols' => 'Pistols',
        'smgs' => 'SMGs',
        'heavy' => 'Heavy',
        'knives' => 'Knives',
        'gloves' => 'Gloves',
        'stickers' => 'Stickers',
        'agents' => 'Agents',
        'containers' => 'Containers',
        'keychains' => 'Keychains',
        'patches' => 'Patches',
        'collectibles' => 'Collectibles',
        'music_kits' => 'Music Kits',
        'graffiti' => 'Graffiti',
        'tools' => 'Tools',
        'misc' => 'Misc',
    ];

    /**
     * Def-index mapping for guns/pistols/heavy/smgs.
     */
    private const DEF_INDEX_CATEGORY = [
        // Pistols
        1 => 'pistols', 2 => 'pistols', 3 => 'pistols', 4 => 'pistols', 30 => 'pistols',
        32 => 'pistols', 36 => 'pistols', 61 => 'pistols', 63 => 'pistols', 64 => 'pistols',

        // Heavy
        14 => 'heavy', 15 => 'heavy', 25 => 'heavy', 27 => 'heavy', 28 => 'heavy', 35 => 'heavy',

        // SMGs
        17 => 'smgs', 19 => 'smgs', 23 => 'smgs', 24 => 'smgs', 26 => 'smgs', 33 => 'smgs', 34 => 'smgs',

        // Rifles (including sniper rifles)
        7 => 'rifles', 8 => 'rifles', 10 => 'rifles', 11 => 'rifles', 13 => 'rifles',
        16 => 'rifles', 38 => 'rifles', 39 => 'rifles', 40 => 'rifles', 60 => 'rifles',
    ];

    private const DIRECT_PREFIX_CATEGORY = [
        'sticker |' => 'stickers',
        'sealed graffiti |' => 'graffiti',
        'patch |' => 'patches',
        'music kit |' => 'music_kits',
        'keychain |' => 'keychains',
        'charm |' => 'keychains',
    ];

    private const NAME_KEYWORD_CATEGORY = [
        'stickers' => ['sticker', 'holo', 'foil', 'glitter'],
        'agents' => ['agent', 'the elite mr. muhlik', 'sir bloody', 'special agent'],
        'containers' => ['case', 'capsule', 'souvenir package', 'container'],
        'patches' => ['patch'],
        'collectibles' => ['pin', 'collectible', 'coin'],
        'music_kits' => ['music kit'],
        'graffiti' => ['graffiti'],
        'tools' => ['name tag', 'stattrak swap tool', 'storage unit', 'coupon'],
        'knives' => [
            'bayonet', 'karambit', 'm9', 'bowie', 'flip', 'gut', 'huntsman', 'falchion',
            'butterfly', 'shadow daggers', 'navaja', 'stiletto', 'ursus', 'talon', 'classic knife',
            'paracord', 'survival knife', 'nomad knife', 'skeleton knife', 'kukri', 'knife',
        ],
        'gloves' => [
            'gloves', 'hand wraps', 'moto gloves', 'sport gloves', 'specialist gloves',
            'driver gloves', 'bloodhound gloves', 'hydra gloves',
        ],
        'pistols' => [
            'glock', 'usp-s', 'p2000', 'p250', 'five-seven', 'cz75', 'r8 revolver',
            'desert eagle', 'tec-9', 'dual berettas', 'zeus',
        ],
        'smgs' => ['mac-10', 'mp9', 'mp7', 'mp5', 'ump-45', 'p90', 'pp-bizon'],
        'heavy' => ['nova', 'xm1014', 'mag-7', 'sawed-off', 'm249', 'negev'],
        'rifles' => [
            'ak-47', 'm4a4', 'm4a1-s', 'aug', 'famas', 'galil ar', 'sg 553',
            'awp', 'ssg 08', 'scar-20', 'g3sg1',
        ],
    ];

    public static function categories(): array
    {
        return self::TAB_CATEGORY_LABELS;
    }

    public static function normalizeCategory(?string $category): string
    {
        $category = strtolower(trim((string) $category));

        return array_key_exists($category, self::TAB_CATEGORY_LABELS) ? $category : 'all';
    }

    public static function normalizeDataCategory(?string $category): string
    {
        $category = strtolower(trim((string) $category));

        return array_key_exists($category, self::DATA_CATEGORY_LABELS) ? $category : 'misc';
    }

    public static function categoryFromItem(?string $marketHashName, ?string $weaponName = null, ?int $defIndex = null): string
    {
        $name = strtolower(trim((string) $marketHashName));
        $weapon = strtolower(self::normalizeWeaponName($weaponName ?: self::weaponFromMarketHashName($marketHashName) ?: ''));

        foreach (self::DIRECT_PREFIX_CATEGORY as $prefix => $category) {
            if ($name !== '' && Str::startsWith($name, $prefix)) {
                return $category;
            }
        }

        foreach (self::NAME_KEYWORD_CATEGORY as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (($name !== '' && Str::contains($name, $keyword)) || ($weapon !== '' && Str::contains($weapon, $keyword))) {
                    return $category;
                }
            }
        }

        if ($defIndex !== null) {
            if (array_key_exists($defIndex, self::DEF_INDEX_CATEGORY)) {
                return self::DEF_INDEX_CATEGORY[$defIndex];
            }

            // CS knife def-indexes are usually in 500+ range; gloves in 5000+.
            if ($defIndex >= 5000) {
                return 'gloves';
            }

            if ($defIndex >= 500 && $defIndex < 600) {
                return 'knives';
            }
        }

        return 'misc';
    }

    public static function weaponFromMarketHashName(?string $marketHashName): ?string
    {
        if (! is_string($marketHashName) || trim($marketHashName) === '') {
            return null;
        }

        $withoutWear = preg_replace('/\s*\(([^()]*)\)\s*$/', '', trim($marketHashName));
        $parts = explode(' | ', (string) $withoutWear, 2);

        if (! isset($parts[0])) {
            return null;
        }

        return self::normalizeWeaponName($parts[0]);
    }

    public static function normalizeWeaponName(?string $weaponName): ?string
    {
        if (! is_string($weaponName)) {
            return null;
        }

        $value = trim($weaponName);

        if ($value === '') {
            return null;
        }

        $value = preg_replace('/^★\s*/u', '', $value);
        $value = preg_replace('/^(StatTrak™|StatTrak)\s*/u', '', (string) $value);
        $value = preg_replace('/^Souvenir\s+/u', '', (string) $value);
        $value = preg_replace('/\s+/', ' ', (string) $value);

        return trim((string) $value) ?: null;
    }
}
