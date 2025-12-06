<?php

namespace App\Models\Production;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class BrowniesTargetPlanWarehouse extends Model
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
        'date',
        'po_order_product_id',
        'product_id',
        'branch_id',
        'product_code',
        'product_name',
        'nomor_po',
        'barcode_po',
        'barcode',
        'estimation_product',
        'minimum_stock',
        'order',
        'po',
        'percentage',
        'total'
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        //
    }

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
     * Get the product for the target plan production.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
