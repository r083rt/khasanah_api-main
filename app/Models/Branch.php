<?php

namespace App\Models;

use App\Models\Management\Area;
use App\Models\Management\BranchDiscount;
use App\Models\Management\BranchSupplier;
use App\Models\Management\Territory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Branch extends Model
{
    use HasFactory;
    use ColumnFilterer;
    use ColumnSorter;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'phone',
        'zip_code',
        'material_delivery_type',
        'schedule',
        'address',
        'initial_capital',
        'is_production',
        'note',
        'territory_id',
        'area_id',
        'discount_active'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_production' => 'boolean'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'material_delivery_type_indo',
        'schedule_indo',
    ];

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
                return $query->where('id', $branchId);
            }
        } else {
            return $query->where('id', $branchId);
        }
    }

    /**
     * Get the material_delivery_type_indo.
     *
     * @param  string  $value
     * @return void
     */
    public function getMaterialDeliveryTypeIndoAttribute()
    {
        switch ($this->material_delivery_type) {
            case 'daily':
                return "Harian";
                break;

            case 'three_days':
                return "3 Hari";
                break;

            case 'weekly':
                return "Mingguan";
                break;

            default:
                return "Tahunan";
                break;
        }
    }

    /**
     * Get the material_delivery_type_indo.
     *
     * @param  string  $value
     * @return void
     */
    public function getScheduleIndoAttribute()
    {
        switch ($this->schedule) {
            case 'monday':
                return "Senin";
                break;

            case 'tuesday':
                return "Selasa";
                break;

            case 'wednesday':
                return "Rabu";
                break;

            case 'thursday':
                return "Kamis";
                break;

            case 'friday':
                return "Jumat";
                break;

            case 'saturday':
                return "Sabtu";
                break;

            default:
                return "Minggu";
                break;
        }
    }

    /**
     * Get the territory for the branch.
     */
    public function territory()
    {
        return $this->belongsTo(Territory::class);
    }

    /**
     * Get the area for the branch.
     */
    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * Get the discounts for the branch.
     */
    public function discounts()
    {
        return $this->hasMany(BranchDiscount::class);
    }

    /**
     * Get the suppliers for the branch.
     */
    public function suppliers()
    {
        return $this->hasMany(BranchSupplier::class);
    }
}
