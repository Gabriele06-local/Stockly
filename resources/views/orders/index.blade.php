@extends('layouts.app')
@section('title','Orders')
@section('content')
<div class="flex items-center justify-between mb-4"><h1 class="text-xl font-bold">Orders</h1><a href="{{ route('orders.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm">+ New order</a></div>
<form method="GET" class="bg-white border rounded-2xl p-3 mb-4 flex flex-wrap gap-2">
<input name="search" value="{{ request('search') }}" placeholder="Order number…" class="border rounded-xl px-3 py-2 text-sm flex-1 min-w-[160px]">
<select name="status" class="border rounded-xl px-3 py-2 text-sm"><option value="">All statuses</option>@foreach(['draft','confirmed','paid','shipped','completed','cancelled'] as $s)<option @selected(request('status')==$s)>{{ $s }}</option>@endforeach</select>
<button class="bg-slate-900 text-white px-4 py-2 rounded-xl text-sm">Filter</button></form>
<div class="bg-white border rounded-2xl overflow-hidden"><table class="w-full text-sm">
<thead class="bg-slate-50 text-left text-slate-500"><tr><th class="px-4 py-3">Order</th><th class="px-4 py-3">Customer</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Total</th></tr></thead>
<tbody>@forelse($orders as $o)<tr class="border-t hover:bg-slate-50">
<td class="px-4 py-3"><a href="{{ route('orders.show',$o) }}" class="font-medium text-indigo-600">{{ $o->order_number }}</a><div class="text-xs text-slate-400">{{ $o->created_at->format('d/m/Y H:i') }} · {{ $o->warehouse->code ?? '' }}</div></td>
<td class="px-4 py-3">{{ $o->customer->name }}</td>
<td class="px-4 py-3"><span class="text-xs px-2 py-1 rounded-full bg-slate-100">{{ $o->status instanceof \BackedEnum ? $o->status->value : $o->status }}</span></td>
<td class="px-4 py-3 text-right font-semibold">€ {{ number_format($o->total,2) }}</td></tr>
@empty<tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">No orders.</td></tr>@endforelse</tbody></table>
<div class="p-3">{{ $orders->links() }}</div></div>
@endsection
