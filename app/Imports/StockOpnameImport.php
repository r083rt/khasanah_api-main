<?php

namespace App\Imports;

use App\Jobs\Purchasing\StockOpnameImport as JobsPurchasingStockOpnameImport;
use App\Models\Purchasing\StockOpnameImport as PurchasingStockOpnameImport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;

class StockOpnameImport implements ToCollection
{
    protected $data;
    /**
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection(Collection $rows)
    {
        $data = $this->data;
        $authId = Auth::id();

        foreach ($rows as $key => $row) {
            if ($key > 0) {
                $model = PurchasingStockOpnameImport::create([
                    'user_id' => $authId,
                    'branch_id' => $row[0],
                    'week' => $data['week'],
                    'month' => $data['month'],
                    'year' => $data['year'],
                    'is_last_stock' =>  $data['is_last_stock'],
                    'product_ingredient_id' => $row[2],
                    'product_recipe_unit_id_1' =>  $row[4],
                    'product_recipe_unit_id_2' =>  $row[5],
                    'product_recipe_unit_id_3' =>  $row[6],
                    'stock_1' =>  $row[7],
                    'stock_2' =>  $row[8],
                    'stock_3' =>  $row[9],
                ]);

                dispatch(new JobsPurchasingStockOpnameImport([
                    'id' => $model->id,
                    'branch_id' => $row[0],
                    'product_ingredient_id' => $row[2],
                    'product_recipe_unit_id_1' =>  $row[4],
                    'product_recipe_unit_id_2' =>  $row[5],
                    'product_recipe_unit_id_3' =>  $row[6],
                ]));
            }
        }
    }
}
