<?php

namespace App\Models\Production;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class TargetPlan extends Model
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
        'date',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        // 'date' => 'date'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'is_generate'
    ];

    /**
     * Get the material_delivery_type_indo.
     *
     * @param  string  $value
     * @return void
     */
    public function getIsGenerateAttribute()
    {
        return false;
    }

    /**
     * Get the details for the target plan.
     */
    public function details()
    {
        return $this->hasMany(TargetPlanDetail::class);
    }

    /**
     * Get the branch for the target plan.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
