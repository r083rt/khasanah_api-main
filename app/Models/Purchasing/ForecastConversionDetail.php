<?php

namespace App\Models\Purchasing;

use App\Models\Inventory\ProductRecipeUnit;
use App\Models\ProductIngredient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class ForecastConversionDetail extends Model
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
        'forecast_conversion_id',
        'product_ingredient_id',
        'conversion',
        'buffer',
        'conversion_total',
        'conversion_2',
        'conversion_unit_id',
        'conversion_rounding',
        'conversion_rounding_unit_id',
        'conversion_latest',
        'conversion_latest_rounding',
        'conversion_latest_rounding_total',
        'conversion_latest_rounding_unit_id',
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
     * Get the conversionUnit for the forecast conversion detail.
     */
    public function conversionUnit()
    {
        return $this->belongsTo(ProductRecipeUnit::class, 'conversion_unit_id');
    }

    /**
     * Get the conversionRoundingUnit for the forecast conversion detail.
     */
    public function conversionRoundingUnit()
    {
        return $this->belongsTo(ProductRecipeUnit::class, 'conversion_rounding_unit_id');
    }

    /**
     * Get the conversionRoundingUnit for the forecast conversion detail.
     */
    public function conversionLatestRoundingUnit()
    {
        return $this->belongsTo(ProductRecipeUnit::class, 'conversion_latest_rounding_unit_id');
    }

    /**
     * Get the productIngredient for the forecast conversion detail.
     */
    public function productIngredient()
    {
        return $this->belongsTo(ProductIngredient::class);
    }
}
