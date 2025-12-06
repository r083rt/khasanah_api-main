<?php

namespace App\Models\Inventory;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class Packaging extends Model
{
    use HasFactory;
    use ColumnFilterer;
    use ColumnSorter;

    protected $table = 'master_packagings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'type',
        'grinds',
        'gramasi',
        'gramasi_production',
        'unit',
        'barcode',
        'code'
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
        'type_indo'
    ];

    /**
     * Get the type indo attribute.
     *
     * @return integer
     */
    public function getTypeIndoAttribute()
    {
        switch ($this->type) {
            case 'brownies':
                return 'Brownies';
                break;

            case 'sponge':
                return 'Bolu';
                break;

            case 'cake':
                return 'Cake';
                break;

            case 'cookie':
                return 'Roti Manis';
                break;

            case 'bread':
                return 'Roti Tawar';
                break;

            case 'cream':
                return 'Cream';
                break;

            default:
                return null;
                break;
        }
    }

    /**
     * Get the products for the packaging.
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'master_packaging_products', 'master_packaging_id', 'product_id')->withTimestamps();
    }

    /**
     * Get the recipes for the packaging.
     */
    public function recipes()
    {
        return $this->hasMany(PackagingRecipe::class, 'master_packaging_id');
    }
}
