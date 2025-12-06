<?php

namespace App\Services\Management;

use App\Models\Branch;
use Illuminate\Support\Facades\Cache;

class BranchService
{
    /**
     * Get all
     */
    public static function getAll($request = null, $filter = [], $all = true)
    {
        $data = Branch::select('id', 'name')->branch($all);
        if ($request && $territoryId = $request->territory_id) {
            $data = $data->where('territory_id', $territoryId);
        }

        foreach ($filter as $key => $value) {
            if (is_array($value)) {
                $data = $data->whereIn($key, $value);
            } else {
                $data = $data->where($key, $value);
            }
        }

        $data = $data->orderBy('name')->get();

        return $data;
    }
}
