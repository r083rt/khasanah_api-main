<?php

namespace App\Models\Purchasing;

use App\Models\ProductIngredient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class ForecastConversionApprovalDetailBranch extends Model
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
        'forecast_conversion_approval_id',
        'branch_id',
        'product_ingredient_id',
        'qty_forecast',
        'qty_so',
        'qty_total'
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
     * Get the productIngredient for the forecast conversion detail.
     */
    public function productIngredient()
    {
        return $this->belongsTo(ProductIngredient::class);
    }
}
