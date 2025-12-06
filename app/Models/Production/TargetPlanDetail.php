<?php

namespace App\Models\Production;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class TargetPlanDetail extends Model
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
        'target_plan_id',
        'product_id',
        'product_category_id',
        'first_stock',
        'remains',
        'realization',
        'two_oclock',
        'four_oclock',
        'tomorrow_plan',
        'current_stock',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'date'
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
     * Get the target plan for the detail.
     */
    public function target()
    {
        return $this->belongsTo(TargetPlan::class, 'target_plan_id');
    }

    /**
     * Get the product for the detail.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the product category for the detail.
     */
    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    /**
     * Get the grinds for the detail.
     */
    public function grinds()
    {
        return $this->hasMany(TargetPlanDetailGrind::class);
    }
}
