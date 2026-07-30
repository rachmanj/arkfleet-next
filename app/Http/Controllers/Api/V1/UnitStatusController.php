<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Unitstatus;
use Illuminate\Http\Request;

class UnitStatusController extends Controller
{
    public function index(Request $request)
    {
        $unitStatuses = Unitstatus::query()
            ->select(['id', 'name', 'color', 'is_active'])
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $unitStatuses]);
    }
}
