<?php

namespace App\Models\Production;

use App\Models\Branch;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class BrowniesTargetPlanWarehouseAdditionalPo extends Model
{
    use HasFactory;
    use ColumnFilterer;
    use ColumnSorter;

    protected $table = 'brownies_target_plan_warehouse_additional_po';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'date',
        'product_id',
        'branch_id',
        'po'
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

    /**
     * Get the branch for the target plan production.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
