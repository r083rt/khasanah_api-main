<?php

namespace App\Models\Purchasing;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Illuminate\Support\Str;

class ForecastConversionApproval extends Model
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
        'stock_opname_id',
        'branch_id',
        'pr_id',
        'month',
        'year',
        'type',
        'status',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($data) {
            $data->pr_id = Str::upper(Str::random(10));
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
        'month_indo',
        'status_setting_po_indo',
    ];

    /**
     * Get the month indo.
     *
     * @param  string  $value
     * @return void
     */
    public function getStatusSettingPoIndoAttribute()
    {
        switch ($this->status) {
            case 'approved':
                $data = 'Belum Setting';
                break;

            case 'setting-po':
                $data = 'Sudah Setting';
                break;

            case 'generating':
                $data = 'Proses Generate';
                break;

            default:
                $data = null;
                break;
        }

        return $data;
    }

    /**
     * Get the month indo.
     *
     * @param  string  $value
     * @return void
     */
    public function getMonthIndoAttribute()
    {
        switch ($this->month) {
            case '1':
                $data = 'Januari';
                break;

            case '2':
                $data = 'Februari';
                break;

            case '3':
                $data = 'Maret';
                break;

            case '4':
                $data = 'April';
                break;

            case '5':
                $data = 'Mei';
                break;

            case '6':
                $data = 'Juni';
                break;

            case '7':
                $data = 'Juli';
                break;

            case '8':
                $data = 'Agustus';
                break;

            case '9':
                $data = 'September';
                break;

            case '10':
                $data = 'Oktober';
                break;

            case '11':
                $data = 'November';
                break;

            case '12':
                $data = 'Desember';
                break;

            default:
                $data = null;
                break;
        }

        return $data;
    }

    /**
     * Get the forecast conversion details for the forecast conversion.
     */
    public function forecastConversionDetails()
    {
        return $this->hasMany(ForecastConversionApprovalDetail::class);
    }

    /**
     * Get the forecastConversionSettingPo for the forecast conversion.
     */
    public function forecastConversionSettingPo()
    {
        return $this->hasMany(ForecastConversionSettingPo::class);
    }

    /**
     * Get the submitted by for the forecast conversion.
     */
    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * Get the spproved by for the forecast conversion.
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

     /**
     * Get the branch for the forecast conversion approval.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
