<?php

use App\Http\Controllers\Masters\BusinessPartnerController;
use App\Http\Controllers\Masters\DepartmentController;
use App\Http\Controllers\Masters\EquipmentController;
use App\Http\Controllers\Masters\ProjectController;
use App\Http\Controllers\Sap\SapIntegrationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:view'])->prefix('masters')->name('masters.')->group(function () {
    Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
    Route::get('business-partners', [BusinessPartnerController::class, 'index'])->name('business-partners.index');
});

Route::middleware(['auth', 'permission:view'])->group(function () {
    Route::get('equipment', [EquipmentController::class, 'index'])->name('equipment.index');
    Route::get('equipment/{equipment}', [EquipmentController::class, 'show'])->name('equipment.show');
    Route::get('equipment/{equipment}/payreq-summary', [EquipmentController::class, 'payreqSummary'])->name('equipment.payreq-summary');
    Route::post('equipment', [EquipmentController::class, 'store'])->name('equipment.store');
    Route::post('equipment/update-rfu', [EquipmentController::class, 'updateRfu'])->name('equipment.update-rfu');
    Route::post('equipment/update-bd', [EquipmentController::class, 'updateBd'])->name('equipment.update-bd');
    Route::put('equipment/{equipment}', [EquipmentController::class, 'update'])->name('equipment.update');
    Route::post('equipment/{equipment}/photos', [EquipmentController::class, 'storePhoto'])->name('equipment.photos.store');
    Route::delete('equipment/photos/{photo}', [EquipmentController::class, 'destroyPhoto'])->name('equipment.photos.destroy');
    Route::post('equipment/{equipment}/unit-no-history', [EquipmentController::class, 'storeUnitNoHistory'])->name('equipment.unit-no-history.store');
});

Route::middleware(['auth', 'permission:sync'])->group(function () {
    Route::post('masters/projects/sync', [ProjectController::class, 'sync'])->name('masters.projects.sync');
    Route::post('masters/departments/sync', [DepartmentController::class, 'sync'])->name('masters.departments.sync');
    Route::post('masters/business-partners/sync', [BusinessPartnerController::class, 'sync'])->name('masters.business-partners.sync');
    Route::get('sap/sync', [SapIntegrationController::class, 'syncRuns'])->name('sap.sync.index');
});

Route::middleware(['auth', 'permission:manage-visibility'])->group(function () {
    Route::patch('masters/projects/{project}/visibility', [ProjectController::class, 'toggleVisibility'])->name('masters.projects.visibility');
    Route::patch('masters/departments/{department}/visibility', [DepartmentController::class, 'toggleVisibility'])->name('masters.departments.visibility');
});

Route::middleware(['auth', 'permission:sap.post'])->group(function () {
    Route::get('sap/posting-logs', [SapIntegrationController::class, 'postingLogs'])->name('sap.posting-logs.index');
});
