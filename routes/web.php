<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PharmacyController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\PasutijumiController;
use App\Http\Controllers\PasutijumuPieprasijumsController;
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

    if ($role === 'brivibas') {
        return redirect()->route('pasutijumu-pieprasijumi.index');
    }

    // default for kruzes, others
    return redirect()->route('pieprasijumi.index');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/users', [UserManagementController::class, 'index'])->middleware('block_special_user')->name('users.index');
    Route::post('/users', [UserManagementController::class, 'store'])->middleware('block_special_user')->name('users.store');
    Route::put('/users/{user}', [UserManagementController::class, 'update'])->middleware('block_special_user')->name('users.update');
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->middleware('block_special_user')->name('users.destroy');
});

Route::post('pasutijumi/kvits', [PasutijumiController::class, 'storeKvits'])
    ->middleware('auth')
    ->name('pasutijumi.kvits');
Route::resource('pasutijumi', PasutijumiController::class)->middleware('auth');
Route::resource('artikuli', ProductController::class)->middleware('auth');
Route::post('pasutijumu-pieprasijumi/{pieprasijums}/sync-pasutijumi', [PasutijumuPieprasijumsController::class, 'syncPasutijumi'])
    ->middleware('auth')
    ->name('pasutijumu-pieprasijumi.sync');
Route::post('pasutijumu-pieprasijumi/{pieprasijums}/aizliegumi', [PasutijumuPieprasijumsController::class, 'updateAizliegumi'])
    ->middleware('auth')
    ->name('pasutijumu-pieprasijumi.aizliegumi');
Route::post('pasutijumu-pieprasijumi/{pieprasijums}/complete', [PasutijumuPieprasijumsController::class, 'complete'])
    ->middleware('auth')
    ->name('pasutijumu-pieprasijumi.complete');
Route::post('pasutijumu-pieprasijumi/{pieprasijums}/pasutijumi/{pasutijums}', [PasutijumuPieprasijumsController::class, 'updatePasutijums'])
    ->middleware('auth')
    ->name('pasutijumu-pieprasijumi.pasutijumi.update');
Route::post('pasutijumu-pieprasijumi/{pieprasijums}/brivie-artikuli', [PasutijumuPieprasijumsController::class, 'storeBrivaisArtikuls'])
    ->middleware('auth')
    ->name('pasutijumu-pieprasijumi.brivie-artikuli.store');
Route::post('pasutijumu-pieprasijumi/{pieprasijums}/brivie-artikuli/{brivaisArtikuls}', [PasutijumuPieprasijumsController::class, 'updateBrivaisArtikuls'])
    ->middleware('auth')
    ->name('pasutijumu-pieprasijumi.brivie-artikuli.update');
Route::delete('pasutijumu-pieprasijumi/{pieprasijums}/brivie-artikuli/{brivaisArtikuls}', [PasutijumuPieprasijumsController::class, 'destroyBrivaisArtikuls'])
    ->middleware('auth')
    ->name('pasutijumu-pieprasijumi.brivie-artikuli.destroy');
Route::get('pasutijumu-pieprasijumi/{pieprasijums}/export/word', [PasutijumuPieprasijumsController::class, 'exportWord'])
    ->middleware('auth')
    ->name('pasutijumu-pieprasijumi.export.word');
Route::resource('pasutijumu-pieprasijumi', PasutijumuPieprasijumsController::class)
    ->parameters(['pasutijumu-pieprasijumi' => 'pieprasijums'])
    ->middleware('auth');
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
