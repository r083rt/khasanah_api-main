<?php

namespace App\Models\Reporting;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class MonitoringClosingSummaryProduct extends Model
{
    use HasFactory;
    use ColumnFilterer;
    use ColumnSorter;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'monitoring_closing_summary_id',
        'branch_id',
        'product_id',
        'product_category_id',
        'date',
        'first_stock',
        'in',
        'sale',
        'order',
        'return',
        'transfer_stock',
        'remains_closing',
        'difference',
        'hpp_total'
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

    /**
     * Get the branch for the order.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the summary for the products.
     */
    public function summary()
    {
        return $this->belongsTo(MonitoringClosingSummary::class);
    }
}
