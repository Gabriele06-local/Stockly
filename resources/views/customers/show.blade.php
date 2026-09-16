@extends('layouts.app')
@section('title',$customer->name)
@section('content')
<div class="flex items-center justify-between mb-4"><a href="{{ route('customers.index') }}" class="text-sm text-indigo-600">← Customers</a>
<div class="flex gap-2"><a href="{{ route('customers.edit',$customer) }}" class="text-sm border px-4 py-2 rounded-xl bg-white">Edit</a></div></div>
<div class="grid lg:grid-cols-3 gap-4">
<div class="lg:col-span-2 bg-white border rounded-2xl p-6">
<h1 class="text-2xl font-bold">{{ $customer->name }}</h1>
@if($customer->company)<div class="text-sm text-slate-500">{{ $customer->company }}</div>@endif
<div class="text-sm mt-3 space-y-1"><div>📧 {{ $customer->email ?? '—' }}</div><div>📞 {{ $customer->phone ?? '—' }}</div><div>📍 {{ $customer->address ?? '' }} {{ $customer->city ?? '' }}</div></div>
@if($customer->notes)<div class="mt-3 text-sm bg-slate-50 rounded-xl p-3">{{ $customer->notes }}</div>@endif
</div>
<div class="bg-white border rounded-2xl p-6"><div class="text-xs text-slate-400">Lifetime value</div><div class="text-3xl font-bold">€ {{ number_format($customer->total_spent,2) }}</div><div class="text-sm text-slate-500 mt-1">{{ $customer->orders->count() }} recent orders</div></div>
</div>
<div class="bg-white border rounded-2xl mt-4 p-6"><h2 class="font-semibold mb-3">Recent orders</h2>
<table class="w-full text-sm"><tbody>@foreach($customer->orders as $o)<tr class="border-t"><td class="py-2"><a href="{{ route('orders.show',$o) }}" class="text-indigo-600">{{ $o->order_number }}</a></td><td>{{ $o->status instanceof \BackedEnum ? $o->status->value : $o->status }}</td><td class="text-right font-semibold">€ {{ number_format($o->total,2) }}</td></tr>@endforeach</tbody></table></div>
@endsection
