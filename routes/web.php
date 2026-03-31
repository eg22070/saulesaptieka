<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PharmacyController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\PasutijumiController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->get('/', function () {
    $user = Auth::user();
    $role = strtolower(trim($user->role ?? ''));
    $isSpecialUser = strtolower(trim($user->email ?? '')) === 'd.grazule@saulesaptieka.lv';

    if ($isSpecialUser) {
        return redirect()->route('pasutijumi.index');
    }

    if ($role === 'farmaceiti') {
        return redirect()->route('pasutijumi.index');
    }

    // default for brivibas, kruzes, others
    return redirect()->route('pieprasijumi.index');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/users', [UserManagementController::class, 'index'])->middleware('block_special_user')->name('users.index');
    Route::post('/users', [UserManagementController::class, 'store'])->middleware('block_special_user')->name('users.store');
    Route::put('/users/{user}', [UserManagementController::class, 'update'])->middleware('block_special_user')->name('users.update');
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->middleware('block_special_user')->name('users.destroy');
});

Route::resource('pasutijumi', PasutijumiController::class)->middleware('auth');
Route::resource('artikuli', ProductController::class)->middleware('auth');
Route::get('/pieprasijumi/bulk-complete', [RequestController::class, 'bulkComplete'])
    ->middleware(['auth', 'block_special_user'])
    ->name('pieprasijumi.bulkComplete');
Route::resource('pieprasijumi', RequestController::class)->middleware(['auth', 'block_special_user']);
Route::resource('pharmacies', PharmacyController::class)->middleware(['auth', 'block_special_user']);
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
