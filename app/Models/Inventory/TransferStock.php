<?php

namespace App\Models\Inventory;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Illuminate\Support\Facades\Auth;

class TransferStock extends Model
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
        'branch_id',
        'branch_sender_id',
        'branch_receiver_id',
        'delivery_number',
        'sender',
        'date',
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
        static::creating(function ($user) {
            $user->delivery_number = date('YmdHis');
            $user->created_by = Auth::id();
            $user->branch_id = Auth::user()->branch_id;
        });
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'date'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'date_indo',
        'is_editable',
    ];

    /**
     * Scope a query to only include popular users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getIsEditableAttribute()
    {
        $branchId = Auth::user()->branch_id;
        if ($branchId == $this->branch_receiver_id) {
            return false;
        }

        return true;
    }

    /**
     * Scope a query to only include popular users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBranch($query, $all = true)
    {
        $branchId = Auth::user()->branch_id;
        if ($all) {
            if ($branchId != 1) {
                return $query->where('branch_id', $branchId);
            }
        } else {
            return $query->where('branch_id', $branchId);
        }
    }

     /**
     * Get the date indo.
     *
     * @param  string  $value
     * @return void
     */
    public function getDateIndoAttribute()
    {
        if ($this->date) {
            return tanggal_indo($this->date->format('Y-m-d'), true);
        }

        return null;
    }

    /**
     * Get the branch for the order.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the branch_sender for the order.
     */
    public function branch_sender()
    {
        return $this->belongsTo(Branch::class, 'branch_sender_id');
    }

    /**
     * Get the branch_receiver for the order.
     */
    public function branch_receiver()
    {
        return $this->belongsTo(Branch::class, 'branch_receiver_id');
    }

    /**
     * Get the created_by for the order.
     */
    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the products for the order.
     */
    public function products()
    {
        return $this->hasMany(TransferStockProduct::class, 'transfer_stock_id');
    }
}
