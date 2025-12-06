<?php

namespace App\Services\Management;

use App\Models\Branch;
use App\Models\Management\Territory;

class TerritoryService
{
    /**
     * Get all
     */
    public static function getAll()
    {
        return Territory::select('id', 'name')->orderBy('name')->get();
    }
}
