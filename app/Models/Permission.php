<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class Permission extends Model
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
        'action',
        'menu_id',
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
        'status'
    ];

    /**
     * Get the permissions attribute.
     *
     * @return integer
     */
    public function getStatusAttribute()
    {
        return false;
    }

    /**
     * Get the menus for the user.
     */
    public function menus()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    /**
     * Get the roles for the user.
     */
    public function roles()
    {
        return $this->hasMany(RoleHasPermission::class);
    }
}
