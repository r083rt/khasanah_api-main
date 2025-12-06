<?php

namespace App\Http\Resources\Supplier;

use App\Models\Product;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierBranchProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $productId = $this->pivot ? $this->pivot->product_id : null;
        $product = Product::find($productId);

        return [
            'product_id' => $productId,
            'product_name' => $product ? $product->name : null
        ];
    }
}
