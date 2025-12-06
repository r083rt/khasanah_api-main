<?php

namespace App\Models\Purchasing;

use App\Models\Branch;
use App\Models\Inventory\ProductRecipeUnit;
use App\Models\ProductRecipe;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class StockOpnameIngredientDetail extends Model
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
        'branch_id',
        'stock_opname_ingredient_id',
        'stock_opname_id',
        'product_ingredient_id',
        'product_recipe_unit_id',
        'stock_system',
        'stock_real',
        'stock_difference',
        'note',
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
     * Get the stock opname ingredient for the stock opname ingredient details.
     */
    public function stockOpnameIngredient()
    {
        return $this->belongsTo(StockOpnameIngredient::class);
    }

    /**
     * Get the product recipe unit for the stock opname ingredient details.
     */
    public function productRecipeUnit()
    {
        return $this->belongsTo(ProductRecipeUnit::class);
    }

    /**
     * Get the branch for the stock opname ingredient detail.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
