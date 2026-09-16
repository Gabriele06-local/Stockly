@extends('layouts.app')
@section('title',$order->order_number)
@section('content')
<div class="flex items-center justify-between mb-4"><a href="{{ route('orders.index') }}" class="text-sm text-indigo-600">← Orders</a>
<div class="flex gap-2">
@if(!in_array($order->status instanceof \BackedEnum ? $order->status->value : $order->status, ['cancelled','completed']))
<form method="POST" action="{{ route('orders.cancel',$order) }}" onsubmit="return confirm('Cancel and restore stock?')">@csrf<button class="text-sm border px-4 py-2 rounded-xl bg-white text-red-600">Cancel</button></form>
@endif
</div></div>

<div class="grid lg:grid-cols-3 gap-4">
<div class="lg:col-span-2 bg-white border rounded-2xl p-6">
<div class="flex items-center justify-between"><div><div class="text-xs text-slate-400">{{ $order->created_at->format('d/m/Y H:i') }} · by {{ $order->user->name ?? '—' }}</div><h1 class="text-2xl font-bold">{{ $order->order_number }}</h1></div>
<span class="text-xs px-3 py-1.5 rounded-full bg-slate-900 text-white">{{ $order->status instanceof \BackedEnum ? $order->status->value : $order->status }}</span></div>
<div class="text-sm mt-2">Customer: <a href="{{ route('customers.show',$order->customer) }}" class="text-indigo-600">{{ $order->customer->name }}</a> · Warehouse: {{ $order->warehouse->name }}</div>
<table class="w-full text-sm mt-4"><thead class="text-slate-400 text-left"><tr><th class="py-2">Product</th><th class="text-right">Qty</th><th class="text-right">Unit</th><th class="text-right">Total</th></tr></thead>
<tbody>@foreach($order->items as $it)<tr class="border-t"><td class="py-2">{{ $it->product->name }}<div class="text-xs text-slate-400">{{ $it->product->sku }}</div></td><td class="text-right">{{ $it->quantity }}</td><td class="text-right">€ {{ number_format($it->unit_price,2) }}</td><td class="text-right font-semibold">€ {{ number_format($it->total,2) }}</td></tr>@endforeach</tbody></table>
<div class="mt-4 text-sm space-y-1 text-right"><div>Subtotal: € {{ number_format($order->subtotal,2) }}</div><div>Discount: −€ {{ number_format($order->discount_amount,2) }}</div><div>Tax ({{ (float)$order->tax_rate*100 }}%): € {{ number_format($order->tax_amount,2) }}</div><div class="text-lg font-bold">Total: € {{ number_format($order->total,2) }}</div></div>
@if($order->notes)<div class="mt-3 text-sm bg-slate-50 rounded-xl p-3">{{ $order->notes }}</div>@endif
</div>

<div class="space-y-4">
<div class="bg-white border rounded-2xl p-5">
<h2 class="font-semibold text-sm mb-2">Update status</h2>
<form method="POST" action="{{ route('orders.status',$order) }}" class="flex gap-2">@csrf
<select name="status" class="flex-1 border rounded-xl px-3 py-2 text-sm">@foreach(['draft','confirmed','paid','shipped','completed','cancelled'] as $s)<option @selected(($order->status instanceof \BackedEnum ? $order->status->value : $order->status)==$s)>{{ $s }}</option>@endforeach</select>
<button class="bg-slate-900 text-white px-4 py-2 rounded-xl text-sm">Save</button></form>
<p class="text-xs text-slate-400 mt-2">Confirming consumes stock · Cancelling restores it (transactional).</p>
</div>
<div class="bg-white border rounded-2xl p-5">
<h2 class="font-semibold text-sm mb-2">Payments — paid € {{ number_format($order->payments->sum('amount'),2) }} / due € {{ number_format(max(0,$order->total-$order->payments->sum('amount')),2) }}</h2>
<ul class="text-sm space-y-1 mb-3">@foreach($order->payments as $pay)<li class="flex justify-between border-t py-1.5"><span>{{ $pay->paid_at?->format('d/m/Y') }} · {{ $pay->method instanceof \BackedEnum ? $pay->method->value : $pay->method }}</span><b>€ {{ number_format($pay->amount,2) }}</b></li>@endforeach</ul>
<form method="POST" action="{{ route('orders.payments',$order) }}" class="grid grid-cols-2 gap-2">@csrf
<input name="amount" type="number" step="0.01" min="0.01" placeholder="Amount" required class="border rounded-xl px-3 py-2 text-sm">
<select name="method" class="border rounded-xl px-3 py-2 text-sm"><option value="cash">cash</option><option value="card">card</option><option value="bank_transfer">bank_transfer</option><option value="other">other</option></select>
<input name="reference" placeholder="Reference (optional)" class="col-span-2 border rounded-xl px-3 py-2 text-sm">
<button class="col-span-2 bg-emerald-600 text-white py-2 rounded-xl text-sm">Record payment</button></form>
</div>
</div>
</div>
@endsection
