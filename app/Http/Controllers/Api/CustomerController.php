<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Customer::class);

        $customers = Customer::withCount('orders')
            ->search($request->string('search')->toString() ?: null)
            ->orderBy($request->get('sort', 'created_at'), $request->get('dir', 'desc') === 'asc' ? 'asc' : 'desc')
            ->paginate($request->integer('per_page', 15));

        return CustomerResource::collection($customers);
    }

    public function show(Customer $customer)
    {
        $this->authorize('view', $customer);

        return new CustomerResource($customer->loadCount('orders'));
    }

    public function store(\App\Http\Requests\StoreCustomerRequest $request)
    {
        $customer = Customer::create($request->validated());

        return (new CustomerResource($customer))->response()->setStatusCode(201);
    }

    public function update(\App\Http\Requests\UpdateCustomerRequest $request, Customer $customer)
    {
        $customer->update($request->validated());

        return new CustomerResource($customer);
    }

    public function destroy(Customer $customer)
    {
        $this->authorize('delete', $customer);

        if ($customer->orders()->exists()) {
            return response()->json(['message' => 'Cannot delete customer with orders.'], 422);
        }
        $customer->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
