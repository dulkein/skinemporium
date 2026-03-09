<?php

use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\SellController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MarketplaceController::class, 'home'])->name('home');
Route::get('/market', [MarketplaceController::class, 'index'])->name('market.index');
Route::get('/market/{id}', [MarketplaceController::class, 'show'])
    ->whereNumber('id')
    ->name('market.show');

Route::get('/sell', [SellController::class, 'create'])->name('sell.create');
Route::post('/sell', [SellController::class, 'store'])->name('sell.store');
Route::get('/sell/success', [SellController::class, 'success'])->name('sell.success');
