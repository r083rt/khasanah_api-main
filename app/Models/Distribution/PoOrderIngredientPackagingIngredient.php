<?php

namespace App\Models\Distribution;

use App\Models\ProductIngredient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Illuminate\Support\Facades\Auth;

class PoOrderIngredientPackagingIngredient extends Model
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
        'po_order_ingredient_packaging_id',
        'product_ingredient_id',
        'qty'
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
    * Get the ingredient for the po order ingredient.
    */
    public function ingredient()
    {
        return $this->belongsTo(ProductIngredient::class, 'product_ingredient_id');
    }

    // /**
    // * Get the ingredient for the po order ingredient.
    // */
    // public function ingredient()
    // {
    //     return $this->belongsTo(ProductIngredient::class, 'product_ingredient_id');
    // }
}
