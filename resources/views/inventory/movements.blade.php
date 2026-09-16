@extends('layouts.app')
@section('title','Movements')
@section('content')
<h1 class="text-xl font-bold mb-4">Inventory movements</h1>
<div class="bg-white border rounded-2xl overflow-hidden"><table class="w-full text-sm">
<thead class="bg-slate-50 text-left text-slate-500"><tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Product</th><th class="px-4 py-3">Wh</th><th class="px-4 py-3">Type</th><th class="px-4 py-3 text-right">Delta</th><th class="px-4 py-3 text-right">Balance</th><th class="px-4 py-3">Reason</th></tr></thead>
<tbody>@foreach($movements as $m)<tr class="border-t"><td class="px-4 py-2 text-xs">{{ $m->created_at->format('d/m/Y H:i') }}</td><td class="px-4 py-2">{{ $m->product->name ?? '#'.$m->product_id }}</td><td class="px-4 py-2">{{ $m->warehouse->code ?? '' }}</td><td class="px-4 py-2">{{ $m->type instanceof \BackedEnum ? $m->type->value : $m->type }}</td><td class="px-4 py-2 text-right font-semibold {{ $m->quantity<0?'text-red-600':'text-emerald-600' }}">{{ $m->quantity>0?'+':'' }}{{ $m->quantity }}</td><td class="px-4 py-2 text-right">{{ $m->balance_after }}</td><td class="px-4 py-2 text-xs text-slate-500">{{ $m->reason }}</td></tr>@endforeach</tbody></table>
<div class="p-3">{{ $movements->links() }}</div></div>
@endsection
