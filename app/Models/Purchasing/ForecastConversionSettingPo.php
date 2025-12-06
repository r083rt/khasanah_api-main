<?php

namespace App\Models\Purchasing;

use App\Models\Inventory\Brand;
use App\Models\Inventory\ProductRecipeUnit;
use App\Models\ProductIngredient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Illuminate\Support\Facades\Auth;

class ForecastConversionSettingPo extends Model
{
    use HasFactory;
    use ColumnFilterer;
    use ColumnSorter;

    protected $table = 'forecast_conversion_setting_po';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'parent_id',
        'forecast_conversion_approval_id',
        'product_ingredient_id',
        'qty_total',
        'product_recipe_unit_id',
        'created_by',
        'brand_id',
        'barcode',
        'qty_real',
        'qty_remaining',
        'qty_used',
        'qty_so',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($data) {
            $data->created_by = Auth::id();
        });
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
     * Get the productIngredient for the forecast conversion setting po.
     */
    public function productIngredient()
    {
        return $this->belongsTo(ProductIngredient::class);
    }

    /**
     * Get the forecastConversionApproval for the forecast conversion setting po.
     */
    public function forecastConversionApproval()
    {
        return $this->belongsTo(ForecastConversionApproval::class);
    }

    /**
     * Get the purchasingSupplier for the forecast conversion setting po.
     */
    public function purchasingSupplier()
    {
        return $this->belongsTo(PurchasingSupplier::class);
    }

    /**
     * Get the supplier for the forecast conversion setting po.
     */
    public function supplier()
    {
        return $this->belongsTo(PurchasingSupplier::class, 'purchasing_supplier_id');
    }

    /**
     * Get the productRecipeUnit for the forecast conversion setting po.
     */
    public function productRecipeUnit()
    {
        return $this->belongsTo(ProductRecipeUnit::class);
    }

    /**
     * Get the createdBy for the forecast conversion setting po.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the brand for the forecast conversion setting po.
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the forecastConversionSettingPoDeliveries for the forecast conversion setting po.
     */
    public function forecastConversionSettingPoDeliveries()
    {
        return $this->belongsTo(ForecastConversionSettingPoDelivery::class);
    }

    /**
     * Get the forecastConversionSettingPoSuppliers for the forecast conversion setting po.
     */
    public function forecastConversionSettingPoSuppliers()
    {
        return $this->hasMany(ForecastConversionSettingPoSupplier::class);
    }

    /**
     * Get the forecastConversionSettingPoDeliveries for the forecast conversion setting po.
     */
    public function forecastConversionSettingPoQtyDeliveries()
    {
        return $this->belongsTo(ForecastConversionSettingPoQtyDelivery::class);
    }

    /**
     * Get the forecastConversionSettingPoSuppliers for the forecast conversion setting po.
     */
    public function forecastConversionSettingPoQtySuppliers()
    {
        return $this->hasMany(ForecastConversionSettingPoQtySupplier::class);
    }
}
