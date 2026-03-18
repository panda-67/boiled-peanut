<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Reports\Integrity\CashDifferenceReport;
use App\Reports\Integrity\ProductionVsSalesReport;
use App\Reports\Sales\DailyProfitReport;
use App\Reports\Sales\DailySalesDetailReport;
use App\Reports\Sales\DailySalesSummaryReport;
use App\Reports\Sales\OutstandingSalesReport;
use App\Reports\Settlements\DailySettlementReport;
use App\Reports\Stocks\DailyMaterialUsageReport;
use App\Reports\Stocks\MaterialLedgerReport;
use App\Reports\Stocks\MaterialStockReport;
use App\Reports\Stocks\ProductStockReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function summary(
        Request $request,
        DailySalesSummaryReport $salesSummary,
        DailyProfitReport $profitReport,
        DailySettlementReport $settlementReport
    ): JsonResponse {

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date'],
            'location_id' => ['nullable', 'string', 'exists:locations,_id']
        ]);

        $start = Carbon::parse($validated['start_date']);
        $end   = Carbon::parse($validated['end_date']);
        $locationId = $validated['location_id'] ?? null;

        $sales = $salesSummary->between($start, $end, $locationId);
        $profit = $profitReport->between($start, $end, $locationId);
        $settlement = $settlementReport->between($start, $end, $locationId);

        return response()->json([
            'total_sales'     => (float) $sales->total_sales,
            'transactions'    => (int) $sales->transactions,
            'avg_transaction' => round((float) $sales->avg_transaction, 2),
            'cogs'            => (float) $profit['cogs'],
            'gross_profit'    => (float) $profit['gross_profit'],
            'cash_total'      => (float) $settlement->cash_total,
            'transfer_total'  => (float) $settlement->transfer_total,
            'ewallet_total'   => (float) $settlement->ewallet_total
        ]);
    }

    public function salesDetail(Request $request, DailySalesDetailReport $report): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date'],
        ]);

        $start = Carbon::parse($validated['start_date']);
        $end   = Carbon::parse($validated['end_date']);

        $sales = $report->forDate($start, $end);

        return response()->json([
            'data' => $sales,
            'message' => 'Success'
        ]);
    }

    public function cashDifference(CashDifferenceReport $report): JsonResponse
    {
        return response()->json([
            'data' => $report->history(),
            'message' => 'Success'
        ]);
    }

    public function productionVsSales(ProductionVsSalesReport $report): JsonResponse
    {
        return response()->json([
            'data' => $report->summary(),
            'message' => 'Success'
        ]);
    }

    public function outstandingSales(OutstandingSalesReport $report): JsonResponse
    {
        return response()->json([
            'data' => $report->all()
        ]);
    }

    public function productStock(ProductStockReport $report): JsonResponse
    {
        return response()->json([
            'data' => $report->current()
        ]);
    }

    public function materialStock(MaterialStockReport $report): JsonResponse
    {
        return response()->json([
            'data' => $report->current()
        ]);
    }

    public function materialLedger(Material  $material, MaterialLedgerReport $report): JsonResponse
    {
        return response()->json([
            'data' => $report->forMaterial($material->id)
        ]);
    }

    public function dailyMaterialUsage(Request $request, DailyMaterialUsageReport $report): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date'],
        ]);

        $start = Carbon::parse($validated['start_date']);
        $end   = Carbon::parse($validated['end_date']);

        return response()->json([
            'data' => $report->forDate($start, $end)
        ]);
    }
}
