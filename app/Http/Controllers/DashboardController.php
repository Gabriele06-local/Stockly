<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $dashboard) {}

    public function index(Request $request)
    {
        $kpis = $this->dashboard->kpis();
        $revenue = $this->dashboard->revenueLast14Days();
        $topProducts = $this->dashboard->topProducts();
        $lowStock = $this->dashboard->lowStockItems();
        $recentOrders = $this->dashboard->recentOrders();

        return view('dashboard', compact('kpis', 'revenue', 'topProducts', 'lowStock', 'recentOrders'));
    }
}
