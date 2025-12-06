<?php

namespace App\Models;

use App\Models\Inventory\ProductCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use HasFactory;
    use ColumnFilterer;
    use ColumnSorter;
    // use Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'barcode',
        'product_category_id',
        'product_unit_id',
        'price',
        'price_sale',
        'gramasi',
        'note',
        'product_unit_delivery_id',
        'unit_value',
        'mill_barrel',
        'shop_roller',
    ];

    // /**
    //  * Get the name of the index associated with the model.
    //  *
    //  * @return string
    //  */
    // public function searchableAs()
    // {
    //     return 'products_index';
    // }

    // /**
    //  * Get the indexable data array for the model.
    //  *
    //  * @return array
    //  */
    // public function toSearchableArray()
    // {
    //     $array = $this->toArray();

    //     // Customize the data array...

    //     return $array;
    // }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        // static::creating(function ($user) {
        //     // $user->code = self::getCode();
        // });
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
    public function scopeAvailable($query, $all = true, $branchId = null)
    {
        if (is_null($branchId)) {
            $branchId = Auth::user()->branch_id;
        }

        if ($all) {
            if ($branchId != 1) {
                $productIds = ProductAvailable::select('id', 'product_id')->where('branch_id', $branchId)->pluck('product_id');
                return $query->whereIn('id', $productIds);
            }
        } else {
            $productIds = ProductAvailable::select('id', 'product_id')->where('branch_id', $branchId)->pluck('product_id');
            return $query->whereIn('id', $productIds);
        }
    }

    /**
     * Get code
     */
    public static function getCode()
    {
        $code = ProductCode::select('id', 'code')->orderBy('code')->first();
        if ($code) {
            return $code->code;
        } else {
            $code = Product::select('id', 'code')->orderBy('code', 'desc')->first();
            if ($code) {
                $code = (int) $code->code + 1;
                return str_pad($code, 3, '0', STR_PAD_LEFT);
            } else {
                return '001';
            }
        }
    }

    /**
     * Get total unit delivery
     */
    public static function getTotalUnitDelivery($qty, $unitValue)
    {
        if (is_null($unitValue) || $unitValue == 0) {
            return $qty;
        }

        return round($qty / $unitValue, 2);
    }

    /**
     * Get total unit
     */
    public static function getTotalUnit($qty, $unitValue)
    {
        if (is_null($unitValue) || $unitValue == 0) {
            return $qty;
        }

        return round($unitValue * $qty, 2);
    }

    /**
     * Get the recipes for the user.
     */
    public function recipes()
    {
        return $this->hasMany(ProductRecipe::class);
    }

    /**
     * Get the availables for the user.
     */
    public function availables()
    {
        return $this->belongsToMany(Branch::class, 'product_availables')->withTimestamps();
    }

    /**
     * Get the stocks for the user.
     */
    public function stocks()
    {
        $auth = Auth::user();
        if ($auth) {
            return $this->hasMany(ProductStock::class)->where('branch_id', $auth->branch_id);
        }

        return 0;
    }

    /**
     * Get the first_stocks for the user.
     */
    public function first_stocks()
    {
        return $this->hasMany(Pos\ProductFirstStock::class);
    }

    /**
     * Get the codeNew for the user.
     */
    public function codeNew()
    {
        return $this->hasMany(Inventory\ProductCodeNew::class);
    }

    /**
     * Get the category for the user.
     */
    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    /**
     * Get the unit for the user.
     */
    public function unit()
    {
        return $this->belongsTo(ProductUnit::class, 'product_unit_id');
    }

    /**
     * Get the unit delivery for the user.
     */
    public function unitDelivery()
    {
        return $this->belongsTo(ProductUnit::class, 'product_unit_delivery_id');
    }
}
