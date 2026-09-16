<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::with(['category', 'inventories'])
            ->search($request->string('search')->toString() ?: null)
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy($request->get('sort', 'created_at'), $request->get('dir', 'desc') === 'asc' ? 'asc' : 'desc')
            ->paginate($request->integer('per_page', 15));

        return ProductResource::collection($products);
    }

    public function show(Product $product)
    {
        $this->authorize('view', $product);
        $product->load(['category', 'inventories.warehouse']);

        return new ProductResource($product);
    }

    public function store(\App\Http\Requests\StoreProductRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? \Illuminate\Support\Str::slug($data['name'].'-'.\Illuminate\Support\Str::random(5));
        $product = Product::create($data);
        $product->load(['category', 'inventories']);

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    public function update(\App\Http\Requests\UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return new ProductResource($product->fresh(['category', 'inventories']));
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);
        $product->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
