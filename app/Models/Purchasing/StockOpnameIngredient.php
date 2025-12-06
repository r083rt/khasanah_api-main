<?php

namespace App\Models\Purchasing;

use App\Models\Branch;
use App\Models\ProductIngredient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class StockOpnameIngredient extends Model
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
        'stock_opname_id',
        'branch_id',
        'product_ingredient_id',
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
     * Get the stock opname for the stock opname ingredient.
     */
    public function stockOpname()
    {
        return $this->belongsTo(StockOpname::class);
    }

    /**
     * Get the product ingredient for the stock opname ingredient.
     */
    public function productIngredient()
    {
        return $this->belongsTo(ProductIngredient::class);
    }

    /**
     * Get the stockOpnameIngredientDetail for the stock opname ingredient.
     */
    public function stockOpnameIngredientDetail()
    {
        return $this->hasMany(StockOpnameIngredientDetail::class, 'stock_opname_ingredient_id');
    }

    /**
     * Get the branch for the stock opname ingredient.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
