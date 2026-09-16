<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orders) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $orders = Order::with(['customer', 'warehouse', 'items.product', 'payments'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->filled('search'), fn ($q) => $q->where('order_number', 'like', '%'.$request->search.'%'))
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        return OrderResource::collection($orders);
    }

    public function show(Order $order)
    {
        $this->authorize('view', $order);

        return new OrderResource($order->load(['customer', 'warehouse', 'items.product', 'payments']));
    }

    public function store(\App\Http\Requests\StoreOrderRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $order = $this->orders->create($data);

        return (new OrderResource($order->load(['customer', 'warehouse', 'items.product', 'payments'])))
            ->response()->setStatusCode(201);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:draft,confirmed,paid,shipped,completed,cancelled'],
        ]);

        $order = $this->orders->updateStatus($order, OrderStatus::from($validated['status']), $request->user()->id);

        return new OrderResource($order->load(['customer', 'warehouse', 'items.product', 'payments']));
    }

    public function cancel(Order $order, Request $request)
    {
        $this->authorize('cancel', $order);
        $order = $this->orders->cancel($order, $request->user()->id);

        return new OrderResource($order->load(['customer', 'warehouse', 'items.product']));
    }
}
