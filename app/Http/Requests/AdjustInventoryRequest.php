<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'quantity' => ['required', 'integer', 'min:-100000', 'max:100000', 'not_in:0'],
            'reason' => ['nullable', 'string', 'max:255'],
            'low_stock_threshold' => ['sometimes', 'integer', 'min:0', 'max:10000'],
        ];
    }
}
