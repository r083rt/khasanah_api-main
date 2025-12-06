<?php

namespace App\Models\Purchasing;

use App\Models\Inventory\Brand;
use App\Models\Inventory\ProductRecipeUnit;
use App\Models\ProductIngredient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class PoSupplierDetail extends Model
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
        'po_supplier_id',
        'product_ingredient_id',
        'product_recipe_unit_id',
        'brand_id',
        'qty',
        'barcode',
        'qty_received',
        'qty_returned',
        'qty_bonus',
        'note',
        'received_by',
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
     * Get the poSupplier for the po supplier detail.
     */
    public function poSupplier()
    {
        return $this->belongsTo(PoSupplier::class);
    }

    /**
     * Get the productIngredient for the po supplier detail.
     */
    public function productIngredient()
    {
        return $this->belongsTo(ProductIngredient::class);
    }

    /**
     * Get the productRecipeUnit for the po supplier detail.
     */
    public function productRecipeUnit()
    {
        return $this->belongsTo(ProductRecipeUnit::class);
    }

    /**
     * Get the brand for the po supplier detail.
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the received by for the po supplier detail.
     */
    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
