<?php

namespace App\Exports\Reporting;

use App\Models\Order as ModelsOrder;
use App\Models\Distribution\PoSjItem;
use App\Models\Inventory\ProductIngredientBrand;
use App\Models\Inventory\ProductRecipeUnit;
use App\Models\Inventory\TransferStockProduct;
use App\Models\OrderProduct;
use App\Models\Pos\ClosingProduct;
use App\Models\Pos\Expense as PosExpense;
use App\Models\Product;
use App\Models\ProductIngredient;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;

class Order implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $startDate;
    protected $endDate;

    /**
     * @return void
     */
    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'HISTORY PESANAN';
    }

    /**
    *
    * @return array
    */
    public function headings(): array
    {
        return [
            'Cabang',
            'Jenis',
            'Keterangan',
            'Pelanggan',
            'Tanggal Pesan',
            'Tanggal Ambil',
            'Tanggal Penyerahan',
            'Item + Qty',
            'Perekam',
            'Status Pengambilan',
            'Status Pembayaran',
            'Link Detail',
        ];
    }

    /**
    *
    * @return array
    */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function array(): array
    {
        $startDate = $this->startDate;
        $endDate = $this->endDate;
        $branchIds = $this->branchIds;

        $data = PoSjItem::with(['posj', 'branch:id,name', 'posj.branchSender:id,name'])
            ->whereHas('posj', function ($query) use ($startDate, $endDate) {
                $query = $query->where('delivery_date', '>=', $startDate)->where('delivery_date', '<=', $endDate);
            });

        if ($branchIds) {
            $data = $data->whereIn('branch_id', $branchIds);
        }

        $productIngredientIds = ProductIngredient::select('id', 'product_recipe_unit_id')->whereIn('id', $data->pluck('product_ingredient_id'))->get();
        $productRecipeUnits = ProductRecipeUnit::select('id', 'parent_id_2')->whereIn('id', $productIngredientIds->pluck('product_recipe_unit_id'))->get();
        $productBrands = ProductIngredientBrand::select('product_ingredient_id', 'barcode', 'product_recipe_unit_id')
            ->whereIn('product_recipe_unit_id', $productRecipeUnits->pluck('parent_id_2'))
            ->whereIn('product_ingredient_id', $data->pluck('product_ingredient_id'))
            ->get();

        $datas = [];
        foreach ($data->get() as $value) {
            if ($value->product_ingredient_id) {
                $product_recipe_unit_id = $productIngredientIds->where('id', $value->product_ingredient_id)->first()?->product_recipe_unit_id;
                $parent_id_2 = $productRecipeUnits->where('id', $product_recipe_unit_id)->first()?->parent_id_2;
                $product_id = $productBrands->where('product_recipe_unit_id', $parent_id_2)->where('product_ingredient_id', $value->product_ingredient_id)->first()?->barcode;
            } else {
                $product_id = $value->code_item;
            }

            $datas[] = [
                'type' => $value->product_id ? 'Produk' : 'Bahan',
                'date' => $value->posj->delivery_date,
                'sender' => $value->posj->branchSender ? $value->posj->branchSender->name : null,
                'recipient' => $value->branch ? $value->branch->name : null,
                'product_id' => $product_id,
                'product_name' => $value->name_item,
                'product_hpp' => $value->hpp,
                'product_qty_delivery' => $value->qty_delivery,
                'product_unit_delivery' => $value->unit_name_delivery,
                'product_qty' => $value->qty,
                'product_unit' => $value->unit_name,
            ];
        }

        $data = TransferStockProduct::with(['transferStock', 'transferStock.branch_receiver:id,name', 'transferStock.branch_sender:id,name', 'product:id,name,price_sale,product_unit_id,product_unit_delivery_id,unit_value', 'ingredient:id,name,hpp,unit_value', 'product.unit:id,name', 'product.unitDelivery:id,name'])
            ->whereHas('transferStock', function ($query) use ($startDate, $endDate) {
                $query = $query->where('date', '>=', $startDate)->where('date', '<=', $endDate);
            });

        $productIngredientIds = ProductIngredient::select('id', 'product_recipe_unit_id')->whereIn('id', $data->pluck('product_ingredient_id'))->get();
        $productRecipeUnits = ProductRecipeUnit::select('id', 'parent_id_2')->whereIn('id', $productIngredientIds->pluck('product_recipe_unit_id'))->get();
        $productBrands = ProductIngredientBrand::select('product_ingredient_id', 'barcode', 'product_recipe_unit_id')
            ->whereIn('product_recipe_unit_id', $productRecipeUnits->pluck('parent_id_2'))
            ->whereIn('product_ingredient_id', $data->pluck('product_ingredient_id'))
            ->get();

        foreach ($data->get() as $value) {
            if ($value->product_id) {
                $datas[] = [
                    'type' => 'Produk',
                    'date' => $value->transferStock->date ? date('Y-m-d', strtotime($value->transferStock->date)) : null,
                    'sender' => $value->transferStock->branch_sender ? $value->transferStock->branch_sender->name : null,
                    'recipient' => $value->transferStock->branch_receiver ? $value->transferStock->branch_receiver->name : null,
                    'product_id' => $value->code,
                    'product_name' => $value->product ? $value->product->name : null,
                    'product_hpp' => $value->product ? $value->product->price_sale : null,
                    'product_qty_delivery' => $value->product_id ? Product::getTotalUnitDelivery($value->qty, $value->product->unit_value) : null,
                    'product_unit_delivery' => $value->product ? $value->product->unitDelivery ? $value->product->unitDelivery->name : null : null,
                    'product_qty' => $value->qty,
                    'product_unit' => $value->product ? $value->product->unit ? $value->product->unit->name : null : null,
                ];
            } elseif ($value->product_ingredient_id) {
                $product_recipe_unit_id = $productIngredientIds->where('id', $value->product_ingredient_id)->first()?->product_recipe_unit_id;
                $parent_id_2 = $productRecipeUnits->where('id', $product_recipe_unit_id)->first()?->parent_id_2;
                $product_id = $productBrands->where('product_recipe_unit_id', $parent_id_2)->where('product_ingredient_id', $value->product_ingredient_id)->first()?->barcode;

                $datas[] = [
                    'type' => 'Bahan',
                    'date' => $value->transferStock->date ? date('Y-m-d', strtotime($value->transferStock->date)) : null,
                    'sender' => $value->transferStock->branch_sender ? $value->transferStock->branch_sender->name : null,
                    'recipient' => $value->transferStock->branch_receiver ? $value->transferStock->branch_receiver->name : null,
                    'product_id' => $product_id,
                    'product_name' => $value->ingredient ? $value->ingredient->name : null,
                    'product_hpp' => $value->ingredient ? $value->ingredient->hpp : null,
                    'product_qty_delivery' => ProductIngredient::getTotalUnitDelivery($value->qty, $value->ingredient->unit_value),
                    'product_unit_delivery' => $value->ingredient ? $value->ingredient->unitDelivery ? $value->ingredient->unitDelivery->name : null : null,
                    'product_qty' => $value->qty,
                    'product_unit' => $value->ingredient ? $value->ingredient->unit ? $value->ingredient->unit->name : null : null,
                ];
            }
        }

        return $datas;
    }
}
