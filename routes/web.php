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

    if ($role === 'farmaceiti') {
        return redirect()->route('pasutijumi.index');
    }

    // default for brivibas, kruzes, others
    return redirect()->route('pieprasijumi.index');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
});

Route::resource('pasutijumi', PasutijumiController::class);
Route::resource('pharmacies', PharmacyController::class);
Route::resource('artikuli', ProductController::class);
Route::get('/pieprasijumi/bulk-complete', [RequestController::class, 'bulkComplete'])
    ->name('pieprasijumi.bulkComplete');
Route::resource('pieprasijumi', RequestController::class);
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
