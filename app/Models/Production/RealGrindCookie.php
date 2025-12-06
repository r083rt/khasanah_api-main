<?php

namespace App\Models\Production;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Illuminate\Support\Facades\Auth;

class RealGrindCookie extends Model
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
        'date',
        'branch_id',
        'type',
        'grind_to',
        'grind_unit',
        'total_press',
        'gram_unit',
        'total_product',
        'note',
        'created_by',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($user) {
            $auth = Auth::user();
            $user->branch_id = $auth->branch_id;
            $user->created_by = $auth->id;
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
        'type_indo',
        'total_gram',
    ];

    /**
     * Get the Total gram attribute.
     *
     * @return integer
     */
    public function getTotalGramAttribute()
    {
        return $this->total_press + $this->gram_unit;
    }

    /**
     * Get the order date indo attribute.
     *
     * @return integer
     */
    public function getTypeIndoAttribute()
    {
        switch ($this->type) {
            case 'cookie':
                return "Roti Manis";
                break;

            default:
                return "Roti Tawar";
                break;
        }
    }

    /**
     * Get the created_by for the order.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
