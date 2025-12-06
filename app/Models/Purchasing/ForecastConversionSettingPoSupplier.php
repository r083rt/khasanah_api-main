<?php

namespace App\Models\Purchasing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class ForecastConversionSettingPoSupplier extends Model
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
        'forecast_conversion_setting_po_id',
        'purchasing_supplier_id',
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
     * Get the supplier for the forecast conversion setting po.
     */
    public function purchasingSupplier()
    {
        return $this->belongsTo(PurchasingSupplier::class);
    }

    /**
     * Get the forecastConversionSettingPoSupplierDeliveries for the forecast conversion setting po supplier.
     */
    public function forecastConversionSettingPoSupplierDeliveries()
    {
        return $this->hasMany(ForecastConversionSettingPoSupplierDelivery::class);
    }

    /**
     * Get the forecastConversionSettingPo for the forecast conversion setting po supplier.
     */
    public function forecastConversionSettingPo()
    {
        return $this->belongsTo(ForecastConversionSettingPo::class);
    }
}
