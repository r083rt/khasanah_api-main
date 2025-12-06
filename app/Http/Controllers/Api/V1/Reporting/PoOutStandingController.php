<?php

namespace App\Http\Controllers\Api\V1\Reporting;

use App\Exports\Reporting\PoOutStandingExport;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Purchasing\PoSupplier;
use Maatwebsite\Excel\Facades\Excel;

class PoOutStandingController extends Controller
{
    protected $model;
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(PoSupplier $model)
    {
        $this->middleware('permission:po-out-standing.lihat', [
            'only' => ['index']
        ]);
        $this->model = $model;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $explode = explode('-', $request->date);
        $month = isset($explode[1]) ? $explode[1] : null;
        $year = isset($explode[0]) ? $explode[0] : null;

        $data = PoSupplier::select('id', 'purchasing_supplier_id', 'po_number', 'date', 'created_at')
            ->with([
                'purchasingSupplier:id,name',
                'poSupplierDetails:id,po_supplier_id,product_ingredient_id,product_recipe_unit_id,qty',
                'poSupplierDetails.productIngredient:id,name',
                'poSupplierDetails.productRecipeUnit:id,name',
            ]);

        if ($month) {
            $data = $data->where('month', $month);
        }

        if ($year) {
            $data = $data->where('year', $year);
        }

        $data = $data->get();

        return $this->response($data);
    }

    /**
     * Export
     *
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        $explode = explode('-', $request->date);
        $month = isset($explode[1]) ? $explode[1] : null;
        $year = isset($explode[0]) ? $explode[0] : null;

        $fileName = 'Po Out Standing-' . $month . '-' . $year . '-' . rand(0, 1000) . '.csv';
        return Excel::download(new PoOutStandingExport($month, $year), $fileName);
    }
}
