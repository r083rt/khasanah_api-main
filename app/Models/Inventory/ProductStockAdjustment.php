<?php

namespace App\Models\Inventory;

use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Illuminate\Support\Facades\Auth;

class ProductStockAdjustment extends Model
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
        'product_id',
        'branch_id',
        'qty',
        'old_stock',
        'note',
        'category',
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
            $user->branch_id = Auth::user()->branch_id;
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
        'created_at_indo'
    ];

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
     * Get the create at indo attribute.
     *
     * @return integer
     */
    public function getCreatedAtIndoAttribute()
    {
        if ($this->created_at) {
            return tanggal_indo($this->created_at->format('Y-m-d'), true);
        }

        return null;
    }

    /**
     * Get the created_by for the order.
     */
    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the product for the user.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the branch for the user.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
