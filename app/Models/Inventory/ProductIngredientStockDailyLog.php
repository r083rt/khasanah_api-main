<?php

namespace App\Models\Inventory;

use App\Models\Branch;
use App\Models\ProductIngredient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Illuminate\Support\Facades\Auth;

class ProductIngredientStockDailyLog extends Model
{
    use HasFactory;
    use ColumnFilterer;
    use ColumnSorter;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'branch_id',
        'product_ingredient_id',
        'stock',
        'product_recipe_unit_id',
        'date',
        'created_at',
        'updated_at',
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
     * Get the branch for the product ingredient stock.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the product ingredient for the user.
     */
    public function productIngredient()
    {
        return $this->belongsTo(ProductIngredient::class);
    }

    /**
     * Get the units for the product ingredient.
     */
    public function productIngredientUnit()
    {
        return $this->belongsTo(ProductRecipeUnit::class, 'product_recipe_unit_id');
    }
}
