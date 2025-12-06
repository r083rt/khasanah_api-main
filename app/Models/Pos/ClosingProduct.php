<?php

namespace App\Models\Pos;

use App\Jobs\MonitoringClosingSummary\RemainsStock;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Illuminate\Support\Facades\Auth;

class ClosingProduct extends Model
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
        'closing_id',
        'product_id',
        'product_name',
        'product_code',
        'stock_system',
        'stock_real',
        'difference',
        'note'
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::created(function ($data) {
            $closing = Closing::select('branch_id')->find($data['closing_id']);
            if ($closing) {
                dispatch(new RemainsStock([
                    'product_id' => $data->product_id,
                    'branch_id' => $closing->branch_id,
                    'qty' => $data->stock_real,
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
        //
    ];

    /**
     * Get the closing for the closing product.
     */
    public function closing()
    {
        return $this->belongsTo(Closing::class);
    }

    /**
     * Get the product for the closing product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
