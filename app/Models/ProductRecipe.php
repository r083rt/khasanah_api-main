<?php

namespace App\Models;

use App\Models\Inventory\Packaging;
use App\Models\Inventory\ProductRecipeUnit;
use App\Models\Management\Division;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Illuminate\Support\Facades\Artisan;

class ProductRecipe extends Model
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
        'name',
        'master_packaging_id',
        'division_id',
        'product_id',
        'product_ingredient_id',
        'product_recipe_unit_id',
        'measure',
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
     * Get the ingredient for the user.
     */
    public function ingredient()
    {
        return $this->belongsTo(ProductIngredient::class, 'product_ingredient_id', 'id');
    }

    /**
     * Get the unit for the user.
     */
    public function unit()
    {
        return $this->belongsTo(ProductRecipeUnit::class, 'product_recipe_unit_id', 'id');
    }

    /**
     * Get the user for the user.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by')->select('id', 'name');
    }

    /**
     * Get the product for the product recipe.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the units for the product ingredient.
     */
    public function units()
    {
        return $this->belongsToMany(ProductIngredient::class, 'product_ingredient_units', 'product_ingredient_id', 'product_recipe_unit_id')->withTimestamps();
    }

    /**
     * Get the division for the product recipe.
     */
    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * Get the masterPackaging for the product recipe.
     */
    public function masterPackaging()
    {
        return $this->belongsTo(Packaging::class);
    }
}
