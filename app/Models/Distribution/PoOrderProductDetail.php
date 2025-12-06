<?php

namespace App\Models\Distribution;

use App\Models\Distribution\PoOrderProduct;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class PoOrderProductDetail extends Model
{
    use HasFactory;
    use ColumnFilterer;
    use ColumnSorter;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'po_order_product_id',
        'product_id',
        'qty'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        //
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        //
    ];

   /**
     * Get the product for the po order product detail.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the po_order_product_id for the po order product detail.
     */
    public function poOrderProduct()
    {
        return $this->belongsTo(PoOrderProduct::class, 'po_order_product_id');
    }
}
