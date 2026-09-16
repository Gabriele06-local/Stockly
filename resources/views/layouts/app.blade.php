<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Stockly') — Stockly</title>
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = { theme: { extend: { colors: { brand: {50:'#eef2ff',600:'#4f46e5',700:'#4338ca'} } } } }
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>body{font-family:'Inter',system-ui,sans-serif}</style>
@stack('head')
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen">
<div class="flex min-h-screen">
  <!-- Sidebar -->
  <aside class="hidden md:flex w-64 flex-col bg-slate-900 text-slate-200 shrink-0">
    <div class="px-6 py-5 flex items-center gap-2">
      <div class="w-9 h-9 rounded-xl bg-indigo-500 flex items-center justify-center font-bold text-white text-lg">S</div>
      <div>
        <div class="font-bold text-white leading-tight">Stockly</div>
        <div class="text-xs text-slate-400">Retail OS</div>
      </div>
    </div>
    <nav class="flex-1 px-3 space-y-1 text-sm">
      @php $r = request()->route()->getName() ?? ''; @endphp
      @php $link = fn($active) => $active ? 'flex items-center gap-3 px-3 py-2 rounded-lg bg-indigo-600 text-white' : 'flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 text-slate-300'; @endphp
      <a href="{{ route('dashboard') }}" class="{{ $link(str_starts_with($r,'dashboard')) }}">📊 Dashboard</a>
      <a href="{{ route('orders.index') }}" class="{{ $link(str_starts_with($r,'orders')) }}">🧾 Orders</a>
      <a href="{{ route('products.index') }}" class="{{ $link(str_starts_with($r,'products')) }}">📦 Products</a>
      <a href="{{ route('categories.index') }}" class="{{ $link(str_starts_with($r,'categories')) }}">🏷️ Categories</a>
      <a href="{{ route('customers.index') }}" class="{{ $link(str_starts_with($r,'customers')) }}">👥 Customers</a>
      <a href="{{ route('warehouses.index') }}" class="{{ $link(str_starts_with($r,'warehouses')) }}">🏬 Warehouses</a>
      <a href="{{ route('inventory.index') }}" class="{{ $link(str_starts_with($r,'inventory')) }}">📋 Inventory</a>
      <a href="{{ route('inventory.movements') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800 text-slate-300">🔁 Movements</a>
    </nav>
    <div class="p-4 border-t border-slate-800">
      <div class="text-sm font-medium text-white">{{ auth()->user()->name }}</div>
      <div class="text-xs text-slate-400">{{ auth()->user()->email }} · {{ auth()->user()->role instanceof \BackedEnum ? auth()->user()->role->value : auth()->user()->role }}</div>
      <form method="POST" action="{{ route('logout') }}" class="mt-3">@csrf
        <button class="w-full text-sm bg-slate-800 hover:bg-slate-700 rounded-lg px-3 py-2">Log out</button>
      </form>
    </div>
  </aside>

  <!-- Main -->
  <div class="flex-1 flex flex-col min-w-0">
    <header class="md:hidden bg-slate-900 text-white px-4 py-3 flex items-center justify-between">
      <span class="font-bold">Stockly</span>
      <span class="text-xs">{{ auth()->user()->name }}</span>
    </header>
    <nav class="md:hidden bg-slate-900 text-slate-200 px-4 pb-3 flex gap-2 overflow-x-auto text-sm">
      <a href="{{ route('dashboard') }}" class="px-3 py-1.5 bg-slate-800 rounded">Dashboard</a>
      <a href="{{ route('orders.index') }}" class="px-3 py-1.5 bg-slate-800 rounded">Orders</a>
      <a href="{{ route('products.index') }}" class="px-3 py-1.5 bg-slate-800 rounded">Products</a>
      <a href="{{ route('customers.index') }}" class="px-3 py-1.5 bg-slate-800 rounded">Customers</a>
      <a href="{{ route('inventory.index') }}" class="px-3 py-1.5 bg-slate-800 rounded">Inventory</a>
    </nav>

    <main class="flex-1 p-4 md:p-8 max-w-7xl w-full mx-auto">
      @if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl">{{ session('success') }}</div>@endif
      @if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl">{{ session('error') }}</div>@endif
      @if($errors->any())<div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl"><ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

      @yield('content')
    </main>
  </div>
</div>
@stack('scripts')
</body>
</html>
