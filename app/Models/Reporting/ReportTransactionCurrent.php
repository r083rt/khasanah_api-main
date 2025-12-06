<?php

namespace App\Models\Reporting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class ReportTransactionCurrent extends Model
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
        'date',
        'start_time',
        'end_time',
        'product_category_id',
        'product_category_name',
        'branch_id',
        'branch_name',
        'qty',
        'total_price',
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
