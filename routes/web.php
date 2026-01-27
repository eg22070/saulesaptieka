<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PharmacyController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RequestController;
use Illuminate\Support\Facades\Route;

// Redirect the root URL to the login page for guests
Route::redirect('/', '/login')->middleware('guest');

// Set the 'pieprasijumi.index' as the landing page for authenticated users
Route::redirect('/', '/pieprasijumi')->middleware('auth');

Route::resource('pharmacies', PharmacyController::class);
Route::resource('artikuli', ProductController::class);
Route::resource('pieprasijumi', RequestController::class);
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
