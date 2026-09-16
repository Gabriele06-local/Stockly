<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function kpis(): array
    {
        $revenueToday = (float) Order::whereNotIn('status', ['draft', 'cancelled'])
            ->whereDate('created_at', today())->sum('total');

        $revenueMonth = (float) Order::whereNotIn('status', ['draft', 'cancelled'])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');

        $ordersMonth = Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)->count();

        $avgTicket = $ordersMonth > 0
            ? round(Order::whereNotIn('status', ['draft', 'cancelled'])
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)->avg('total') ?? 0, 2)
            : 0;

        $lowStockCount = Inventory::lowStock()->count();
        $outOfStockCount = Inventory::where('quantity', '<=', 0)->count();
        $productsCount = Product::count();
        $customersCount = Customer::count();

        $stockValue = (float) DB::table('inventories')
            ->join('products', 'products.id', '=', 'inventories.product_id')
            ->selectRaw('COALESCE(SUM(inventories.quantity * products.cost),0) as v')
            ->value('v');

        return compact(
            'revenueToday', 'revenueMonth', 'ordersMonth', 'avgTicket',
            'lowStockCount', 'outOfStockCount', 'productsCount', 'customersCount', 'stockValue'
        );
    }

    public function revenueLast14Days(): array
    {
        $rows = Order::selectRaw('DATE(created_at) as d, SUM(total) as total')
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->groupBy('d')->orderBy('d')->get()->keyBy('d');

        $labels = [];
        $data = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $labels[] = $day;
            $data[] = round((float) ($rows[$day]->total ?? 0), 2);
        }

        return compact('labels', 'data');
    }

    public function topProducts(int $limit = 5): Collection
    {
        return DB::table('order_items')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNotIn('orders.status', ['draft', 'cancelled'])
            ->selectRaw('products.id, products.name, products.sku, SUM(order_items.quantity) as qty, SUM(order_items.total) as revenue')
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('revenue')->limit($limit)->get();
    }

    public function lowStockItems(int $limit = 8): Collection
    {
        return Inventory::with(['product', 'warehouse'])
            ->lowStock()->orderBy('quantity')->limit($limit)->get();
    }

    public function recentOrders(int $limit = 8): Collection
    {
        return Order::with(['customer', 'warehouse'])
            ->latest()->limit($limit)->get();
    }
}
