<?php

namespace App\Models\Inventory;

use App\Models\Management\Division;
use App\Models\ProductIngredient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Illuminate\Support\Facades\Auth;

class PackagingRecipe extends Model
{
    use HasFactory;
    use ColumnFilterer;
    use ColumnSorter;

    protected $table = 'master_packaging_recipes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'division_id',
        'master_packaging_id',
        'master_packaging_recipe_id',
        'product_ingredient_id',
        'product_ingredient_recipe_unit_id',
        'measure',
        'created_by',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($data) {
            $data->created_by = Auth::id();
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
     * Get the packaging for the packaging recipe.
     */
    public function packaging()
    {
        return $this->belongsTo(Packaging::class);
    }

     /**
     * Get the packaging for the packaging recipe.
     */
    public function packagingRecipe()
    {
        return $this->belongsTo(Packaging::class, 'master_packaging_recipe_id');
    }

    /**
     * Get the cretedBy for the packaging recipe.
     */
    public function cretedBy()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the productIngredient for the packaging recipe.
     */
    public function productIngredient()
    {
        return $this->belongsTo(ProductIngredient::class);
    }

    /**
     * Get the division for the packaging recipe.
     */
    public function division()
    {
        return $this->belongsTo(Division::class);
    }
}
