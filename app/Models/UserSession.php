<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class UserSession extends Model
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
        'user_id',
        'last_login_at',
        'last_active_at',
        'os',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'last_login_at' => 'datetime',
        'last_active_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'last_login_at_indo',
        'last_active_at_indo',
    ];

    /**
     * Get the login at indo attribute.
     *
     * @return integer
     */
    public function getLastLoginAtIndoAttribute()
    {
        if ($this->last_login_at) {
            return tanggal_indo($this->last_login_at->format('Y-m-d H:i:s'), true);
        }

        return null;
    }

    /**
     * Get the login at indo attribute.
     *
     * @return integer
     */
    public function getLastActiveAtIndoAttribute()
    {
        if ($this->last_active_at) {
            return tanggal_indo($this->last_active_at->format('Y-m-d H:i:s'), true);
        }

        return null;
    }

    /**
     * Get the user for the user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
