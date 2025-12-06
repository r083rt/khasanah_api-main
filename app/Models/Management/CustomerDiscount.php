<?php

namespace App\Models\Management;

use App\Models\CustomerDiscountLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class CustomerDiscount extends Model
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
        'customer_id',
        'product_category_id',
        'product_id',
        'discount_type',
        'discount',
        'created_by',
    ];

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
     * Get the logs for the customer discount.
     */
    public function logs()
    {
        return $this->hasMany(CustomerDiscountLog::class);
    }

    /**
     * Get the user for the customer discount.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the product for the customer discount.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
