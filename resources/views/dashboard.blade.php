@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="flex items-center justify-between mb-6">
  <div>
    <h1 class="text-2xl font-bold">Dashboard</h1>
    <p class="text-sm text-slate-500">{{ now()->format('l, d M Y') }} · {{ config('app.name') }}</p>
  </div>
  <a href="{{ route('orders.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-medium">+ New order</a>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  @php $cards = [
    ['Revenue today', '€ '.number_format($kpis['revenueToday'],2), 'bg-indigo-600'],
    ['Revenue this month', '€ '.number_format($kpis['revenueMonth'],2), 'bg-emerald-600'],
    ['Orders this month', $kpis['ordersMonth'].' · avg € '.number_format($kpis['avgTicket'],2), 'bg-slate-900'],
    ['Stock value (cost)', '€ '.number_format($kpis['stockValue'],2), 'bg-amber-600'],
  ]; @endphp
  @foreach($cards as [$label,$value,$color])
  <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
    <div class="text-xs uppercase tracking-wide text-slate-500">{{ $label }}</div>
    <div class="text-xl font-bold mt-1">{{ $value }}</div>
  </div>
  @endforeach
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <div class="bg-white rounded-2xl p-4 border {{ $kpis['lowStockCount']>0 ? 'border-amber-300' : 'border-slate-200' }}">
    <div class="text-sm text-slate-500">Low-stock lines</div>
    <div class="text-2xl font-bold {{ $kpis['lowStockCount']>0 ? 'text-amber-600' : '' }}">{{ $kpis['lowStockCount'] }}</div>
    <a href="{{ route('inventory.index', ['low_stock'=>1]) }}" class="text-xs text-indigo-600 hover:underline">View alerts →</a>
  </div>
  <div class="bg-white rounded-2xl p-4 border">
    <div class="text-sm text-slate-500">Out of stock</div>
    <div class="text-2xl font-bold">{{ $kpis['outOfStockCount'] }}</div>
  </div>
  <div class="bg-white rounded-2xl p-4 border">
    <div class="text-sm text-slate-500">Products</div>
    <div class="text-2xl font-bold">{{ $kpis['productsCount'] }}</div>
  </div>
  <div class="bg-white rounded-2xl p-4 border">
    <div class="text-sm text-slate-500">Customers</div>
    <div class="text-2xl font-bold">{{ $kpis['customersCount'] }}</div>
  </div>
</div>

<div class="grid lg:grid-cols-3 gap-4">
  <div class="lg:col-span-2 bg-white rounded-2xl border p-5">
    <h2 class="font-semibold mb-3">Revenue — last 14 days</h2>
    <canvas id="revChart" height="120"></canvas>
  </div>
  <div class="bg-white rounded-2xl border p-5">
    <h2 class="font-semibold mb-3">Top products</h2>
    <ul class="space-y-2 text-sm">
      @forelse($topProducts as $p)
      <li class="flex justify-between"><span>{{ $p->name }} <span class="text-slate-400">×{{ $p->qty }}</span></span><span class="font-semibold">€ {{ number_format($p->revenue,2) }}</span></li>
      @empty<li class="text-slate-400">No sales yet.</li>@endforelse
    </ul>
  </div>
</div>

<div class="grid lg:grid-cols-2 gap-4 mt-4">
  <div class="bg-white rounded-2xl border p-5">
    <div class="flex items-center justify-between mb-3"><h2 class="font-semibold">⚠️ Low stock</h2><a href="{{ route('inventory.index',['low_stock'=>1]) }}" class="text-xs text-indigo-600">View all</a></div>
    <div class="overflow-x-auto"><table class="w-full text-sm">
      <thead class="text-left text-slate-500"><tr><th class="py-2">Product</th><th>Warehouse</th><th class="text-right">Qty</th></tr></thead>
      <tbody>@forelse($lowStock as $inv)<tr class="border-t"><td>{{ $inv->product->name }}<div class="text-xs text-slate-400">{{ $inv->product->sku }}</div></td><td>{{ $inv->warehouse->code }}</td><td class="text-right font-bold {{ $inv->quantity<=0?'text-red-600':'text-amber-600' }}">{{ $inv->quantity }} / {{ $inv->low_stock_threshold }}</td></tr>@empty<tr><td colspan="3" class="py-4 text-slate-400">All stocked ✓</td></tr>@endforelse</tbody>
    </table></div>
  </div>
  <div class="bg-white rounded-2xl border p-5">
    <div class="flex items-center justify-between mb-3"><h2 class="font-semibold">Recent orders</h2><a href="{{ route('orders.index') }}" class="text-xs text-indigo-600">View all</a></div>
    <div class="overflow-x-auto"><table class="w-full text-sm">
      <thead class="text-left text-slate-500"><tr><th class="py-2">Order</th><th>Customer</th><th class="text-right">Total</th></tr></thead>
      <tbody>@foreach($recentOrders as $o)<tr class="border-t"><td><a class="text-indigo-600 hover:underline" href="{{ route('orders.show',$o) }}">{{ $o->order_number }}</a><div class="text-xs text-slate-400">{{ $o->created_at->diffForHumans() }} · {{ $o->status instanceof \BackedEnum ? $o->status->value : $o->status }}</div></td><td>{{ $o->customer->name }}</td><td class="text-right font-semibold">€ {{ number_format($o->total,2) }}</td></tr>@endforeach</tbody>
    </table></div>
  </div>
</div>
@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('revChart'), {type:'bar',
 data:{labels:@json($revenue['labels']), datasets:[{data:@json($revenue['data']), backgroundColor:'#4f46e5', borderRadius:6}]},
 options:{plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}}}});
</script>
@endpush
