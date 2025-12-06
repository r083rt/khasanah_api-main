<?php

namespace App\Models\Distribution;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Illuminate\Support\Facades\Auth;

class PoManualStatusLog extends Model
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
        'po_manual_id',
        'status',
        'note',
        'created_by'
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($user) {
            $user->created_by = Auth::id();
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
        'status_indo'
    ];

    /**
     * Get the status_payment_indo.
     *
     * @param  string  $value
     * @return void
     */
    public function getStatusIndoAttribute()
    {
        switch ($this->status) {
            case 'processed':
                return "Telah disiapkan";
                break;

            case 'product_accepted':
                return "Siap dikirim";
                break;

            case 'product_rejected':
                return "Tidak dikirim";
                break;

            case 'shipment_accepted':
                return "Telah dicek";
                break;

            case 'product_incomplete':
                return "Total Produk Disesuaikan";
                break;

            case 'print':
                return "Telah cetak surat jalan";
                break;

            case 'rejected':
                return "Ditolak Selisih SJ";
                break;

            case 'done':
                return "Selesai";
                break;

            case 'pending':
                return "Pending";
                break;

            case 'po-accepted':
                return "PO Diterima";
                break;

            case 'po-rejected':
                return "Po Ditolak";
                break;

            case 'print-po':
                return "PO Dicetak";
                break;

            default:
                return "Baru";
                break;
        }
    }

    /**
     * Get the created_by for the po order product.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
