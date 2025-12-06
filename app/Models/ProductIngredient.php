<?php

namespace App\Models;

use App\Jobs\Purchasing\RegenerateForecastConversion;
use App\Models\Inventory\Brand;
use App\Models\Inventory\ProductIngredientBrand;
use App\Models\Inventory\ProductIngredientStock;
use App\Models\Inventory\ProductRecipeUnit;
use App\Models\Management\Division;
use App\Models\Purchasing\ForecastBuffer;
use App\Models\Purchasing\PurchasingSupplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class ProductIngredient extends Model
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
        'name',
        'code',
        'barcode',
        'hpp',
        'product_recipe_unit_id',
        'product_ingredient_unit_delivery_id',
        'unit_value',
        'brand_id',
        'price',
        'discount',
        'real_price'
    ];

    protected static function booted()
    {
        // static::updated(function ($data) {
        //     if ($data->wasChanged('product_recipe_unit_id')) {
        //         dispatch(new RegenerateForecastConversion([
        //             'product_ingredient_id' => $data->product_recipe_unit_id
        //         ]));
        //     }
        // });
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
     * Get total unit delivery
     */
    public static function getTotalUnitDelivery($qty, $unitValue)
    {
        if ($unitValue == 0) {
            return $qty;
        }

        return round($qty / $unitValue, 2);
    }

    /**
     * Get total unit
     */
    public static function getTotalUnit($qty, $unitValue)
    {
        if ($unitValue == 0) {
            return $qty;
        }

        return round($unitValue * $qty, 2);
    }

    /**
     * Get the units for the product ingredient.
     */
    public function unit()
    {
        return $this->belongsTo(ProductRecipeUnit::class, 'product_recipe_unit_id');
    }

    /**
     * Get the unitDelivery for the product ingredient.
     */
    public function unitDelivery()
    {
        return $this->belongsTo(ProductRecipeUnit::class, 'product_ingredient_unit_delivery_id');
    }

    /**
     * Get the productIngredientBrands for the product ingredient.
     */
    public function productIngredientBrands()
    {
        return $this->hasMany(ProductIngredientBrand::class);
    }

    /**
     * Get the division for the product ingredient.
     */
    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * Get the suppliers for the product ingredient.
     */
    public function suppliers()
    {
        return $this->belongsToMany(PurchasingSupplier::class, 'product_ingredient_suppliers', 'product_ingredient_id', 'purchasing_supplier_id')->withTimestamps();
    }

    /**
     * Get the brand for the product ingredient.
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the stocks for the product ingredient.
     */
    public function stocks()
    {
        return $this->hasMany(ProductIngredientStock::class);
    }

    /**
     * Get the buffer for the product ingredient.
     */
    public function buffer()
    {
        return $this->hasOne(ForecastBuffer::class);
    }
}
