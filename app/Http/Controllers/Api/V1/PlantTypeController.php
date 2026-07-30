<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PlantType;
use Illuminate\Http\Request;

class PlantTypeController extends Controller
{
    public function index(Request $request)
    {
        $plantTypes = PlantType::query()
            ->select(['id', 'name', 'is_active'])
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $plantTypes]);
    }
}
