<?php

namespace App\Models\Inventory;

use App\Jobs\MonitoringClosingSummary\TransferStock;
use App\Models\Inventory\TransferStock as InventoryTransferStock;
use App\Models\Product;
use App\Models\ProductIngredient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class TransferStockProduct extends Model
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
        'transfer_stock_id',
        'product_id',
        'product_ingredient_id',
        'box',
        'code',
        'qty',
        'price',
        'total_price',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::created(function ($data) {
            $transferStock = InventoryTransferStock::select('status', 'branch_receiver_id')->find($data['transfer_stock_id']);
            if ($transferStock) {
                dispatch(new TransferStock([
                    'product_id' => $data->product_id,
                    'branch_id' => $transferStock->branch_receiver_id,
                    'qty' => $data->qty,
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
     * Get the transfer stock for the transfer stock product.
     */
    public function transferStock()
    {
        return $this->belongsTo(InventoryTransferStock::class);
    }

    /**
     * Get the product for the transfer stock product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the ingredient for the transfer stock product.
     */
    public function ingredient()
    {
        return $this->belongsTo(ProductIngredient::class, 'product_ingredient_id');
    }
}
