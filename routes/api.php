<?php

use App\Http\Controllers\Api\V1\DepreciationController;
use App\Http\Controllers\Api\V1\EquipmentController;
use App\Http\Controllers\Api\V1\FixedAssetController;
use App\Http\Controllers\Api\V1\ProjectController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'abilities:api:read', 'throttle:api'])->group(function () {
    Route::get('equipment', [EquipmentController::class, 'index']);
    Route::get('equipment/{equipment}', [EquipmentController::class, 'show']);

    Route::get('projects', [ProjectController::class, 'index']);
    Route::get('projects/{code}', [ProjectController::class, 'show']);

    Route::get('fixed-assets', [FixedAssetController::class, 'index']);
    Route::get('fixed-assets/{fixedAsset}', [FixedAssetController::class, 'show']);

    Route::get('depreciation/runs', [DepreciationController::class, 'runs']);
    Route::get('depreciation/runs/{run}', [DepreciationController::class, 'showRun']);
    Route::get('depreciation/entries', [DepreciationController::class, 'entries']);
});
