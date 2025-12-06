<?php

namespace App\Models\Purchasing;

use App\Models\ProductIngredient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class ForecastConversionDetailLog extends Model
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
        'product_id',
        'product_ingredient_id',
        'master_packaging_id',
        'qty',
        'measure',
        'qty_measure',
        'gramasi_production',
        'qty_packaging',
        'measure_packaging',
        'conversion',
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
