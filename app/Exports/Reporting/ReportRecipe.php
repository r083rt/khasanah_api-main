<?php

namespace App\Exports\Reporting;

use App\Models\Inventory\PackagingRecipe;
use App\Models\Pos\ClosingProduct;
use App\Models\Reporting\ReportRecipe as ReportingReportRecipe;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReportRecipe implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $product_ingredient_id;
    protected $master_packaging_id;
    protected $search;

    /**
     * @return void
     */
    public function __construct($product_ingredient_id, $master_packaging_id, $search)
    {
        $this->product_ingredient_id = $product_ingredient_id;
        $this->master_packaging_id = $master_packaging_id;
        $this->search = $search;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Report Resep';
    }

    /**
    *
    * @return array
    */
    public function headings(): array
    {
        return [
            'ID Produk',
            'Nama Produk',
            'ID Bahan',
            'Bahan',
            'Qty Resep',
        ];
    }

    /**
    * @var data
    * @return array
    */
    public function map($data): array
    {
        return [
            $data->product_code,
            $data->product_name,
            $data->ingredient_code,
            $data->ingredient_name,
            $data->qty,
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
    public function query()
    {
        $product_ingredient_id = $this->product_ingredient_id;
        $master_packaging_id = $this->master_packaging_id;
        $search = $this->search;
        $data = ReportingReportRecipe::select('*');

        if ($master_packaging_id) {
            $data = $data->where('master_packaging_id', $master_packaging_id)->whereNull('product_ingredient_id');
        } elseif ($product_ingredient_id) {
            $data = $data->where('product_ingredient_id', $product_ingredient_id);
        }

        if ($search) {
            $data = $data->where(function ($query) use ($search) {
                $query->where('product_name', 'like', '%' . $search . '%')
                    ->orWhere('product_code', 'like', '%' . $search . '%')
                    ->orWhere('ingredient_name', 'like', '%' . $search . '%')
                    ->orWhere('ingredient_code', 'like', '%' . $search . '%');
            });
        }

        return $data->orderBy('product_name');
    }
}
