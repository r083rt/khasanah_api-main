<?php

namespace App\Models\Inventory;

use App\Jobs\MonitoringClosingSummary\InStock;
use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Illuminate\Support\Facades\Auth;

class ProductStockLog extends Model
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
        'stock',
        'stock_old',
        'stock_after',
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
        static::creating(function ($data) {
            if (Auth::id()) {
                $data->created_by = Auth::id();
            }

            $data->stock_after = $data->stock + $data->stock_old;
        });

        static::created(function ($data) {
            $from = [
                'Po Produksi Roti Manis',
                'Transfer Stok',
                'Penyesuain Stok',
                'Po Manual',
                'Po Brownis',
                'Po Brownis Toko'
            ];

            if (in_array($data->from, $from) && $data->stock != 0) {
                dispatch(new InStock([
                    'branch_id' => $data->branch_id,
                    'product_id' => $data->product_id,
                    'stock' => $data->stock,
                ]));
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
        'created_at_indo',
        'stock_after',
    ];

    /**
     * Get the stock after attribute.
     *
     * @return integer
     */
    public function getStockAfterAttribute()
    {
        return $this->stock + $this->stock_old;
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
