<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class Menu extends Model
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
        'parent_id',
        'title',
        'classification',
        'icon',
        'url',
        'type',
        'order_menu',
        'is_displayed'
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
        //
    ];

    /**
     * Scope a query to only include popular users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_displayed', 1);
    }

    /**
     * Get the permissions for the user.
     */
    public function permissions()
    {
        return $this->hasMany(Permission::class, 'menu_id');
    }

    /**
     * Get available menu for current user
     * @param int $roleId
     * @return array
     */
    public static function getAvailableMenuId($roleId)
    {
        return self::with(['permissions'])->whereHas('permissions', function ($q) use ($roleId) {
            $q->where('action', 'like', '%.lihat%')->whereHas('roles', function ($query) use ($roleId) {
                $query->where('role_id', $roleId);
            });
        })
        ->where('is_displayed', 1)
        ->pluck('id')
        ->toArray();
    }

    /**
     * Get child from menu
     * @param int $parentId
     * @param array $id
     * @return array
     */
    public static function getChild($parentId, $menuIds)
    {
        return self::where('parent_id', $parentId)
            ->where('is_displayed', 1)
            ->whereIn('id', $menuIds)
            ->orderBy('order_menu')
            ->get();
            // ->toArray();
    }
}
