<?php

namespace App\Models\Pos;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Illuminate\Support\Facades\Auth;

class ClosingDetail extends Model
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
        'closing_id',
        'local_system',
        'central_system',
        'deposit_difference',
        'cashier_deposit',
        'cost',
        'payment_cash',
        'payment_noncash',
        'sales_cash',
        'sales_noncash',
        'local_central_difference',
        'dp_cash_order',
        'dp_noncash_order',
        'dp_cash_withdrawal',
        'dp_noncash_withdrawal',
        'credit',
        'refund'
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
