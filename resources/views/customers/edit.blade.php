@extends('layouts.app')
@section('title','Edit customer')
@section('content')
<h1 class="text-xl font-bold mb-4">Edit customer</h1>
<form method="POST" action="{{ route('customers.update',$customer) }}" class="bg-white border rounded-2xl p-6 max-w-2xl grid md:grid-cols-2 gap-4">@csrf @method('PUT')
<div><label class="text-sm font-medium">Name *</label><input name="name" required value="{{ $customer->name }}" class="mt-1 w-full border rounded-xl px-3 py-2"></div>
<div><label class="text-sm font-medium">Company</label><input name="company" value="{{ $customer->company }}" class="mt-1 w-full border rounded-xl px-3 py-2"></div>
<div><label class="text-sm font-medium">Email</label><input name="email" type="email" value="{{ $customer->email }}" class="mt-1 w-full border rounded-xl px-3 py-2"></div>
<div><label class="text-sm font-medium">Phone</label><input name="phone" value="{{ $customer->phone }}" class="mt-1 w-full border rounded-xl px-3 py-2"></div>
<div><label class="text-sm font-medium">Address</label><input name="address" value="{{ $customer->address }}" class="mt-1 w-full border rounded-xl px-3 py-2"></div>
<div><label class="text-sm font-medium">City</label><input name="city" value="{{ $customer->city }}" class="mt-1 w-full border rounded-xl px-3 py-2"></div>
<div class="md:col-span-2"><label class="text-sm font-medium">Notes</label><textarea name="notes" rows="2" class="mt-1 w-full border rounded-xl px-3 py-2">{{ $customer->notes }}</textarea></div>
<div class="md:col-span-2"><button class="bg-indigo-600 text-white px-5 py-2 rounded-xl text-sm">Update</button></div></form>
@endsection
