<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class ProductRecipeUnit extends Model
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
        'note',
        'parent_id',
        'parent_id_2',
        'parent_id_2_conversion',
        'parent_id_3',
        'parent_id_3_conversion',
        'parent_id_4',
        'parent_id_4_conversion'
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
     * Get the recipes for the user.
     */
    public function recipes()
    {
        return $this->hasMany(ProductRecipe::class, 'product_recipe_unit_id', 'id');
    }

    /**
     * Get the parentId for the product recipe unit.
     */
    public function parentId()
    {
        return $this->belongsTo(ProductRecipeUnit::class, 'parent_id');
    }

    /**
     * Get the parentId2 for the product recipe unit.
     */
    public function parentId2()
    {
        return $this->belongsTo(ProductRecipeUnit::class, 'parent_id_2');
    }

    /**
     * Get the parentId3 for the product recipe unit.
     */
    public function parentId3()
    {
        return $this->belongsTo(ProductRecipeUnit::class, 'parent_id_3');
    }

    /**
     * Get the parentId4 for the product recipe unit.
     */
    public function parentId4()
    {
        return $this->belongsTo(ProductRecipeUnit::class, 'parent_id_4');
    }
}
