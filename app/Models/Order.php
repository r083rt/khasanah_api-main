<?php

namespace App\Models;

use App\Jobs\MonitoringClosingSummary\Order as MonitoringClosingSummaryOrder;
use App\Jobs\MonitoringClosingSummary\Sale;
use App\Jobs\Reporting\ReportTransactionUpdate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Pos\OrderPayment;
use Illuminate\Support\Facades\DB;

class Order extends Model
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
        'uuid',
        'invoice',
        'product_category_id',
        'branch_id',
        'customer_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'payment_id',
        'payment_name',
        'payment_desc',
        'pay',
        'payment_type',
        'total_price',
        'type',
        'note',
        'date_pickup',
        'status_payment',
        'status_pickup',
        'created_by',
        'received_date',
        'received_by',
        'status',
        'refund_dp_date',
        'refund_by',
        'discount_type'
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($data) {
            $data->invoice = date('Ymd') . '-' . date('His') . '-' .  Str::random(6);
            $data->created_by = Auth::id();
        });

        static::created(function ($data) {
            if ($data->type == 'cashier') {
                $qty = 1;
                $date = $data->created_at->format('Y-m-d');
                $time = $data->created_at->format('H:i:s');
                $product_category_id = 'Cust.';
                $branch_id = $data->branch_id;
                dispatch(new ReportTransactionUpdate([
                    'qty' => $qty,
                    'total_price' => null,
                    'date' => $date,
                    'time' => $time,
                    'product_category_id' => $product_category_id,
                    'branch_id' => $branch_id,
                ]))->onQueue('report_transaction');
            }
        });

        static::updated(function ($data) {
            if ($data->type == 'order') {
                if (is_null($data->getOriginal('received_date'))) {
                    if ($data->received_date) {
                        dispatch(new MonitoringClosingSummaryOrder([
                            'order_id' => $data->id,
                        ]));

                        $qty = 1;
                        $date = $data->received_date->format('Y-m-d');
                        $time = $data->received_date->format('H:i:s');
                        $product_category_id = 'Cust.';
                        $branch_id = $data->branch_id;
                        dispatch(new ReportTransactionUpdate([
                            'qty' => $qty,
                            'total_price' => null,
                            'date' => $date,
                            'time' => $time,
                            'product_category_id' => $product_category_id,
                            'branch_id' => $branch_id,
                        ]))->onQueue('report_transaction');

                        $orderProduct = OrderProduct::select('qty', 'total_price', 'product_category_id')->where(['order_id' => $data->id])->get();
                        foreach ($orderProduct as $value) {
                            dispatch(new ReportTransactionUpdate([
                                'qty' => $value->qty,
                                'total_price' =>  $value->total_price,
                                'date' => $date,
                                'time' => $time,
                                'product_category_id' => $value->product_category_id,
                                'branch_id' => $branch_id,
                            ]))->onQueue('report_transaction');
                        }
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
        'created_at' => 'datetime',
        'received_date' => 'datetime',
        'refund_dp_date' => 'datetime'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'status_payment_indo',
        'status_pickup_indo',
        'order_date_indo',
        'received_date_indo',
        'status_indo',
        'refund_dp_date_indo',
    ];

    /**
     * Get the order date indo attribute.
     *
     * @return integer
     */
    public function getRefundDpDateIndoAttribute()
    {
        if ($this->refund_dp_date) {
            return tanggal_indo($this->refund_dp_date->format('Y-m-d H:i:s'), true);
        }

        return null;
    }

    /**
     * Get the order date indo attribute.
     *
     * @return integer
     */
    public function getOrderDateIndoAttribute()
    {
        if ($this->created_at) {
            return tanggal_indo($this->created_at->format('Y-m-d'), true);
        }

        return null;
    }

    /**
     * Get the received date indo attribute.
     *
     * @return integer
     */
    public function getReceivedDateIndoAttribute()
    {
        if ($this->received_date) {
            return tanggal_indo($this->received_date->format('Y-m-d H:i:s'), true);
        }

        return null;
    }

    /**
     * Cek Duplicate Order
     */
    public static function cekDuplicate($uuid)
    {
        $closing = self::where('uuid', $uuid)->count();
        if ($closing > 0) {
            return true;
        }

        return false;
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
     * Scope a query to only include popular users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNow($query)
    {
        return $query->whereDate('created_at', date('Y-m-d'));
    }

    /**
     * Scope a query to only include popular users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNonCash($query)
    {
        return $query->where('payment_id', '!=', 1);
    }

    /**
     * Scope a query to only include popular users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCash($query)
    {
        return $query->where('payment_id', 1);
    }

    /**
     * Scope a query to only include popular users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByMe($query)
    {
        $id = Auth::id();
        return $query->where('created_by', $id);
    }

    /**
     * Scope a query to only include popular users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReceivedByMe($query)
    {
        $id = Auth::id();
        return $query->where('received_by', $id);
    }

    /**
     * Scope a query to only include popular users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrder($query)
    {
        return $query->where('type', 'order');
    }

    /**
     * Scope a query to only include popular users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCashier($query)
    {
        return $query->where('type', 'cashier');
    }

    /**
     * Get the status_payment_indo.
     *
     * @param  string  $value
     * @return void
     */
    public function getStatusPaymentIndoAttribute()
    {
        switch ($this->status_payment) {
            case 'paid':
                return "Lunas";
                break;

            default:
                return "Belum Lunas";
                break;
        }
    }

    /**
     * Get the status_payment_indo.
     *
     * @param  string  $value
     * @return void
     */
    public function getStatusIndoAttribute()
    {
        switch ($this->status) {
            case 'canceled':
                return "Batal";
                break;

            case 'completed':
                return "Selesai";
                break;

            default:
                return "Baru";
                break;
        }
    }

    /**
     * Get the status_pickup_indo.
     *
     * @param  string  $value
     * @return void
     */
    public function getStatusPickupIndoAttribute()
    {
        switch ($this->status_pickup) {
            case 'done':
                return "Sudah diambil";
                break;

            default:
                return "Belum diambil";
                break;
        }
    }

    /**
     * Get the products for the order.
     */
    public function products()
    {
        return $this->hasMany(OrderProduct::class);
    }

     /**
     * Get the customer for the order.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the created_by for the order.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the received_by for the order.
     */
    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Get the refund by for the order.
     */
    public function refundBy()
    {
        return $this->belongsTo(User::class, 'refund_by');
    }

    /**
     * Get the branch for the order.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the category for the order.
     */
    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    /**
     * Get the payments for the order.
     */
    public function payments()
    {
        return $this->hasMany(OrderPayment::class);
    }
}
