<?php

namespace App\Models\Distribution;

use App\Models\Distribution\PoOrderProduct;
use App\Models\Inventory\ProductRecipeUnit;
use App\Models\ProductIngredient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class PoOrderIngredientDetail extends Model
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
        'po_order_ingredient_id',
        'product_ingredient_id',
        'product_ingredient_unit_id',
        'qty'
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
     * Get the ingredient for the po order ingredient detail.
     */
    public function ingredient()
    {
        return $this->belongsTo(ProductIngredient::class, 'product_ingredient_id');
    }

    /**
     * Get the unit for the po order ingredient detail.
     */
    public function unit()
    {
        return $this->belongsTo(ProductRecipeUnit::class, 'product_ingredient_unit_id');
    }

    /**
     * Get the po_order_ingredient_id for the po order ingredient detail.
     */
    public function poOrderIngredient()
    {
        return $this->belongsTo(PoOrderProduct::class, 'po_order_ingredient_id');
    }
}
