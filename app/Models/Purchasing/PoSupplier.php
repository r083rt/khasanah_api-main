<?php

namespace App\Models\Purchasing;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Carbon\Carbon;

class PoSupplier extends Model
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
        'po_number',
        'day',
        'month',
        'year',
        'date',
        'branch_id',
        'purchasing_supplier_id',
        'forecast_conversion_approval_id',
        'status',
        'status_delivery',
        'received_at',
        'receipt_number'
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($data) {
            $data->po_number = date('YmdHis') . rand(1000,9999);
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
        'status_indo',
        'status_delivery_indo',
        'created_at_date',
        'day_deviation',
        'file_path',
    ];

    /**
     * Get the file_path.
     *
     * @return mixed
     */
    public function getFilePathAttribute()
    {
        if ($this->po_number) {
            return url('storages/po_supplier/' . $this->po_number . '.pdf');
        }

        return null;
    }

    /**
     * Get the created_at_date.
     *
     * @param  string  $value
     * @return void
     */
    public function getDayDeviationAttribute()
    {
        if ($this->received_at) {
            $received_at = Carbon::parse($this->received_at);
            $date = Carbon::parse($this->date);

            $a = strtotime($this->received_at);
            $b = strtotime($this->date);
            $deviation = '';
            if ($b > $a) {
                $deviation = '-';
            }

            return $deviation . $received_at->diffInDays($date);
        }

        return null;
    }

    /**
     * Get the created_at_date.
     *
     * @param  string  $value
     * @return void
     */
    public function getCreatedAtDateAttribute()
    {
        if ($this->created_at) {
            return date('Y-m-d', strtotime($this->created_at));
        }

        return null;
    }

    /**
     * Get the status_delivery_indo.
     *
     * @param  string  $value
     * @return void
     */
    public function getStatusDeliveryIndoAttribute()
    {
        switch ($this->status_delivery) {
            case 'new':
                $data = 'Baru';
                break;

            case 'received':
                $data = 'Diterima';
                break;

            case 'partial':
                $data = 'Partial';
                break;

            default:
                $data = null;
                break;
        }

        return $data;
    }

    /**
     * Get the status_indo.
     *
     * @param  string  $value
     * @return void
     */
    public function getStatusIndoAttribute()
    {
        switch ($this->status) {
            case 'new':
                $data = 'Belum dikirim';
                break;

            case 'sent':
                $data = 'Sedang dikirim';
                break;

            case 'success':
                $data = 'Berhasil dikirim';
                break;

            case 'failed':
                $data = 'Gagal';
                break;

            default:
                $data = null;
                break;
        }

        return $data;
    }

    /**
     * Get the branch for the po supplier.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the purchasingSupplier for the po supplier.
     */
    public function purchasingSupplier()
    {
        return $this->belongsTo(PurchasingSupplier::class);
    }

    /**
     * Get the poSupplierDetails for the po supplier.
     */
    public function poSupplierDetails()
    {
        return $this->hasMany(PoSupplierDetail::class);
    }

    /**
     * Get the poSupplierDetails for the po supplier.
     */
    public function receivePoSuppliers()
    {
        return $this->hasMany(ReceivePoSupplier::class);
    }

    /**
     * Get the poSupplierDetails for the po supplier.
     */
    public function returnPoSuppliers()
    {
        return $this->hasMany(ReturnPoSupplier::class);
    }

     /**
     * Get the forecastConversionApproval for the po suppliers.
     */
    public function forecastConversionApproval()
    {
        return $this->belongsTo(ForecastConversionApproval::class);
    }
}
