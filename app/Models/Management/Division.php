<?php

namespace App\Models\Management;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class Division extends Model
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
        'name',
        'parent_id',
        'code'
    ];

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
        'sub_divisions',
        'division',
    ];

    /**
     * Get the division attribute.
     *
     * @return integer
     */
    public function getDivisionAttribute()
    {
        return self::select('id', 'name')->where('id', $this->parent_id)->first();
    }

     /**
     * Get the sub_division attribute.
     *
     * @return integer
     */
    public function getSubDivisionsAttribute()
    {
        return self::select('id', 'name')->where('parent_id', $this->id)->get();
    }

    /**
     * Get the parent for the division.
     */
    public function parent()
    {
        return $this->belongsTo(Division::class);
    }
}
