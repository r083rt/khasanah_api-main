<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Illuminate\Support\Facades\Auth;

class CookieProduction extends Model
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
        'product_id',
        'date',
        'branch_name',
        'product_name',
        'target',
        'buffer',
        'order',
        'remains',
        'total_target',
        'total_target_after_remains',
        'real_grinds',
        'status',
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
     * Get the grinds for the cookie production.
     */
    public function grinds()
    {
        return $this->hasMany(CookieProductionGrind::class);
    }
}
