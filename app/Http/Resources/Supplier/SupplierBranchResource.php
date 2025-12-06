<?php

namespace App\Http\Resources\Supplier;

use Illuminate\Http\Resources\Json\JsonResource;

class SupplierBranchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'branch_id' => $this->branch_id,
            'name' => $this->branch ? $this->branch->name : null,
            'products' => SupplierBranchProductResource::collection($this->products)
        ];
    }
}
