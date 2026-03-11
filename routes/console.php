<?php

use App\Models\Skin;
use App\Services\CsfloatMarketImporter;
use App\Support\CsMarketTaxonomy;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('market:import-csfloat {--pages=1} {--limit=50}', function (CsfloatMarketImporter $importer) {
    $pages = max(1, (int) $this->option('pages'));
    $limit = max(1, min(100, (int) $this->option('limit')));

    $this->info("Importing CSFloat listings (pages={$pages}, limit={$limit})...");

    try {
        $stats = $importer->importListings($pages, $limit);
    } catch (\Throwable $e) {
        $this->error('Import failed: '.$e->getMessage());
        return;
    }

    $this->info('Done.');
    $this->line('Imported: '.$stats['imported']);
    $this->line('Updated: '.$stats['updated']);
    $this->line('Skipped: '.$stats['skipped']);
})->purpose('Import marketplace listings from CSFloat API into local DB');

Artisan::command('market:reclassify-skins', function () {
    $updated = 0;

    Skin::query()->chunkById(200, function ($skins) use (&$updated) {
        foreach ($skins as $skin) {
            $weapon = CsMarketTaxonomy::normalizeWeaponName(
                $skin->weapon_name ?: CsMarketTaxonomy::weaponFromMarketHashName($skin->market_hash_name)
            );

            $defIndex = is_numeric(data_get($skin->metadata, 'def_index'))
                ? (int) data_get($skin->metadata, 'def_index')
                : null;

            $category = CsMarketTaxonomy::categoryFromItem($skin->market_hash_name, $weapon, $defIndex);

            $skin->update([
                'weapon_name' => $weapon,
                'market_category' => $category,
            ]);

            $updated++;
        }
    });

    $this->info('Reclassified skins: '.$updated);
})->purpose('Recompute weapon + market category for imported skins');
