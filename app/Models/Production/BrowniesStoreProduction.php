<?php

namespace App\Models\Production;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class BrowniesStoreProduction extends Model
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
        'product_id',
        'master_packaging_id',
        'branch_id',
        'product_code',
        'product_name',
        'date',
        'total_po',
        'grind',
        'pcs',
        'recipe_production',
        'product_ids'
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($data) {
            $data->recipe_production = $data->grind * $data->pcs;
        });
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'product_ids' => 'json'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'product_names',
    ];

    /**
     * Get the product for the target plan production.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the getProductNamesAttribute.
     *
     * @param  string  $value
     * @return void
     */
    public function getProductNamesAttribute()
    {
        if (is_array($this->product_ids) && count($this->product_ids) > 0) {
            $productNames = null;
            foreach ($this->product_ids as $row) {
                $product = Product::select('name')->where('id', $row['id'])->first();
                if ($product) {
                    if ($productNames) {
                        $productNames = $productNames . ', ' . $product->name . '(' . $row['value'] . ')';
                    } else {
                        $productNames = $product->name . '(' . $row['value'] . ')';
                    }
                }
            }

            return $productNames;
        }

        return null;
    }
}
