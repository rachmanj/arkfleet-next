<?php

use App\Http\Controllers\Operations\DocumentController;
use App\Http\Controllers\Operations\MovingController;
use App\Http\Controllers\Reports\NlqController;
use App\Http\Controllers\Reports\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:view'])->group(function () {
    Route::get('movings', [MovingController::class, 'index'])->name('movings.index');
    Route::get('movings/create', [MovingController::class, 'create'])->name('movings.create');
    Route::post('movings', [MovingController::class, 'store'])->name('movings.store');
    Route::get('movings/{moving}/edit', [MovingController::class, 'edit'])->name('movings.edit');
    Route::put('movings/{moving}', [MovingController::class, 'update'])->name('movings.update');
    Route::delete('movings/{moving}', [MovingController::class, 'destroy'])->name('movings.destroy');

    Route::get('movings/{moving}/equipment', [MovingController::class, 'addEquipment'])->name('movings.equipment');
    Route::post('movings/{moving}/cart', [MovingController::class, 'addToCart'])->name('movings.cart.add');
    Route::delete('movings/{moving}/cart/{cartItem}', [MovingController::class, 'removeFromCart'])->name('movings.cart.remove');

    Route::post('movings/{moving}/submit', [MovingController::class, 'submit'])->name('movings.submit');
    Route::post('movings/{moving}/approve', [MovingController::class, 'approve'])->name('movings.approve');

    Route::get('movings/{moving}/show', [MovingController::class, 'show'])->name('movings.show');
    Route::get('movings/{moving}/pdf', [MovingController::class, 'pdf'])->name('movings.pdf');

    Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::put('documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
    Route::post('documents/{document}/extend', [DocumentController::class, 'extend'])->name('documents.extend');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/expiring-documents', [ReportController::class, 'expiringDocuments'])->name('reports.expiring-documents');
    Route::get('reports/ipa-summary', [ReportController::class, 'ipaSummary'])->name('reports.ipa-summary');
    Route::get('reports/active-equipment', [ReportController::class, 'activeEquipmentStatus'])->name('reports.active-equipment');
    Route::get('reports/expiring-documents/export/{format}', [ReportController::class, 'exportExpiringDocuments'])->name('reports.expiring-documents.export');
    Route::get('reports/ipa-summary/export/{format}', [ReportController::class, 'exportIpaSummary'])->name('reports.ipa-summary.export');
    Route::get('reports/active-equipment/export/{format}', [ReportController::class, 'exportActiveEquipment'])->name('reports.active-equipment.export');

    Route::get('reports/ai-nlq', [NlqController::class, 'index'])->name('reports.nlq');
    Route::post('reports/ai-nlq', [NlqController::class, 'ask'])->name('reports.nlq.ask');
});
