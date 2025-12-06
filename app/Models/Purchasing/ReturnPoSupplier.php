<?php

namespace App\Models\Purchasing;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Carbon\Carbon;

class ReturnPoSupplier extends Model
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
        'po_supplier_id',
        'rt_number',
        'returned_at',
        'returned_by'
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        
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
     * Get the poSupplier for the receive po supplier.
     */
    public function poSupplier()
    {
        return $this->belongsTo(PoSupplier::class);
    }

    /**
     * Get the poSupplierDetails for the po supplier.
     */
    public function returnPoSupplierDetails()
    {
        return $this->hasMany(ReturnPoSupplierDetail::class, 'return_id');
    }

    /**
     * Get the received by for the po supplier detail.
     */
    public function returnedBy()
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

}
