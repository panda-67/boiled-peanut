<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('type');

        return Location::where('is_active', true)
            ->when($type, fn($q, $t) => $q->where('type', $t))
            ->get(['_id', 'name'])
            ->map(fn($location) => [
                'id' => $location->_id,
                'name' => $location->name
            ]);
    }
}
