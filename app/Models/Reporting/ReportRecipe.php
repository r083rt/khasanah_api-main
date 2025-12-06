<?php

namespace App\Models\Reporting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class ReportRecipe extends Model
{
    use HasFactory;
    use ColumnFilterer;
    use ColumnSorter;

    protected $connection= 'report';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'master_packaging_id',
        'product_id',
        'product_name',
        'product_code',
        'product_ingredient_id',
        'ingredient_name',
        'ingredient_code',
        'qty',
        'product_recipe_unit_id',
        'unit_name',
        'logs'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'logs' => 'json'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        //
    ];
}
