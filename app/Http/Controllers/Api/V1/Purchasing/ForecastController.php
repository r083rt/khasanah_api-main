<?php

namespace App\Http\Controllers\Api\V1\Purchasing;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Imports\ForecastImport as ImportsForecastImport;
use App\Jobs\Purchasing\ForecastConversion;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\Purchasing\Forecast;
use App\Models\Purchasing\ForecastConversion as PurchasingForecastConversion;
use App\Models\Purchasing\ForecastImport;
use App\Models\Purchasing\Trend;
use App\Services\Inventory\ProductService;
use App\Services\Management\BranchService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ForecastController extends Controller
{
    /**
     * The user repository instance.
     */
    protected $model;

    protected $branchService;

    protected $productService;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(Forecast $model, BranchService $branchService, ProductService $productService)
    {
        $this->middleware('permission:forecast.lihat|forecast.show', [
            'only' => ['index', 'show', 'listBranch']
        ]);
        $this->model = $model;
        $this->branchService = $branchService;
        $this->productService = $productService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return $this->response(month());
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listBranch(Request $request)
    {
        return $this->response($this->branchService->getAll()->prepend([
            'id' => 0,
            'name' => "Semua Cabang",
            'material_delivery_type_indo' => null,
            'schedule_indo' => null,
        ]));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $month)
    {
        $data = collect(month())->where('value', $month)->values()->first();

        $branchData = [];
        $branchId = $request->branch_id;
        if ($branchId != 0) {
            $filter = [
                'id' => $branchId
            ];
            $branches = $this->branchService->getAll(null, $filter);
        } else {
            if (is_null($branchId)) {
                $branches = [];
            } else {
                $branches = collect([]);
                $branches->push((object)[
                    'id' => 0,
                    'name' => "Semua Cabang",
                ]);
            }
        }

        foreach ($branches as $row) {
            $productsData = [];
            $products = $this->productService->getAllWhereHaveRecipe();
            foreach ($products as $value) {
                $cek = $this->model->select('sale')->where('product_id', $value->id);

                if ($row->id != 0) {
                    $cek = $cek->where('branch_id', $row->id);
                }

                $sale = $cek->where('month', $month)->where('year', date('Y'))->sum('sale');
                $real_sale = $cek->where('month', $month)->where('year', date('Y'))->sum('real_sale');
                $trend = $cek->where('month', $month)->where('year', date('Y'))->sum('trend');
                $inflation = $cek->where('month', $month)->where('year', date('Y'))->sum('inflation');

                $productsData[] = [
                    'id' => $value->id,
                    'real_sale' => $real_sale,
                    'sale' => $sale,
                    'trend' => $trend,
                    'inflation' => $inflation,
                    'name' => $value->name,
                ];
            }
            $branchData[] = [
                'branch_id' => $row->id,
                'branch_name' => $row->name,
                'products' => $productsData
            ];
        }

        $data['datas'] = $branchData;

        return $this->response($data);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showAdditional(Request $request, $month)
    {
        $data = collect(month())->where('value', $month)->values()->first();

        $branchData = [];
        $branchId = $request->branch_id;
        if ($branchId != 0) {
            $filter = [
                'id' => $branchId
            ];
            $branches = $this->branchService->getAll(null, $filter);
        } else {
            if (is_null($branchId)) {
                $branches = [];
            } else {
                $branches = collect([]);
                $branches->push((object)[
                    'id' => 0,
                    'name' => "Semua Cabang",
                ]);
            }
        }

        foreach ($branches as $row) {
            $productsData = [];
            $products = $this->productService->getAllWhereHaveRecipe();
            foreach ($products as $value) {
                $cek = $this->model->select('sale')->where('product_id', $value->id);

                if ($row->id != 0) {
                    $cek = $cek->where('branch_id', $row->id);
                }

                $sale = $cek->where('month', $month)->where('year', date('Y'))->sum('sale');
                $real_sale = $cek->where('month', $month)->where('year', date('Y'))->sum('real_sale');
                $trend = $cek->where('month', $month)->where('year', date('Y'))->sum('trend');
                $inflation = $cek->where('month', $month)->where('year', date('Y'))->sum('inflation');

                $productsData[] = [
                    'id' => $value->id,
                    'real_sale' => $real_sale,
                    'sale' => $sale,
                    'trend' => $trend,
                    'inflation' => $inflation,
                    'name' => $value->name,
                ];
            }
            $branchData[] = [
                'branch_id' => $row->id,
                'branch_name' => $row->name,
                'products' => $productsData
            ];
        }

        $data['datas'] = $branchData;

        return $this->response($data);
    }

    /**
     * Import
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // public function import(Request $request)
    // {
    //     $data = $this->validate($request, [
    //         'month' => 'required|min:1|max:12',
    //         'file' => 'required|mimes:xlsx,xls|max:10000'
    //     ]);

    //     try {
    //         ForecastImport::whereNotNull('id')->delete();
    //         Excel::import(new ImportsForecastImport($data['month']), $request->file);

    //         return $this->response('Silahkan Preview data sebelum submit');
    //     } catch (\Throwable $th) {
    //         return $this->response('Terjadi kesalahan. Silahkan import kembali dan pastikan file sesuai format', 422);
    //     }
    // }

    public function import(Request $request)
    {
        $data = $this->validate($request, [
            'month' => 'required|min:1|max:12',
            'file' => 'required|mimes:xlsx,xls|max:10000'
        ]);

        try {
            if (!$request->hasFile('file')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'File tidak ditemukan'
                ], 400);
            }

            ForecastImport::whereNotNull('id')->delete();

            Excel::import(
                new ImportsForecastImport($data['month']),
                $request->file('file')
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Silahkan Preview data sebelum submit'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Import Preview
     *
     * @return \Illuminate\Http\Response
     */
    public function importPreview(Request $request)
    {
        $job = DB::table('jobs')->select('id')->where('queue', 'forecast_import')->first();
        if ($job) {
            return response()->json([
                'status' => 'success',
                'message' => 'Data dalam proses validasi. Silahkan cek secara berkala dengan menekan tombol Preview Data.',
                'data' => null,
            ]);
        }

        $data = ForecastImport::orderBy('is_valid')->search($request)->sort($request);
        if ($branch_id = $request->branch_id) {
            $data = $data->where('branch_id', $branch_id);
        }

        if ($request->is_valid || $request->is_valid == '0') {
            $data = $data->where('is_valid', $request->is_valid);
        }
        $data = $data->orderBy('branch_name')->paginate($this->perPage($data));

        if ($data->count() > 0) {
            return response()->json([
                'status' => 'success',
                'message' => 'Sukses',
                'data' => $data
            ]);
        } else {
            return response()->json([
                'status' => 'success',
                'message' => 'Data tidak ditemukan.',
                'data' => $data
            ]);
        }
    }

    /**
     * Import Submit
     *
     * @return \Illuminate\Http\Response
     */
    public function importSubmit(Request $request)
    {
        $cek = PurchasingForecastConversion::select('id')->where('status_generate', 'running')->first();
        if ($cek) {
            return $this->response('Ada data masih dalam proses generate ulang. Kembali beberapa saat lagi', 422);
        }

        $year = date('Y');
        $month = $request->month;
        $additional = $request->additional;

        $branchIds = ForecastImport::pluck('branch_id')->unique();
        Forecast::where([
            'month' => $month,
            'year' => $year,
        ])->delete();

        PurchasingForecastConversion::where([
            'month' => $month,
            'year' => $year,
            'status' => 'new',
        ])->delete();

        $branches = [];
        foreach ($branchIds as $row) {
            $forecastConversion = PurchasingForecastConversion::create([
                'month' => $month,
                'year' => $year,
                'branch_id' => $row,
                'status_generate' => 'running',
                'additional' => $additional
            ]);

            $branches[$row] = $forecastConversion->id;
        }

        // $trendInflasi = Trend::where('month', $month)->where('year', $year)->first();
        // $trend = $trendInflasi?->trend;
        // $inflasi = $trendInflasi?->inflation;

        $datas = ForecastImport::where('is_valid', 1)->get();
        $ProductRecipes = ProductRecipe::select('master_packaging_id', 'product_id', 'product_ingredient_id', 'product_recipe_unit_id', 'measure')->whereIn('product_id', $datas->pluck('product_id')->unique())->get();
        foreach ($datas as $value) {
            if (isset($branches[$value->branch_id])) {
                // $trendTotal = $trend ? round($trend / 100 * $value->total) : 0;
                // $inflasiTotal = $inflasi ? round($inflasi / 100 * $value->total) : 0;
                // $sale = $value->total + $trendTotal + $inflasiTotal;
                $sale = $value->total;

                $data = [
                    'branch_id' => $value->branch_id,
                    'product_id' => $value->product_id,
                    'month' => $value->month,
                    'year' => $year,
                    'sale' => $sale,
                    'real_sale' => $value->total,
                    // 'trend' => $trendTotal,
                    // 'inflation' => $inflasiTotal,
                ];

                Forecast::create($data);
                $data['forecast_conversion_id'] = $branches[$value->branch_id];

                $recipes = $ProductRecipes->where('product_id', $value->product_id);
                $data['recipes'] = $recipes;

                dispatch(new ForecastConversion($data));
            }
        }

        ForecastImport::whereNotNull('id')->delete();

        $key = 'fc_show_' . $month;
        Cache::forget($key);

        $key = 'fc_show_detail_' . $month;
        Cache::forget($key);

        return $this->response('Berhasil disubmit');
    }
}
