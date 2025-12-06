<?php

namespace App\Imports;

use App\Models\ProductCategory;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class ProductsCategoryImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $key => $row) {
            if ($key > 0) {
                ProductCategory::firstOrCreate(
                    [
                        'name' => $row[3]
                    ],
                    [
                        'name' => $row[3]
                    ],
                );
            }
        }
    }
}
