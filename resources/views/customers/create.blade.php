@extends('layouts.app')
@section('title','New customer')
@section('content')
<h1 class="text-xl font-bold mb-4">New customer</h1>
<form method="POST" action="{{ route('customers.store') }}" class="bg-white border rounded-2xl p-6 max-w-2xl grid md:grid-cols-2 gap-4">@csrf
<div><label class="text-sm font-medium">Name *</label><input name="name" required class="mt-1 w-full border rounded-xl px-3 py-2"></div>
<div><label class="text-sm font-medium">Company</label><input name="company" class="mt-1 w-full border rounded-xl px-3 py-2"></div>
<div><label class="text-sm font-medium">Email</label><input name="email" type="email" class="mt-1 w-full border rounded-xl px-3 py-2"></div>
<div><label class="text-sm font-medium">Phone</label><input name="phone" class="mt-1 w-full border rounded-xl px-3 py-2"></div>
<div><label class="text-sm font-medium">Address</label><input name="address" class="mt-1 w-full border rounded-xl px-3 py-2"></div>
<div><label class="text-sm font-medium">City</label><input name="city" class="mt-1 w-full border rounded-xl px-3 py-2"></div>
<div class="md:col-span-2"><label class="text-sm font-medium">Notes</label><textarea name="notes" rows="2" class="mt-1 w-full border rounded-xl px-3 py-2"></textarea></div>
<div class="md:col-span-2"><button class="bg-indigo-600 text-white px-5 py-2 rounded-xl text-sm">Save</button></div></form>
@endsection
