<?php

namespace App\Exports\Purchasing;

use App\Models\Branch;
use App\Models\Inventory\ProductIngredientBrand;
use App\Models\Inventory\ProductIngredientStock;
use App\Models\Inventory\ProductRecipeUnit;
use App\Models\OrderProduct;
use App\Models\ProductIngredient;
use App\Models\Purchasing\StockOpnameIngredientDetail;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;

class StockOpname implements FromArray, WithHeadings, WithStyles, WithTitle
{
    /**
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Stok Opname';
    }

    /**
    *
    * @return array
    */
    public function headings(): array
    {
        return [
            'ID Cabang',
            'Nama Cabang',
            'ID Bahan',
            'Nama Bahan',
            'ID Satuan 1',
            'ID Satuan 2',
            'ID Satuan 3',
            'QTY Satuan 1',
            'QTY Satuan 2',
            'QTY Satuan 3',
            'Barcode Satuan 2',
            'Keterangan',
        ];
    }

    /**
    *
    * @return array
    */
    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true]],
        ];
    }

    /**
     * Get data to be export
     *
     * @return Collection
     */
    public function array(): array
    {
        $data = ProductIngredient::select('id', 'name', 'code', 'product_recipe_unit_id')->orderBy('name')->get();
        $datas = [];
        foreach ($data as $value) {
            $unit = ProductRecipeUnit::find($value->product_recipe_unit_id);
            if ($unit) {
                $unit_1 = $unit->id;
                $unit_1_name = $unit->name;

                $unit_2 = null;
                $unit_2_name = '-';
                if ($unit->parent_id_2) {
                    $unit2 = ProductRecipeUnit::find($unit->parent_id_2);
                    if ($unit2) {
                        $unit_2 = $unit2->id;
                        $unit_2_name = $unit2->name;
                    }
                }

                $unit_3 = null;
                $unit_3_name = '-';
                if ($unit->parent_id_3) {
                    $unit3 = ProductRecipeUnit::find($unit->parent_id_3);
                    if ($unit3) {
                        $unit_3 = $unit3->id;
                        $unit_3_name = $unit3->name;
                    }
                }

                $barcode = ProductIngredientBrand::where('product_ingredient_id', $value->id)->where('product_recipe_unit_id', $unit->parent_id_2)->first();

                $datas[] = [
                    'id' => $value->id,
                    'name' => $value->name,
                    'unit_1_id' => $unit_1,
                    'unit_2_id' => $unit_2,
                    'unit_3_id' => $unit_3,
                    'unit_1_qty' => null,
                    'unit_2_qty' => null,
                    'unit_3_qty' => null,
                    'barcode' => $barcode?->barcode,
                    'note' => 'Satuan 1 (' . $unit_1_name . '). Satuan 2 (' . $unit_2_name . '). Satuan 3 (' . $unit_3_name . ')',
                ];
            }
        }

        $results = [];
        $branches = Branch::select('id', 'name')->orderBy('name')->get();
        foreach ($branches as $branch) {
            foreach ($datas as $value) {
                $results[] = [
                    'branch_id' => $branch->id,
                    'branch_name' => $branch->name,
                    'id' => $value['id'],
                    'name' => $value['name'],
                    'unit_1_id' => $value['unit_1_id'],
                    'unit_2_id' => $value['unit_2_id'],
                    'unit_3_id' => $value['unit_3_id'],
                    'unit_1_qty' => null,
                    'unit_2_qty' => null,
                    'unit_3_qty' => null,
                    'barcode' => $value['barcode'],
                    'note' => $value['note'],
                ];
            }
        }

        return $results;
    }
}
