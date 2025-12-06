<?php

namespace App\Models;

use App\Jobs\MonitoringClosingSummary\Sale;
use App\Jobs\Reporting\ReportTransactionUpdate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderProduct extends Model
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
        'order_id',
        'product_id',
        'product_name',
        'product_code',
        'product_price',
        'discount',
        'qty',
        'total_price',
        'product_category_id'
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($data) {
            $data->created_by = Auth::id();
            $product = Product::select('product_category_id')->find($data->product_id);
            $data->product_category_id = $product ? $product->product_category_id : null;
        });

        static::created(function ($data) {
            $order = Order::select('type', 'branch_id')->find($data['order_id']);
            if ($order) {
                if ($order->type == 'cashier') {
                    dispatch(new Sale([
                        'product_id' => $data->product_id,
                        'branch_id' => $order->branch_id,
                        'qty' => $data->qty,
                    ]));

                    try {
                        $qty = $data->qty;
                        $total_price = $data->total_price;
                        $date = $data->created_at->format('Y-m-d');
                        $time = $data->created_at->format('H:i:s');
                        $product_category_id = $data->product_category_id;
                        $branch_id = $order->branch_id;
                        dispatch(new ReportTransactionUpdate([
                            'qty' => $qty,
                            'total_price' => $total_price,
                            'date' => $date,
                            'time' => $time,
                            'product_category_id' => $product_category_id,
                            'branch_id' => $branch_id,
                        ]))->onQueue('report_transaction');
                    } catch (\Throwable $th) {
                        //throw $th;
                        Log::error('Order Product Report Transaction: ' + $th->getMessage());
                    }
                }
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
        'total_price_per_item',
        'sub_total_price'
    ];

    /**
     * Get the total_price_per_item attribute.
     *
     * @return integer
     */
    public function getTotalPricePerItemAttribute()
    {
        return $this->product_price;
    }

    /**
     * Get the sub_total_price attribute.
     *
     * @return integer
     */
    public function getSubTotalPriceAttribute()
    {
        return $this->total_price + $this->discount;
    }

    /**
     * Get the products for the user.
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'id', 'product_id');
    }

    /**
     * Get the orders for the user.
     */
    public function orders()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
