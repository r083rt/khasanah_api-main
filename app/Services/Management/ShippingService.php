<?php

namespace App\Services\Management;

use App\Models\Management\Shipping;

class ShippingService
{
    /**
     * Get all
     */
    public static function getAll()
    {
        return Shipping::select('id', 'name')->orderBy('name')->get();
    }
}
