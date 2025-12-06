<?php

namespace App\Models\Pos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class ClosingExport extends Model
{
    use HasFactory;
    use ColumnFilterer;
    use ColumnSorter;

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'closing_id',
        'note',
        'branch_name',
        'local_system',
        'central_system',
        'cashier_income',
        'deposit_difference',
        'cost',
        'payment_cash',
        'payment_noncash',
        'sales_cash',
        'sales_noncash',
        'initial_capital',
        'local_central_difference',
        'dp_cash_order',
        'dp_noncash_order',
        'dp_cash_withdrawal',
        'dp_noncash_withdrawal',
        'credit',
        'created_by_name',
        'date',
        'status',
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
        //
    ];

    /**
     * Get the closing products for the closing detail.
     */
    public function closing()
    {
        return $this->belongsTo(Closing::class);
    }
}
