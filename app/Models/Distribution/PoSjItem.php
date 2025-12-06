<?php

namespace App\Models\Distribution;

use App\Models\Branch;
use App\Models\Management\Shipping;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Illuminate\Support\Facades\Auth;

class PoSjItem extends Model
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
        'po_sj_id',
        'po_id',
        'type',
        'branch_id',
        'product_id',
        'product_ingredient_id',
        'box_name',
        'code_item',
        'name_item',
        'qty',
        'qty_real',
        'hpp',
        'unit_name',
        'qty_delivery',
        'unit_name_delivery',
        'po_date',
        'received_date',
        'branch_receiver_id',
        'is_added',
        'is_submitted'
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        //
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
    * Get the po sj for the po sj item.
    */
    public function posj()
    {
        return $this->belongsTo(PoSj::class, 'po_sj_id');
    }

    /**
    * Get the branch for the po sj item.
    */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
    * Get the product for the po sj item.
    */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
