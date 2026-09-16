@extends('layouts.app')
@section('title','Edit category')
@section('content')
<h1 class="text-xl font-bold mb-4">Edit category</h1>
<form method="POST" action="{{ route('categories.update',$category) }}" class="bg-white border rounded-2xl p-6 max-w-lg space-y-4">@csrf @method('PUT')
<div><label class="text-sm font-medium">Name *</label><input name="name" required value="{{ $category->name }}" class="mt-1 w-full border rounded-xl px-3 py-2"></div>
<div><label class="text-sm font-medium">Description</label><textarea name="description" rows="3" class="mt-1 w-full border rounded-xl px-3 py-2">{{ $category->description }}</textarea></div>
<button class="bg-indigo-600 text-white px-5 py-2 rounded-xl text-sm">Update</button></form>
@endsection
