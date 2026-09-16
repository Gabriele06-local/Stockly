@extends('layouts.app')
@section('title','Customers')
@section('content')
<div class="flex items-center justify-between mb-4"><h1 class="text-xl font-bold">Customers</h1><a href="{{ route('customers.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm">+ Customer</a></div>
<form method="GET" class="bg-white border rounded-2xl p-3 mb-4 flex gap-2"><input name="search" value="{{ request('search') }}" placeholder="Search name / email / company…" class="border rounded-xl px-3 py-2 text-sm flex-1"><button class="bg-slate-900 text-white px-4 py-2 rounded-xl text-sm">Search</button></form>
<div class="bg-white border rounded-2xl overflow-hidden"><table class="w-full text-sm">
<thead class="bg-slate-50 text-left text-slate-500"><tr><th class="px-4 py-3">Customer</th><th class="px-4 py-3">Contact</th><th class="px-4 py-3 text-right">Orders</th><th class="px-4 py-3 text-right">Spent</th></tr></thead>
<tbody>@forelse($customers as $c)<tr class="border-t hover:bg-slate-50"><td class="px-4 py-3"><a href="{{ route('customers.show',$c) }}" class="font-medium text-indigo-600 hover:underline">{{ $c->name }}</a>@if($c->company)<div class="text-xs text-slate-400">{{ $c->company }}</div>@endif</td>
<td class="px-4 py-3 text-xs">{{ $c->email }}<br>{{ $c->phone }}</td><td class="px-4 py-3 text-right">{{ $c->orders_count }}</td><td class="px-4 py-3 text-right font-semibold">€ {{ number_format($c->total_spent,2) }}</td></tr>
@empty<tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">No customers.</td></tr>@endforelse</tbody></table>
<div class="p-3">{{ $customers->links() }}</div></div>
@endsection
