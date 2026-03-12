<?php

use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\SellController;
use App\Http\Controllers\SteamAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MarketplaceController::class, 'home'])->name('home');
Route::get('/market', [MarketplaceController::class, 'index'])->name('market.index');
Route::get('/market/{id}', [MarketplaceController::class, 'show'])
    ->whereNumber('id')
    ->name('market.show');

Route::get('/sell', [SellController::class, 'create'])->name('sell.create');
Route::post('/sell/trade-link', [SellController::class, 'updateTradeLink'])->name('sell.trade-link');
Route::get('/sell/inventory', [SellController::class, 'inventory'])->name('sell.inventory');
Route::post('/sell', [SellController::class, 'store'])->name('sell.store');
Route::get('/sell/success', [SellController::class, 'success'])->name('sell.success');

Route::get('/auth/steam/redirect', [SteamAuthController::class, 'redirect'])->name('steam.redirect');
Route::get('/auth/steam/callback', [SteamAuthController::class, 'callback'])->name('steam.callback');
Route::post('/auth/steam/logout', [SteamAuthController::class, 'logout'])->name('steam.logout');
