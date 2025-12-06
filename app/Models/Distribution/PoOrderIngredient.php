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

class PoOrderIngredient extends Model
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
        'order_id',
        'nomor_po',
        'status',
        'available_at',
        'created_by',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($user) {
            $auth = Auth::user();
            $user->nomor_po = date('YmdHis') .  $auth->branch_id;
            $user->branch_id = $auth->branch_id;
            $user->created_by = $auth->id;
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
        'created_at_indo',
        'available_at_indo',
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
     * Scope a query to only include popular users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvailable($query)
    {
        return $query->where('available_at', '<=', date('Y-m-d'));
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

            case 'print-po':
                return "PO Dicetak";
                break;

            default:
                return "Baru";
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
     * Get the available date indo attribute.
     *
     * @return integer
     */
    public function getAvailableAtIndoAttribute()
    {
        if ($this->available_at) {
            return tanggal_indo($this->available_at, false);
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
        return $this->hasMany(PoOrderIngredientDetail::class);
    }

    /**
     * Get the status_logs for the po order product.
     */
    public function statusLogs()
    {
        return $this->hasMany(PoOrderIngredientStatusLog::class);
    }

    /**
     * Get the packagings for the po order product.
     */
    public function packagings()
    {
        return $this->hasMany(PoOrderIngredientPackaging::class);
    }

     /**
     * Get the shipping for the po order product.
     */
    public function shipping()
    {
        return $this->belongsTo(Shipping::class);
    }

    /**
     * Get the notes for the po order product.
     */
    public function notes()
    {
        return $this->hasMany(PoOrderIngredientNote::class);
    }
}
