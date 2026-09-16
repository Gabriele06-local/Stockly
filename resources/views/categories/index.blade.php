@extends('layouts.app')
@section('title','Categories')
@section('content')
<div class="flex items-center justify-between mb-4"><h1 class="text-xl font-bold">Categories</h1>@can('create', App\Models\Category::class)<a href="{{ route('categories.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm">+ Category</a>@endcan</div>
<div class="bg-white border rounded-2xl overflow-hidden"><table class="w-full text-sm">
<thead class="bg-slate-50 text-left text-slate-500"><tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Products</th><th class="px-4 py-3"></th></tr></thead>
<tbody>@foreach($categories as $c)<tr class="border-t"><td class="px-4 py-3 font-medium">{{ $c->name }}<div class="text-xs text-slate-400">{{ $c->slug }}</div></td><td class="px-4 py-3">{{ $c->products_count }}</td>
<td class="px-4 py-3 text-right flex gap-2 justify-end">@can('update', $c)<a href="{{ route('categories.edit',$c) }}" class="text-indigo-600 text-xs">Edit</a>@endcan
@can('delete', $c)<form method="POST" action="{{ route('categories.destroy',$c) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-600 text-xs">Delete</button></form>@endcan</td></tr>@endforeach</tbody></table>
<div class="p-3">{{ $categories->links() }}</div></div>
@endsection
