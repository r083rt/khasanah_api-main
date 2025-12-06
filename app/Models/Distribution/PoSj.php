<?php

namespace App\Models\Distribution;

use App\Models\Branch;
use App\Models\Management\Shipping;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Illuminate\Support\Facades\Auth;

class PoSj extends Model
{
    use HasFactory;
    use ColumnFilterer;
    use ColumnSorter;

    protected $table = 'po_sj';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sj_number',
        'shipping_id',
        'delivery_date',
        'vehicle_number',
        'branch_sender_id',
        'status',
        'created_by'
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($user) {
            $user->sj_number = 'SJ' . date('YmdHis');
            $user->created_by = Auth::user() ? Auth::user()->branch_id : null;
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
        //
    ];

    /**
    * Get the shipping for the po sj.
    */
    public function shipping()
    {
        return $this->belongsTo(Shipping::class);
    }

    /**
    * Get the items for the po sj.
    */
    public function items()
    {
        return $this->hasMany(PoSjItem::class);
    }

    /**
     * Get the created_by for the po sj.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the branch sender for the po sj.
     */
    public function branchSender()
    {
        return $this->belongsTo(Branch::class, 'branch_sender_id');
    }
}
