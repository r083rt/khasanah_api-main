<?php

namespace App\Models\Production;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class BrowniesTargetPlanProduct extends Model
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
        'branch_id',
        'product_id',
        'is_production',
        'day'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_production' => 'boolean'
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
}
