<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index()
    {
        return Sale::latest()->paginate(5);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string',
            'total' => 'required|numeric'
        ]);

        $sale = Sale::create($validated);

        return response()->json($sale, 201);
    }
}
