<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Material;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        $query = Material::query();

        // Optional filter: is_stocked
        if ($request->has('is_stocked')) {
            $query->where('is_stocked', $request->boolean('is_stocked'));
        }

        $materials = $query->get();

        // Optional: include stock per location
        if ($request->filled('location')) {
            $location = Location::where('_id', $request->location)->firstOfFail();

            $materials->transform(function ($material) use ($location) {
                return [
                    'id' => $material->id,
                    'name' => $material->name,
                    'unit' => $material->unit,
                    'default_unit_cost' => $material->default_unit_cost,
                    'stock' => $material->stockAt($location),
                ];
            });
        } else {
            $materials->transform(function ($material) {
                return [
                    'id' => $material->id,
                    'name' => $material->name,
                    'unit' => $material->unit,
                    'default_unit_cost' => $material->default_unit_cost,
                    'stock' => $material->stock(),
                ];
            });
        }

        return response()->json($materials);
    }
}
