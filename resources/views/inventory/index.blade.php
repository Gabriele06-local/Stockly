@extends('layouts.app')
@section('title','Inventory')
@section('content')
<div class="flex items-center justify-between mb-4"><h1 class="text-xl font-bold">Inventory</h1>
<div class="flex gap-2"><a href="{{ route('inventory.adjust.form') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm">Adjust stock</a></div></div>
<form method="GET" class="bg-white border rounded-2xl p-3 mb-4 flex flex-wrap gap-2">
<input name="search" value="{{ request('search') }}" placeholder="Search product / SKU…" class="border rounded-xl px-3 py-2 text-sm flex-1 min-w-[180px]">
<select name="warehouse_id" class="border rounded-xl px-3 py-2 text-sm"><option value="">All warehouses</option>@foreach($warehouses as $w)<option value="{{ $w->id }}" @selected(request('warehouse_id')==$w->id)>{{ $w->name }}</option>@endforeach</select>
<label class="flex items-center gap-2 text-sm border rounded-xl px-3"><input type="checkbox" name="low_stock" value="1" @checked(request('low_stock'))> Low stock only</label>
<button class="bg-slate-900 text-white px-4 py-2 rounded-xl text-sm">Filter</button></form>
<div class="bg-white border rounded-2xl overflow-hidden"><table class="w-full text-sm">
<thead class="bg-slate-50 text-left text-slate-500"><tr><th class="px-4 py-3">Product</th><th class="px-4 py-3">Warehouse</th><th class="px-4 py-3 text-right">Qty</th><th class="px-4 py-3 text-right">Threshold</th><th class="px-4 py-3">Alert</th></tr></thead>
<tbody>@forelse($query as $inv)<tr class="border-t {{ $inv->quantity <= $inv->low_stock_threshold ? 'bg-amber-50' : '' }}">
<td class="px-4 py-3 font-medium">{{ $inv->product->name }}<div class="text-xs text-slate-400">{{ $inv->product->sku }}</div></td>
<td class="px-4 py-3">{{ $inv->warehouse->code }}</td><td class="px-4 py-3 text-right font-bold">{{ $inv->quantity }}</td><td class="px-4 py-3 text-right">{{ $inv->low_stock_threshold }}</td>
<td class="px-4 py-3">@if($inv->quantity<=0)<span class="text-xs bg-red-600 text-white px-2 py-1 rounded-full">Out</span>@elseif($inv->quantity<=$inv->low_stock_threshold)<span class="text-xs bg-amber-500 text-white px-2 py-1 rounded-full">Low</span>@else<span class="text-xs text-slate-400">OK</span>@endif</td></tr>
@empty<tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">No inventory lines.</td></tr>@endforelse</tbody></table>
<div class="p-3">{{ $query->links() }}</div></div>
@endsection
