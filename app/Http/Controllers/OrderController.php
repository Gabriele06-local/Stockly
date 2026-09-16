<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orders) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $orders = Order::with(['customer', 'warehouse'])
            ->when($request->filled('search'), fn ($q) => $q->where('order_number', 'like', '%'.$request->search.'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
            ->orderBy('created_at', 'desc')
            ->paginate(12)->withQueryString();

        return view('orders.index', compact('orders'));
    }

    public function create()
    {
        $this->authorize('create', Order::class);
        $customers = Customer::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        $products = Product::active()->with('inventories')->orderBy('name')->limit(200)->get();
        $defaultWarehouse = $warehouses->firstWhere('is_default', true) ?? $warehouses->first();

        return view('orders.create', compact('customers', 'warehouses', 'products', 'defaultWarehouse'));
    }

    public function store(StoreOrderRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        try {
            $order = $this->orders->create($data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('orders.show', $order)->with('success', "Order {$order->order_number} created.");
    }

    public function show(Order $order)
    {
        $this->authorize('view', $order);
        $order->load(['customer', 'warehouse', 'user', 'items.product', 'payments.user']);

        return view('orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
        ]);

        try {
            $newStatus = $validated['status'] instanceof OrderStatus
                ? $validated['status']
                : OrderStatus::from($validated['status']);
            $order = $this->orders->updateStatus($order, $newStatus, $request->user()->id);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Order status updated to '.$order->status->value.'.');
    }

    public function cancel(Order $order, Request $request)
    {
        $this->authorize('cancel', $order);

        $order = $this->orders->cancel($order, $request->user()->id);

        return back()->with('success', 'Order cancelled and stock restored.');
    }

    public function addPayment(StorePaymentRequest $request, Order $order)
    {
        $this->authorize('update', $order);

        $data = $request->validated();

        if ($data['amount'] > $order->balanceDue() + 0.009) {
            return back()->with('error', 'Payment exceeds balance due ('.number_format($order->balanceDue(), 2).').');
        }

        $order->payments()->create([
            'amount' => $data['amount'],
            'method' => $data['method'],
            'reference' => $data['reference'] ?? null,
            'paid_at' => $data['paid_at'] ?? now(),
            'user_id' => $request->user()->id,
        ]);

        // Auto-mark paid if fully covered
        if ($order->fresh()->isPaid() && in_array($order->status->value, ['confirmed', 'draft'])) {
            $order->update(['status' => OrderStatus::Paid]);
        }

        return back()->with('success', 'Payment recorded.');
    }

    public function destroy(Order $order)
    {
        $this->authorize('delete', $order);

        if (! in_array($order->status->value, ['draft', 'cancelled'])) {
            return back()->with('error', 'Only draft/cancelled orders can be deleted. Cancel it first.');
        }
        $order->delete();

        return redirect()->route('orders.index')->with('success', 'Order deleted.');
    }
}
