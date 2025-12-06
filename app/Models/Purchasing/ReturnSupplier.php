<?php

namespace App\Models\Purchasing;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;
use Carbon\Carbon;

class ReturnSupplier extends Model
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
        'return_number',
        'supplier_id',
        'po_supplier_id',
        'return_at',
        'note'
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        
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
     * Get the poSupplier for the receive po supplier.
     */
    public function poSupplier()
    {
        return $this->belongsTo(PoSupplier::class);
    }

    /**
     * Get the poSupplierDetails for the po supplier.
     */
    public function returnSuppliersDetails()
    {
        return $this->hasMany(ReturnSuppliersDetail::class);
    }

    /**
     * Get the poSupplier for the receive po supplier.
     */
    public function purchasingSupplier()
    {
        return $this->belongsTo(PurchasingSupplier::class, 'supplier_id');
    }

}
