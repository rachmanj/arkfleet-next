<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AssetCategory;
use Illuminate\Http\Request;

class AssetCategoryController extends Controller
{
    public function index(Request $request)
    {
        $assetCategories = AssetCategory::query()
            ->select(['id', 'name', 'code', 'is_active'])
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $assetCategories]);
    }
}
