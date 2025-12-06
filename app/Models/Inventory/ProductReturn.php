<?php

namespace App\Models\Inventory;

use App\Jobs\MonitoringClosingSummary\ReturnProduct;
use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Illuminate\Support\Facades\Auth;

class ProductReturn extends Model
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
        'price',
        'total_price',
        'hpp',
        'total_hpp',
        'type',
        'note',
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
            $data->branch_id = Auth::user()->branch_id;
            $data->created_by = Auth::id();

            $product = Product::find($data->product_id);
            if ($product) {
                $data->price = $product->price;
                $data->total_price = $product->price * $data->qty;

                $data->hpp = $product->price_sale;
                $data->total_hpp = $product->price_sale * $data->qty;
            }
        });

        static::created(function ($data) {
            dispatch(new ReturnProduct([
                'product_id' => $data->product_id,
                'branch_id' => $data->branch_id,
                'qty' => $data->qty,
            ]));
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
        'created_at_indo',
        'type_indo',
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
     * Get the type indo attribute.
     *
     * @return integer
     */
    public function getTypeIndoAttribute()
    {
        switch ($this->type) {
            case 'return':
                return "Retur";
                break;

            default:
                return "Sumbangan";
                break;
        }
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
