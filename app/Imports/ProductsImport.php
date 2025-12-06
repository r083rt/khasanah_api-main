<?php

namespace App\Imports;

use App\Models\Branch;
use App\Models\Management\BranchDiscount;
use App\Models\Product;
use App\Models\ProductAvailable;
use App\Models\ProductCategory;
use App\Models\ProductStock;
use App\Models\ProductUnit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class ProductsImport implements ToCollection
{
    protected $branch_id;

    /**
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function collection(Collection $rows)
    {
        $date = date('Y-m-d H:i:s');
        foreach ($rows as $key => $row) {
            if ($key > 0) {
                $category = ProductCategory::select('id')->where('name', isset($row[0]) ? $row[0] : null)->first();
                $unit = ProductUnit::select('id')->where('name', isset($row[4]) ? $row[4] : null)->first();
                $unitDelivery = ProductUnit::select('id')->where('name', isset($row[5]) ? $row[5] : null)->first();
                $product = DB::connection('mysql')->table('products')->select('id')->where('code', isset($row[1]) ? $row[1] : null)->first();
                if ($product) {
                    $product = DB::connection('mysql')->table('products')->where('id', $product->id)->update([
                        'name' => isset($row[3]) ? $row[3] : null,
                        'code' => isset($row[1]) ? $row[1] : null,
                        'barcode' => isset($row[2]) ? $row[2] : null,
                        'product_category_id' => $category ? $category->id : null,
                        'product_unit_id' => $unit ? $unit->id : null,
                        'product_unit_delivery_id' => $unitDelivery ? $unitDelivery->id : null,
                        'price' => isset($row[7]) ? $row[7] : null,
                        'price_sale' => isset($row[6]) ? $row[6] : null,
                        'gramasi' => isset($row[8]) ? $row[8] : null,
                        'mill_barrel' => isset($row[9]) ? $row[9] : null,
                        'shop_roller' => isset($row[10]) ? $row[10] : null,
                        'created_at' => $date,
                        'updated_at' => $date
                    ]);
                } else {
                    $product = DB::connection('mysql')->table('products')->insertGetId([
                            'name' => isset($row[3]) ? $row[3] : null,
                            'code' => isset($row[1]) ? $row[1] : null,
                            'barcode' => isset($row[2]) ? $row[2] : null,
                            'product_category_id' => $category ? $category->id : null,
                            'product_unit_id' => $unit ? $unit->id : null,
                            'product_unit_delivery_id' => $unitDelivery ? $unitDelivery->id : null,
                            'price' => isset($row[7]) ? $row[7] : null,
                            'price_sale' => isset($row[6]) ? $row[6] : null,
                            'gramasi' => isset($row[8]) ? $row[8] : null,
                            'mill_barrel' => isset($row[9]) ? $row[9] : null,
                            'shop_roller' => isset($row[10]) ? $row[10] : null,
                            'created_at' => $date,
                            'updated_at' => $date
                    ]);

                    $branches = Branch::select('id')->get();
                    foreach ($branches as $value) {
                        $productAvailable = ProductAvailable::where('product_id', $product)->where('branch_id', $value->id)->first();
                        if (is_null($productAvailable)) {
                            ProductAvailable::create([
                                'product_id' => $product,
                                'branch_id' => $value->id
                            ]);
                        }
                    }
                }
            }
        }
    }
}
