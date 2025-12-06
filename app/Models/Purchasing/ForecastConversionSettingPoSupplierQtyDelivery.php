<?php

namespace App\Models\Purchasing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use App\Models\Branch;

class ForecastConversionSettingPoSupplierQtyDelivery extends Model
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
        'forecast_conversion_setting_po_supplier_id',
        'period',
        'date',
        'qty_total',
        'day',
        'qty',
        'branch'
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
     * Get the forecastConversionSettingPoSupplier for the forecast conversion setting po supplier delivery.
     */
    public function forecastConversionSettingPoQtySupplier()
    {
        return $this->belongsTo(forecastConversionSettingPoQtySupplier::class, 'forecast_conversion_setting_po_supplier_id', 'id');
    }
}
