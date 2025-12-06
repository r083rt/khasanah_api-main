<?php

namespace App\Models\Pos;

use App\Models\Branch;
use App\Models\ProductIngredient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Illuminate\Support\Facades\Auth;

class Expense extends Model
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
        'master_expense_id',
        'category',
        'product_ingredient_id',
        'date',
        'cost',
        'qty',
        'total_cost',
        'note',
        'created_by'
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($data) {
            $auth = Auth::user();
            $data->created_by = $auth->id;
            $data->branch_id = $auth->branch_id;
            $data->total_cost = $data->cost * $data->qty;
        });

        static::updating(function ($data) {
            $data->total_cost = $data->cost * $data->qty;
        });
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'date'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'date_indo',
    ];

    /**
     * Scope a query to only include popular users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByMe($query)
    {
        $id = Auth::id();
        return $query->where('created_by', $id);
    }

    /**
     * Scope a query to only include popular users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNow($query)
    {
        return $query->where('date', date('Y-m-d'));
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
     * Get the date indo.
     *
     * @param  string  $value
     * @return void
     */
    public function getDateIndoAttribute()
    {
        if ($this->date) {
            return tanggal_indo($this->date->format('Y-m-d'), true);
        }

        return null;
    }

    /**
     * Get the created_by for the expense.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the branch for the expense.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the ingredient for the expense.
     */
    public function ingredient()
    {
        return $this->belongsTo(ProductIngredient::class);
    }

    /**
     * Get the master for the expense.
     */
    public function master()
    {
        return $this->belongsTo(MasterExpense::class, 'master_expense_id');
    }
}
