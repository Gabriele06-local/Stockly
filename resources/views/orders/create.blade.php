@extends('layouts.app')
@section('title','New order')
@section('content')
<h1 class="text-xl font-bold mb-4">New order</h1>
<form method="POST" action="{{ route('orders.store') }}" id="orderForm" class="grid lg:grid-cols-3 gap-4">@csrf
<div class="lg:col-span-2 bg-white border rounded-2xl p-6 space-y-4">
  <div class="grid md:grid-cols-2 gap-4">
    <div><label class="text-sm font-medium">Customer *</label><select name="customer_id" required class="mt-1 w-full border rounded-xl px-3 py-2"><option value="">Select…</option>@foreach($customers as $c)<option value="{{ $c->id }}">{{ $c->name }}@if($c->company) ({{ $c->company }})@endif</option>@endforeach</select></div>
    <div><label class="text-sm font-medium">Warehouse *</label><select name="warehouse_id" required class="mt-1 w-full border rounded-xl px-3 py-2">@foreach($warehouses as $w)<option value="{{ $w->id }}" @selected($defaultWarehouse && $defaultWarehouse->id==$w->id)>{{ $w->name }} ({{ $w->code }})</option>@endforeach</select></div>
  </div>
  <div>
    <label class="text-sm font-medium">Products</label>
    <div id="lines" class="space-y-2 mt-2"></div>
    <button type="button" onclick="addLine()" class="mt-2 text-sm border px-3 py-1.5 rounded-xl">+ Add line</button>
  </div>
  <div><label class="text-sm font-medium">Notes</label><textarea name="notes" rows="2" class="mt-1 w-full border rounded-xl px-3 py-2"></textarea></div>
</div>
<div class="bg-white border rounded-2xl p-6 space-y-4 h-fit">
  <div class="grid grid-cols-2 gap-3">
    <div><label class="text-sm">Tax rate</label><input name="tax_rate" type="number" step="0.0001" value="0.22" class="mt-1 w-full border rounded-xl px-3 py-2"></div>
    <div><label class="text-sm">Discount €</label><input name="discount_amount" type="number" step="0.01" value="0" class="mt-1 w-full border rounded-xl px-3 py-2"></div>
  </div>
  <div><label class="text-sm">Status</label><select name="status" class="mt-1 w-full border rounded-xl px-3 py-2"><option value="confirmed">confirmed</option><option value="draft">draft</option><option value="paid">paid</option></select></div>
  <div class="bg-slate-50 rounded-xl p-3 text-sm">Subtotal: <b id="subPreview">€ 0.00</b><div class="text-xs text-slate-500">Stock is checked & decremented in a DB transaction. Draft orders don't consume stock.</div></div>
  <button class="w-full bg-indigo-600 text-white py-2.5 rounded-xl text-sm font-medium">Create order</button>
</div>
</form>
@endsection
@push('scripts')
<script>
const PRODUCTS = @json($products->map(fn($p)=>['id'=>$p->id,'name'=>$p->name,'price'=>(float)$p->price,'stock'=>$p->inventories->sum('quantity')]));
function addLine(){
  const div = document.createElement('div');
  div.className = 'flex gap-2 items-center';
  div.innerHTML = `<select name="items[][product_id]" required class="flex-1 border rounded-xl px-2 py-2 text-sm" onchange="upd()">${PRODUCTS.map(p=>`<option value="${p.id}">${p.name} — €${p.price} (stock ${p.stock})</option>`).join('')}</select>
  <input name="items[][quantity]" type="number" min="1" value="1" class="w-20 border rounded-xl px-2 py-2 text-sm" oninput="upd()">
  <input name="items[][unit_price]" type="number" step="0.01" placeholder="price" class="w-24 border rounded-xl px-2 py-2 text-sm" oninput="upd()">
  <button type="button" onclick="this.parentElement.remove();upd()" class="text-red-600">✕</button>`;
  document.getElementById('lines').appendChild(div); upd();
}
function upd(){
  let sub = 0;
  document.querySelectorAll('#lines > div').forEach(row=>{
    const pid = row.querySelector('select').value;
    const q = parseFloat(row.querySelectorAll('input')[0].value||0);
    let pr = parseFloat(row.querySelectorAll('input')[1].value||'');
    if(isNaN(pr)){ const p = PRODUCTS.find(x=>x.id==pid); pr = p?p.price:0; row.querySelectorAll('input')[1].placeholder = pr; }
    sub += q*pr;
  });
  document.getElementById('subPreview').textContent = '€ ' + sub.toFixed(2);
}
addLine();
</script>
@endpush
