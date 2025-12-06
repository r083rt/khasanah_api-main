<?php

namespace App\Models\Management;

use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ColumnFilterer;
use App\Traits\ColumnSorter;

class BranchSupplier extends Model
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
        'supplier_id',
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
     * Get the user for the user branch.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the branch for the user branch.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the product ingredients for the user branch.
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'branch_supplier_products', 'branch_supplier_id', 'product_id')->withTimestamps();
    }
}
