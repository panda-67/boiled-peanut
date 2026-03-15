<?php

namespace App\Http\Controllers\Api;

use App\Enums\LocationType;
use App\Http\Controllers\Controller;
use App\Models\BusinessDay;
use App\Models\Location;
use App\Services\BusinessDayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BusinessDayController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'location' => ['required', 'exists:locations,_id'],
        ]);

        $businessDay = BusinessDay::whereHas(
            'location',
            fn($q) => $q->where('_id', $request->location)
        )
            ->with(['location', 'openedBy', 'closedBy'])
            ->latest('date')
            ->first();

        if (!$businessDay) {
            return response()->json([], 204);
        }

        return response()->json([
            'id'            => $businessDay->id,
            'location_id'   => $businessDay->location->_id,
            'status'        => $businessDay->status,
            'opened_at'     => $businessDay->opened_at,
            'opened_by'     => $businessDay->openedBy?->name,
            'closed_at'     => $businessDay->closed_at,
            'closed_by'     => $businessDay->closedBy?->name,
        ]);
    }

    public function open(Request $request, BusinessDayService $service): JsonResponse
    {
        $validated = $request->validate([
            'location' => ['required', 'exists:locations,_id'],
        ]);

        /** @var \App\Models\Location $location */
        $location = Location::firstWhere('_id', $validated['location']);

        $this->authorize('open', [BusinessDay::class]);

        $service->open($location->id, $request->user()->id);

        return response()->json(['message' => "Business day for $location->name open successfully."], 201);
    }

    /**
     * Close the active business day for a location.
     *
     * The $receivedCash (amount) parameter represents the physical cash counted
     * during closing. It is required for sales_point locations and optional for
     * other location types.
     */
    public function close(Request $request, BusinessDayService $service): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'location' => ['required', 'exists:locations,_id'],
            'amount'   => ['nullable', 'numeric', 'min:0'],
        ]);

        $validator->sometimes('amount', 'required', function ($input) {
            $location = Location::firstWhere('_id', $input->location);
            return $location?->type === LocationType::SALE_POINT;
        });

        $validated = $validator->validate();

        /** @var \App\Models\Location $location */
        $location = Location::firstWhere('_id', $validated['location']);

        $this->authorize('close', $location->openBusinessDay);

        $service->close($location->id, $request->user()->id, $validated['amount'] ?? null);

        return response()->json(['message' => "Business day for $location->name closed successfully."], 201);
    }
}
