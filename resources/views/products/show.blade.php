@extends('layouts.app')
@section('title',$product->name)
@section('content')
<div class="flex items-center justify-between mb-4">
  <a href="{{ route('products.index') }}" class="text-sm text-indigo-600">← Products</a>
  <div class="flex gap-2">
    <a href="{{ route('products.edit',$product) }}" class="text-sm border px-4 py-2 rounded-xl bg-white">Edit</a>
    @can('delete',$product)<form method="POST" action="{{ route('products.destroy',$product) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-sm bg-red-600 text-white px-4 py-2 rounded-xl">Delete</button></form>@endcan
  </div>
</div>
<div class="grid lg:grid-cols-3 gap-4">
  <div class="lg:col-span-2 bg-white border rounded-2xl p-6">
    <div class="text-xs text-slate-400">{{ $product->sku }} · {{ $product->category->name ?? 'Uncategorized' }}</div>
    <h1 class="text-2xl font-bold">{{ $product->name }}</h1>
    <p class="text-sm text-slate-500 mt-2">{{ $product->description ?? 'No description.' }}</p>
    <div class="flex gap-6 mt-4"><div><div class="text-xs text-slate-400">Price</div><div class="text-xl font-bold">€ {{ number_format($product->price,2) }}</div></div>
    <div><div class="text-xs text-slate-400">Cost</div><div class="text-xl font-bold">€ {{ number_format($product->cost ?? 0,2) }}</div></div>
    <div><div class="text-xs text-slate-400">Total stock</div><div class="text-xl font-bold">{{ $product->inventories->sum('quantity') }}</div></div></div>
  </div>
  <div class="bg-white border rounded-2xl p-6">
    <h2 class="font-semibold mb-3">Stock by warehouse</h2>
    @foreach($product->inventories as $inv)<div class="flex justify-between text-sm py-2 border-t"><span>{{ $inv->warehouse->name }} <span class="text-slate-400">{{ $inv->warehouse->code }}</span></span><span class="font-bold {{ $inv->quantity <= $inv->low_stock_threshold ? 'text-amber-600' : '' }}">{{ $inv->quantity }}</span></div>@endforeach
  </div>
</div>
<div class="bg-white border rounded-2xl p-6 mt-4">
  <h2 class="font-semibold mb-3">Recent movements</h2>
  <table class="w-full text-sm"><thead class="text-slate-400 text-left"><tr><th class="py-2">Date</th><th>Type</th><th>Warehouse</th><th class="text-right">Qty</th><th class="text-right">Balance</th></tr></thead>
  <tbody>@foreach($product->movements as $m)<tr class="border-t"><td>{{ $m->created_at->format('d/m/Y H:i') }}</td><td>{{ $m->type instanceof \BackedEnum ? $m->type->value : $m->type }}</td><td>{{ $m->warehouse->code ?? '' }}</td><td class="text-right">{{ $m->quantity }}</td><td class="text-right">{{ $m->balance_after }}</td></tr>@endforeach</tbody></table>
</div>
@endsection
