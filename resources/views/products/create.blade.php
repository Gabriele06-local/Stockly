@extends('layouts.app')
@section('title','New product')
@section('content')
<h1 class="text-xl font-bold mb-4">New product</h1>
<form method="POST" action="{{ route('products.store') }}" class="bg-white border rounded-2xl p-6 max-w-2xl space-y-4">@csrf
  <div class="grid md:grid-cols-2 gap-4">
    <div><label class="text-sm font-medium">Name *</label><input name="name" required value="{{ old('name') }}" class="mt-1 w-full border rounded-xl px-3 py-2"></div>
    <div><label class="text-sm font-medium">SKU *</label><input name="sku" required value="{{ old('sku') }}" class="mt-1 w-full border rounded-xl px-3 py-2"></div>
  </div>
  <div class="grid md:grid-cols-2 gap-4">
    <div><label class="text-sm font-medium">Category</label><select name="category_id" class="mt-1 w-full border rounded-xl px-3 py-2"><option value="">—</option>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
    <div><label class="text-sm font-medium">Slug (optional)</label><input name="slug" class="mt-1 w-full border rounded-xl px-3 py-2"></div>
  </div>
  <div class="grid md:grid-cols-2 gap-4">
    <div><label class="text-sm font-medium">Price (€) *</label><input name="price" type="number" step="0.01" min="0" required value="{{ old('price') }}" class="mt-1 w-full border rounded-xl px-3 py-2"></div>
    <div><label class="text-sm font-medium">Cost (€)</label><input name="cost" type="number" step="0.01" min="0" value="{{ old('cost') }}" class="mt-1 w-full border rounded-xl px-3 py-2"></div>
  </div>
  <div><label class="text-sm font-medium">Description</label><textarea name="description" rows="3" class="mt-1 w-full border rounded-xl px-3 py-2">{{ old('description') }}</textarea></div>
  <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" checked> Active</label>
  <div class="flex gap-2"><button class="bg-indigo-600 text-white px-5 py-2 rounded-xl text-sm">Save</button><a href="{{ route('products.index') }}" class="px-5 py-2 text-sm border rounded-xl">Cancel</a></div>
</form>
@endsection
