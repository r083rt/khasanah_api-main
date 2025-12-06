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

class ProductStockLogTemp extends Model
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
        'date',
        'product_id',
        'branch_id',
        'stock',
        'from',
        'table_reference',
        'table_id',
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
            $id = Auth::id();
            if ($id) {
                $user->created_by = $id;
            }
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
    public function createdBy()
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
