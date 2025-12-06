<?php

namespace App\Models\Distribution;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Illuminate\Support\Facades\Auth;

class PoOrderProductPackaging extends Model
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
        'po_order_product_id',
        'name',
        'barcode',
        'created_by'
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($user) {
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
        //
    ];

    /**
     * Get the created_by for the po order product.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the products for the po order product.
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'po_order_product_packaging_products', 'po_order_product_packaging_id', 'product_id')->withTimestamps()->withPivot('qty');
    }
}
