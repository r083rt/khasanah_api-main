<?php

namespace App\Models\Management;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class BranchProduct extends Model
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
        'branch_supplier_id',
        'product_id',
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
     * Get the branch supplier for the branch supplier.
     */
    public function branchSupplier()
    {
        return $this->belongsTo(BranchSupplier::class);
    }

    /**
     * Get the product for the branch supplier.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
