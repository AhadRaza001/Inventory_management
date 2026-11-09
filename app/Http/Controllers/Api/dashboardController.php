<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Purchase_order;
use App\Models\Sale_order;
use App\Models\Supplier;
use Carbon\Carbon;

class dashboardController extends ResponseController
{
    public function index()
    {
        try {
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            $data = [
                'counts' => [
                    'items' => Item::count(),
                    'customers' => Customer::count(),
                    'suppliers' => Supplier::count(),
                    'categories' => Category::count(),
                ],
                'sales_this_month' => (float) Sale_order::whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->where('status', '!=', 'cancelled')
                    ->sum('grand_total'),
                'purchases_this_month' => (float) Purchase_order::whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->where('status', '!=', 'cancelled')
                    ->sum('grand_total'),
                // Your orders are 'open' until fully delivered
                'pending_sale_orders' => Sale_order::where('status', 'open')->count(),
                'pending_purchase_orders' => Purchase_order::where('status', 'open')->count(),
                'monthly_totals' => $this->monthlyTotals(),
                'low_stock_items' => $this->lowStockItems(),
                'recent_sale_orders' => $this->recentSaleOrders(),
                'recent_purchase_orders' => $this->recentPurchaseOrders(),
            ];

            return $this->sendResponse($data, 'Dashboard data retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->sendError('Failed to load dashboard.', [$e->getMessage()], 500);
        }
    }

    // Last 6 months, oldest first, so the chart reads left to right
    private function monthlyTotals()
    {
        $result = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $sales = Sale_order::whereBetween('created_at', [$start, $end])
                ->where('status', '!=', 'cancelled')
                ->sum('grand_total');

            $purchases = Purchase_order::whereBetween('created_at', [$start, $end])
                ->where('status', '!=', 'cancelled')
                ->sum('grand_total');

            $result[] = [
                'month' => $month->format('M Y'),
                'sales' => (float) $sales,
                'purchases' => (float) $purchases,
            ];
        }

        return $result;
    }

    // Stock comes from store_stocks, added up across all stores for each item
    private function lowStockItems()
    {
        $items = Item::where('status', 'active')
            ->withSum('stocks as total_stock', 'qty')
            ->get();

        $low = [];
        foreach ($items as $item) {
            $stock = (float) ($item->total_stock ?? 0);

            // reorder_level 0 means "not tracked", so skip those
            if ($item->reorder_level > 0 && $stock <= $item->reorder_level) {
                $low[] = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'stock_qty' => $stock,
                    'reorder_level' => (float) $item->reorder_level,
                ];
            }
        }

        // Lowest stock first, show only 8
        usort($low, function ($a, $b) {
            return $a['stock_qty'] <=> $b['stock_qty'];
        });

        return array_slice($low, 0, 8);
    }

    private function recentSaleOrders()
    {
        $orders = Sale_order::with('customer')->orderByDesc('id')->limit(5)->get();

        $result = [];
        foreach ($orders as $order) {
            $result[] = [
                'id' => $order->id,
                'order_no' => $order->so_no,
                'party_name' => $order->customer ? $order->customer->name : '-',
                'created_at' => $order->created_at,
                'grand_total' => (float) $order->grand_total,
                'status' => $order->status,
            ];
        }

        return $result;
    }

    private function recentPurchaseOrders()
    {
        $orders = Purchase_order::with('supplier')->orderByDesc('id')->limit(5)->get();

        $result = [];
        foreach ($orders as $order) {
            $result[] = [
                'id' => $order->id,
                'order_no' => $order->po_no,
                'party_name' => $order->supplier ? $order->supplier->name : '-',
                'created_at' => $order->created_at,
                'grand_total' => (float) $order->grand_total,
                'status' => $order->status,
            ];
        }

        return $result;
    }
}
