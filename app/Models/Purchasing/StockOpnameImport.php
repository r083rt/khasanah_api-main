<?php

namespace App\Models\Purchasing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class StockOpnameImport extends Model
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
        'user_id',
        'branch_id',
        'branch_name',
        'week',
        'month',
        'year',
        'is_last_stock',
        'product_ingredient_id',
        'product_ingredient_name',
        'product_recipe_unit_id_1',
        'product_recipe_unit_1_name',
        'product_recipe_unit_id_2',
        'product_recipe_unit_2_name',
        'product_recipe_unit_id_3',
        'product_recipe_unit_3_name',
        'stock_1',
        'stock_2',
        'stock_3',
        'is_valid',
        'reason',
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
        'month_indo'
    ];

    /**
     * Get the month_indo.
     *
     * @param  string  $value
     * @return void
     */
    public function getMonthIndoAttribute()
    {
        switch ($this->month) {
            case '1':
                $data = 'Januari';
                break;

            case '2':
                $data = 'Februari';
                break;

            case '3':
                $data = 'Maret';
                break;

            case '4':
                $data = 'April';
                break;

            case '5':
                $data = 'Mei';
                break;

            case '6':
                $data = 'Juni';
                break;

            case '7':
                $data = 'Juli';
                break;

            case '8':
                $data = 'Agustus';
                break;

            case '9':
                $data = 'September';
                break;

            case '10':
                $data = 'Oktober';
                break;

            case '11':
                $data = 'November';
                break;

            case '12':
                $data = 'Desember';
                break;

            default:
                $data = null;
                break;
        }

        return $data;
    }
}
