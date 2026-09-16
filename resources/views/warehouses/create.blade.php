@extends('layouts.app')
@section('title','New warehouse')
@section('content')
<h1 class="text-xl font-bold mb-4">New warehouse</h1>
<form method="POST" action="{{ route('warehouses.store') }}" class="bg-white border rounded-2xl p-6 max-w-lg space-y-4">@csrf
<div><label class="text-sm font-medium">Name *</label><input name="name" required class="mt-1 w-full border rounded-xl px-3 py-2"></div>
<div><label class="text-sm font-medium">Code *</label><input name="code" required placeholder="WH-MAIN" class="mt-1 w-full border rounded-xl px-3 py-2"></div>
<div><label class="text-sm font-medium">Address</label><input name="address" class="mt-1 w-full border rounded-xl px-3 py-2"></div>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1"> Default warehouse</label>
<button class="bg-indigo-600 text-white px-5 py-2 rounded-xl text-sm">Save</button></form>
@endsection
