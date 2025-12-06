<?php

namespace App\Models\Distribution;

use App\Models\Distribution\PoOrderProduct;
use App\Models\Inventory\ProductRecipeUnit;
use App\Models\Product;
use App\Models\ProductIngredient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Illuminate\Support\Facades\Auth;

class PoManualImport extends Model
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
        'product_code',
        'qty',
        'type',
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
        'type_fix',
        'product_id',
    ];

    /**
     * Get the product id.
     *
     * @return void
     */
    public function getProductIdAttribute()
    {
        $code = $this->product_code;
        if ($this->type == 'produk') {
            $product = Product::select('id')->where('code', $code)->first();
            if ($product) {
                return $product->id;
            }
        } else {
            $product = ProductIngredient::select('id')->where('code', $code)->first();
            if ($product) {
                return $product->id;
            }
        }

        return null;
    }

    /**
     * Get the type fix.
     *
     * @return void
     */
    public function getTypeFixAttribute()
    {
        switch ($this->type) {
            case 'produk':
                return "product";
                break;

            default:
                return "ingredient";
                break;
        }
    }
}
