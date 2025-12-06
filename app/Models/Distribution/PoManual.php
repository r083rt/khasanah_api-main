<?php

namespace App\Models\Distribution;

use App\Models\Branch;
use App\Models\Management\Shipping;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Illuminate\Support\Facades\Auth;

class PoManual extends Model
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
        'shipping_id',
        'branch_id',
        'is_urgent',
        'nomor_po',
        'type',
        'status_shipping',
        'status',
        'created_by',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($data) {
            $branchId = $data->branch_id;
            $auth = Auth::user();
            if (is_null($data->branch_id)) {
                $branchId = $auth->branch_id;
                $data->branch_id = $branchId;
            }
            $data->created_by = $auth->id;
            $data->nomor_po = date('YmdHis') .  $branchId;
        });
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_urgent' => 'boolean'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'status_indo',
        'created_at_indo',
        'status_shipping_indo',
    ];

    /**
     * Scope a query to only include popular users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBranch($query)
    {
        $branchId = Auth::user()->branch_id;
        if ($branchId != 1) {
            return $query->where('branch_id', $branchId)->whereNotNull('branch_id');
        }
    }

    /**
     * Get the status_id.
     *
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
     * Get the status_id.
     *
     * @return void
     */
    public function getStatusShippingIndoAttribute()
    {
        switch ($this->status_shipping) {
            case 'today':
                return "Hari ini";
                break;

            default:
                return "Besok";
                break;
        }
    }

    /**
     * Get the crated date indo attribute.
     *
     * @return integer
     */
    public function getCreatedAtIndoAttribute()
    {
        if ($this->created_at) {
            return tanggal_indo($this->created_at->format('Y-m-d H:i:s'), true);
        }

        return null;
    }

   /**
    * Get the branch_id for the po order ingredient.
    */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the created_by for the po order ingredient.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the details for the po order ingredient.
     */
    public function details()
    {
        return $this->hasMany(PoManualDetail::class);
    }

    /**
     * Get the status_logs for the po order product.
     */
    public function statusLogs()
    {
        return $this->hasMany(PoManualStatusLog::class);
    }

    /**
     * Get the packagings for the po order product.
     */
    public function packagings()
    {
        return $this->hasMany(PoManualPackaging::class);
    }

     /**
     * Get the shipping for the po order product.
     */
    public function shipping()
    {
        return $this->belongsTo(Shipping::class);
    }

    /**
     * Get the notes for the po manual.
     */
    public function notes()
    {
        return $this->hasMany(PoManualNote::class);
    }
}
