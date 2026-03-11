<?php

use App\Services\CsfloatMarketImporter;
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
