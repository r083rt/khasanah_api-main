<?php

namespace App\Models\Pos;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Illuminate\Support\Facades\Auth;

class Closing extends Model
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
        'branch_id',
        'total_income',
        'past_income',
        'cashier_income',
        'initial_capital',
        'total_cost',
        'created_by',
        'approved_by',
        'status',
        'note'
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
            $auth = Auth::user();
            $data->created_by = $auth->id;
            $data->branch_id = $auth->branch_id;
        });

        static::updating(function ($data) {
            $data->cashier_income = $data->total_income - $data->past_income - $data->initial_capital;
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
        'closing_indo',
    ];

    /**
     * Get the closing indo attribute.
     *
     * @return integer
     */
    public function getClosingIndoAttribute()
    {
        if ($this->created_at) {
            return tanggal_indo($this->created_at->format('Y-m-d H:i:s'), true);
        }

        return null;
    }

    /**
     * Scope a query to only include popular users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBranch($query, $all = true)
    {
        $branchId = Auth::user()->branch_id;
        if ($all) {
            if ($branchId != 1) {
                return $query->where('branch_id', $branchId);
            }
        } else {
            return $query->where('branch_id', $branchId);
        }
    }

    /**
     * Cek closing
     */
    public static function cekClosing()
    {
        $now = date('Y-m-d');
        $closing = self::whereDate('created_at', $now)->where('created_by', Auth::id())->first();
        if ($closing) {
            return [
                'date' => $closing->created_at,
                'closing_indo' => tanggal_indo($closing->created_at->format('Y-m-d H:i:s'), true)
            ];
        }

        return $closing;
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

            default:
                return "Belum disetujui";
                break;
        }
    }

    /**
     * Get the created_by for the closing.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the products products for the closing.
     */
    public function products()
    {
        return $this->hasMany(ClosingProduct::class);
    }

    /**
     * Get the branch products for the closing.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the detail closing for the closing.
     */
    public function detail()
    {
        return $this->hasOne(ClosingDetail::class);
    }

    /**
     * Get the closing reference for the closing detail.
     */
    public function reference()
    {
        return $this->hasOne(ClosingDetailReference::class);
    }
}
