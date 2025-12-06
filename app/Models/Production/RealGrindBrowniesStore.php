<?php

namespace App\Models\Production;

use App\Models\Branch;
use App\Models\Inventory\Packaging;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Illuminate\Support\Facades\Auth;

class RealGrindBrowniesStore extends Model
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
        'master_packaging_id',
        'date',
        'branch_id',
        'type',
        'grind_to',
        'grind_unit',
        'gramasi',
        'qty_estimation',
        'qty_real',
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
        'type_indo'
    ];

    /**
     * Get the order date indo attribute.
     *
     * @return integer
     */
    public function getTypeIndoAttribute()
    {
        switch ($this->type) {
            case 'brownies':
                return "Brownies";
                break;

            case 'sponge':
                return "Bolu";
                break;

            default:
                return "Cake";
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

    /**
     * Get the branch for the real grind brownies store.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the packaging for the real grind brownies store.
     */
    public function packaging()
    {
        return $this->belongsTo(Packaging::class, 'master_packaging_id', 'id');
    }
}
