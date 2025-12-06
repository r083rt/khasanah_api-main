<?php

namespace App\Models;

use App\Models\Management\UserBranch;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Traits\ColumnSorter;
use App\Traits\ColumnFilterer;
use Illuminate\Support\Facades\Auth;

class User extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable;
    use Authorizable;
    use HasFactory;
    use SoftDeletes;
    use ColumnSorter;
    use ColumnFilterer;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'status',
        'password',
        'branch_id',
        'role_id'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'permissions',
    ];

    /**
     * Scope a query to only include popular users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBranch($query)
    {
        $branchId = Auth::user()->branch_id;
        if ($branchId != 1) {
            return $query->where('branch_id', $branchId)->whereNotNull('branch_id');
        }
    }

    /**
     * Get the permissions attribute.
     *
     * @return integer
     */
    public function getPermissionsAttribute()
    {
        $roleId = $this->role_id;

        return Permission::select('id', 'name', 'action')->whereHas('roles', function ($query) use ($roleId) {
            $query->where('role_id', $roleId);
        })->pluck('action')->toArray();
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Get the branch for the user.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the roles for the user.
     */
    public function roles()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the role for the user.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the session for the user.
     */
    public function session()
    {
        return $this->hasOne(UserSession::class, 'user_id');
    }

    /**
     * Get the suppliers for the user.
     */
    public function suppliers()
    {
        return $this->hasMany(UserBranch::class);
    }
}
