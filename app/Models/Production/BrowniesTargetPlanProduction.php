<?php

namespace App\Models\Production;

use App\Models\Branch;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class BrowniesTargetPlanProduction extends Model
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
        'date',
        'day',
        'total_po',
        'barrel',
        'barrel_conversion',
        'edit_barrel',
        'barrel_different',
        'recipe_production'
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($data) {
            $data->recipe_production = $data->edit_barrel * $data->barrel;
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
        'day_indo'
    ];

    /**
     * Get the material_delivery_type_indo.
     *
     * @param  string  $value
     * @return void
     */
    public function getDayIndoAttribute()
    {
        switch ($this->day) {
            case 'monday':
                $data = 'Senin';
                break;

            case 'tuesday':
                $data = 'Selasa';
                break;

            case 'wednesday':
                $data = 'Rabu';
                break;

            case 'thursday':
                $data = 'Kamis';
                break;

            case 'friday':
                $data = 'Jumat';
                break;

            case 'saturday':
                $data = 'Sabtu';
                break;

            case 'sunday':
                $data = 'Minggu';
                break;

            default:
                $data = null;
                break;
        }

        return $data;
    }

    /**
     * Get the product for the target plan production.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
