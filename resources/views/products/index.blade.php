@extends('layouts.app')
@section('title','Products')
@section('content')
<div class="flex flex-wrap gap-3 items-center justify-between mb-4">
  <h1 class="text-xl font-bold">Products</h1>
  <a href="{{ route('products.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm">+ Product</a>
</div>
<form method="GET" class="bg-white border rounded-2xl p-3 mb-4 flex flex-wrap gap-2">
  <input name="search" value="{{ request('search') }}" placeholder="Search name / SKU…" class="border rounded-xl px-3 py-2 text-sm flex-1 min-w-[200px]">
  <select name="category_id" class="border rounded-xl px-3 py-2 text-sm"><option value="">All categories</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category_id')==$c->id)>{{ $c->name }}</option>@endforeach</select>
  <button class="bg-slate-900 text-white px-4 py-2 rounded-xl text-sm">Filter</button>
</form>
<div class="bg-white border rounded-2xl overflow-hidden">
<div class="overflow-x-auto"><table class="w-full text-sm">
<thead class="bg-slate-50 text-slate-500 text-left"><tr><th class="px-4 py-3">Product</th><th class="px-4 py-3">Category</th><th class="px-4 py-3 text-right">Price</th><th class="px-4 py-3 text-right">Stock</th><th class="px-4 py-3">Status</th></tr></thead>
<tbody>
@forelse($products as $p)
<tr class="border-t hover:bg-slate-50">
<td class="px-4 py-3"><a href="{{ route('products.show',$p) }}" class="font-medium text-indigo-600 hover:underline">{{ $p->name }}</a><div class="text-xs text-slate-400">{{ $p->sku }}</div></td>
<td class="px-4 py-3">{{ $p->category->name ?? '—' }}</td>
<td class="px-4 py-3 text-right font-semibold">€ {{ number_format($p->price,2) }}</td>
<td class="px-4 py-3 text-right">{{ $p->inventories->sum('quantity') }}</td>
<td class="px-4 py-3">@if($p->is_active)<span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">Active</span>@else<span class="text-xs bg-slate-200 px-2 py-1 rounded-full">Archived</span>@endif</td>
</tr>
@empty<tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">No products.</td></tr>@endforelse
</tbody></table></div>
<div class="p-3">{{ $products->links() }}</div>
</div>
@endsection
