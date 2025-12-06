<?php

namespace App\Models\Purchasing;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Illuminate\Support\Facades\Auth;

class StockOpname extends Model
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
        'week',
        'month',
        'year',
        'is_last_stock',
        'status',
        'updated_by',
        'created_by',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        parent::boot();
        static::creating(function ($data) {
            $data->created_by = Auth::id();
        });

        static::updating(function ($data) {
            $data->updated_by = Auth::id();
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
        'status_indo',
        'created_at_indo',
        'month_indo',
    ];

    /**
     * Get the closing indo attribute.
     *
     * @return integer
     */
    public function getCreatedAtIndoAttribute()
    {
        if ($this->created_at) {
            return tanggal_indo($this->created_at->format('Y-m-d'), true);
        }

        return null;
    }

    /**
     * Get the month indo attribute.
     *
     * @return integer
     */
    public function getMonthIndoAttribute()
    {
        if ($this->month) {
            return month_indo($this->month);
        }

        return null;
    }

    /**
     * Get the status indo.
     *
     * @param  string  $value
     * @return void
     */
    public function getStatusIndoAttribute()
    {
        switch ($this->status) {
            case 'approved':
                return "Disetujui";
                break;

            case 'rejected':
                return "Ditolak";
                break;

            default:
                return "Baru";
                break;
        }
    }

    /**
     * Get the created_by for the stock opname.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the created_by for the stock opname.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the branch products for the stock opname.
     */
    public function stockOpnameIngredient()
    {
        return $this->hasMany(StockOpnameIngredient::class, 'stock_opname_id');
    }
}
