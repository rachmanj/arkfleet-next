<?php

use App\Http\Controllers\Finance\DepreciationController;
use App\Http\Controllers\Finance\FixedAssetController;
use App\Http\Controllers\Finance\LoanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:view'])->group(function () {
    Route::get('fixed-assets', [FixedAssetController::class, 'index'])->name('fixed-assets.index');
    Route::post('fixed-assets', [FixedAssetController::class, 'store'])->name('fixed-assets.store');
    Route::put('fixed-assets/{fixedAsset}', [FixedAssetController::class, 'update'])->name('fixed-assets.update');
    Route::get('fixed-assets/{fixedAsset}/schedule', [FixedAssetController::class, 'schedule'])->name('fixed-assets.schedule');
    Route::post('fixed-assets/{fixedAsset}/dispose', [FixedAssetController::class, 'dispose'])->name('fixed-assets.dispose');

    Route::get('depreciation', [DepreciationController::class, 'index'])->name('depreciation.index');
    Route::post('depreciation/run', [DepreciationController::class, 'run'])->name('depreciation.run');
    Route::get('depreciation/runs/{run}', [DepreciationController::class, 'show'])->name('depreciation.show');
    Route::get('depreciation/deferred-tax', [DepreciationController::class, 'deferredTax'])->name('depreciation.deferred-tax');

    Route::get('loans', [LoanController::class, 'index'])->name('loans.index');
    Route::post('loans', [LoanController::class, 'store'])->name('loans.store');
    Route::get('loans/{loan}', [LoanController::class, 'show'])->name('loans.show');
    Route::put('loans/{loan}', [LoanController::class, 'update'])->name('loans.update');
    Route::post('loans/{loan}/documents', [LoanController::class, 'uploadDocument'])->name('loans.documents.upload');
    Route::post('loans/{loan}/confirm-schedule', [LoanController::class, 'confirmParsedSchedule'])->name('loans.confirm-schedule');
    Route::put('loans/{loan}/installments/{installment}', [LoanController::class, 'updateInstallment'])->name('loans.installments.update');
    Route::post('loans/{loan}/installments/{installment}/confirm', [LoanController::class, 'confirmInstallment'])->name('loans.installments.confirm');
    Route::get('loans/{loan}/documents/{document}/download', [LoanController::class, 'downloadDocument'])->name('loans.documents.download');
});

Route::middleware(['auth', 'permission:sap.post'])->group(function () {
    Route::post('depreciation/runs/{run}/confirm', [DepreciationController::class, 'confirm'])->name('depreciation.confirm');
    Route::post('depreciation/runs/{run}/post-sap', [DepreciationController::class, 'postToSap'])->name('depreciation.post-sap');

    Route::post('loans/{loan}/installments/{installment}/post-ap', [LoanController::class, 'postApInvoice'])->name('loans.installments.post-ap');
    Route::post('loans/{loan}/installments/{installment}/post-payment', [LoanController::class, 'postPayment'])->name('loans.installments.post-payment');
});
