<?php

use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReviewResponseController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ReviewController::class, 'index'])->name('reviews.index');
Route::post('/reviews/{review}/response', [ReviewResponseController::class, 'store'])
    ->name('reviews.responses.store');
Route::put('/reviews/{review}/response', [ReviewResponseController::class, 'update'])
    ->name('reviews.responses.update');
Route::delete('/reviews/{review}/response', [ReviewResponseController::class, 'destroy'])
    ->name('reviews.responses.destroy');
