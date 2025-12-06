<?php

namespace App\Models\Pos;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Illuminate\Support\Facades\Auth;

class ClosingDetailReference extends Model
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
        'central_system_reference',
        'cost_reference',
        'payment_cash_reference',
        'payment_noncash_reference',
        'sales_cash_reference',
        'sales_noncash_reference',
        'dp_cash_order_reference',
        'dp_noncash_order_reference',
        'dp_cash_withdrawal_reference',
        'dp_noncash_withdrawal_reference',
        'credit_reference',
        'refund_reference'
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
        'central_system_reference' => 'array',
        'cost_reference' => 'array',
        'payment_cash_reference' => 'array',
        'payment_noncash_reference' => 'array',
        'sales_cash_reference' => 'array',
        'sales_noncash_reference' => 'array',
        'dp_cash_order_reference' => 'array',
        'dp_noncash_order_reference' => 'array',
        'dp_cash_withdrawal_reference' => 'array',
        'dp_noncash_withdrawal_reference' => 'array',
        'credit_reference' => 'array',
        'refund_reference' => 'array',
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
     * Get the closing for the closing detail refference.
     */
    public function closing()
    {
        return $this->belongsTo(Closing::class);
    }
}
