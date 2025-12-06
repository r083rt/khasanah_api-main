<?php

namespace App\Http\Controllers\Api\V1\Purchasing;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Inventory\ProductIngredientBrand;
use App\Models\ProductIngredient;
use App\Models\Purchasing\ForecastConversionApproval;
use App\Models\Purchasing\ForecastConversionSettingPo;
use App\Models\Inventory\ProductRecipeUnit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ForecastConversionApprovalController extends Controller
{
    /**
     * The user repository instance.
     */
    protected $model;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(ForecastConversionApproval $model)
    {
        $this->middleware('permission:approval-konversi-forecast.lihat', [
            'only' => ['index', 'show', 'listBranch', 'history']
        ]);
        $this->middleware('permission:approval-konversi-forecast.ubah', [
            'only' => ['update']
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
        $data = $this->model->select('id', 'pr_id', 'month', 'year', 'status', 'submitted_by', 'submitted_at')->with(['submittedBy:id,name'])->where('status', 'submitted')->simplePaginate(10);
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function history(Request $request)
    {
        $data = $this->model->select('id', 'pr_id', 'month', 'year', 'status', 'approved_by', 'approved_at', 'submitted_at')->with(['approvedBy:id,name'])->where('status', 'approved')->simplePaginate(10);
        return $this->response($data);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        try {
            //code...
            $data = $this->model->select('id', 'pr_id', 'month', 'year', 'status')->with(['forecastConversionDetails', 'forecastConversionDetails.conversionUnit:id,name', 'forecastConversionDetails.conversionRoundingUnit:id,name', 'forecastConversionDetails.productIngredient:id,name', 'forecastConversionDetails.conversionLatestRoundingUnit:id,name',])->findOrFail($id);

            foreach( $data->forecastConversionDetails as $value ) {
                $productIngredient = ProductIngredient::where('id', $value->product_ingredient_id)->first();
                $unit = ProductRecipeUnit::find($productIngredient->product_recipe_unit_id);
                $value->conversion_unit_price = $productIngredient->hpp;
                $value->conversion_rounding_unit_price = $productIngredient->hpp * $unit?->parent_id_2_conversion;
            }

            return $this->response($data);
        } catch (\Throwable $th) {
            return $this->response($th);
        }
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = $this->validate($request, [
            'status' => 'required|in:approved',
        ]);
        $data['approved_by'] = Auth::id();
        $data['approved_at'] = date('Y-m-d H:i:s');

        $model = $this->model->findOrFail($id);

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            return $model->update($data);
        });

        //pindahkan data ke setting po
        foreach ($model->forecastConversionDetails as $value) {
            $ingredient = ProductIngredient::find($value->product_ingredient_id);
            $brandId = $ingredient->brand_id;

            $brand = ProductIngredientBrand::where('product_ingredient_id', $value->product_ingredient_id)->where('product_recipe_unit_id', $value->conversion_latest_rounding_unit_id)->first();
            $barcode = $brand?->barcode;

            $qty_real = $value->conversion_latest_rounding;
            $qty_total =  round($qty_real * 50 / 100);

            if( $model->additional ) {
                $qty_total = round($qty_real);
            }

            // $qty_total = round($qty_real);
            // $qty_remaining = $qty_real - $qty_total;
            ForecastConversionSettingPo::create([
                'forecast_conversion_approval_id' => $id,
                'product_ingredient_id' => $value->product_ingredient_id,
                'brand_id' => $brandId,
                'barcode' => $barcode,
                'qty_total' => $qty_total,
                'qty_real' => $qty_real,
                'qty_remaining' => null,
                'product_recipe_unit_id' => $value->conversion_latest_rounding_unit_id,
            ]);
        }

        return $this->response($data ? true : false);
    }
}
