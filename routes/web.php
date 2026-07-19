<?php

use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Settings\ApiTokenController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('change-password', [ChangePasswordController::class, 'edit'])->name('password.change');
    Route::put('change-password', [ChangePasswordController::class, 'update'])->name('password.update');
    Route::post('logout', LogoutController::class)->name('logout');

    Route::middleware('permission:view')->prefix('settings')->group(function () {
        Route::get('api-keys', [ApiTokenController::class, 'index'])->name('settings.api-keys.index');
        Route::post('api-keys', [ApiTokenController::class, 'store'])->name('settings.api-keys.store');
        Route::delete('api-keys/{tokenId}', [ApiTokenController::class, 'destroy'])->name('settings.api-keys.destroy');
    });
});
