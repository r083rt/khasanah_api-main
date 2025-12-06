<?php

namespace App\Models\Reporting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class IngredientUsageStatus extends Model
{
    use HasFactory;
    use ColumnFilterer;
    use ColumnSorter;

    protected $table = 'ingredient_usage_status';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'branch_id',
        'date',
        'status_po_production_cookie',
        'status_po_production_brownies',
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
}
