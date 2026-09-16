@extends('layouts.app')
@section('title','Adjust stock')
@section('content')
<h1 class="text-xl font-bold mb-4">Adjust stock</h1>
<div class="grid lg:grid-cols-2 gap-4">
<form method="POST" action="{{ route('inventory.adjust') }}" class="bg-white border rounded-2xl p-6 space-y-4">@csrf
<h2 class="font-semibold">Manual adjustment</h2>
<div><label class="text-sm">Product *</label><select name="product_id" required class="mt-1 w-full border rounded-xl px-3 py-2">@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }})</option>@endforeach</select></div>
<div><label class="text-sm">Warehouse *</label><select name="warehouse_id" required class="mt-1 w-full border rounded-xl px-3 py-2">@foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach</select></div>
<div><label class="text-sm">Quantity delta * (e.g. 10 or -5)</label><input name="quantity" type="number" required class="mt-1 w-full border rounded-xl px-3 py-2"></div>
<div><label class="text-sm">Reason</label><input name="reason" class="mt-1 w-full border rounded-xl px-3 py-2" placeholder="Stocktake, damage, return…"></div>
<button class="bg-indigo-600 text-white px-5 py-2 rounded-xl text-sm">Apply</button></form>

<form method="POST" action="{{ route('inventory.transfer') }}" class="bg-white border rounded-2xl p-6 space-y-4">@csrf
<h2 class="font-semibold">Transfer between warehouses <span class="text-xs text-slate-400">(admin)</span></h2>
<div><label class="text-sm">Product *</label><select name="product_id" required class="mt-1 w-full border rounded-xl px-3 py-2">@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div>
<div class="grid grid-cols-2 gap-3">
<div><label class="text-sm">From *</label><select name="from_warehouse_id" required class="mt-1 w-full border rounded-xl px-3 py-2">@foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->code }}</option>@endforeach</select></div>
<div><label class="text-sm">To *</label><select name="to_warehouse_id" required class="mt-1 w-full border rounded-xl px-3 py-2">@foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->code }}</option>@endforeach</select></div>
</div>
<div><label class="text-sm">Quantity *</label><input name="quantity" type="number" min="1" required class="mt-1 w-full border rounded-xl px-3 py-2"></div>
<button class="bg-slate-900 text-white px-5 py-2 rounded-xl text-sm">Transfer</button></form>
</div>
@endsection
