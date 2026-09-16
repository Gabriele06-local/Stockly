@extends('layouts.app')
@section('title','Warehouses')
@section('content')
<div class="flex items-center justify-between mb-4"><h1 class="text-xl font-bold">Warehouses</h1>@can('create', App\Models\Warehouse::class)<a href="{{ route('warehouses.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm">+ Warehouse</a>@endcan</div>
<div class="grid md:grid-cols-3 gap-4">
@foreach($warehouses as $w)
<div class="bg-white border rounded-2xl p-5">
<div class="flex items-center justify-between"><span class="font-bold">{{ $w->name }}</span>@if($w->is_default)<span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-1 rounded-full">Default</span>@endif</div>
<div class="text-xs text-slate-400">{{ $w->code }} · {{ $w->address }}</div>
<div class="text-2xl font-bold mt-2">{{ $w->inventories_sum_quantity ?? 0 }} <span class="text-sm font-normal text-slate-400">units</span></div>
<div class="flex gap-2 mt-3">@can('update', $w)<a href="{{ route('warehouses.edit',$w) }}" class="text-xs border px-3 py-1.5 rounded-lg">Edit</a>@endcan
@can('delete', $w)<form method="POST" action="{{ route('warehouses.destroy',$w) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-xs border px-3 py-1.5 rounded-lg text-red-600">Delete</button></form>@endcan</div>
</div>
@endforeach
</div>
<div class="mt-4">{{ $warehouses->links() }}</div>
@endsection
