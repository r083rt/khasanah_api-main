<?php

namespace App\Http\Controllers\Api\V1\Purchasing;

use App\Exports\Purchasing\ForecastConversion as ReportingForecastConversion;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\GetUserApprovalNotificationToken;
use App\Jobs\Purchasing\ForecastConversion2;
use App\Models\Inventory\ProductRecipeUnit;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Models\ProductRecipe;
use App\Models\Purchasing\Forecast;
use App\Models\Purchasing\ForecastBuffer;
use App\Models\Purchasing\ForecastConversion;
use App\Models\Purchasing\ForecastConversionApproval;
use App\Models\Purchasing\ForecastConversionApprovalDetail;
use App\Models\Purchasing\ForecastConversionApprovalDetailBranch;
use App\Models\Purchasing\ForecastConversionDetail;
use App\Models\Purchasing\ForecastConversionDetailLog;
use App\Services\Management\BranchService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ForecastConversionController extends Controller
{
    /**
     * The user repository instance.
     */
    protected $model;

    protected $branchService;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(ForecastConversion $model, BranchService $branchService)
    {
        $this->middleware('permission:konversi-forecast.lihat', [
            'only' => ['index', 'show', 'listBranch']
        ]);
        $this->middleware('permission:konversi-forecast.ubah', [
            'only' => ['update', 'regenerate', 'export']
        ]);
        $this->model = $model;
        $this->branchService = $branchService;
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
        $cek = ForecastConversion::select('id')->where('month', $month)->whereIn('status_generate', ['running', 'calculation'])->where('year', date('Y'))->first();
        if ($cek) {
            return $this->response('Data masih dalam proses generate ulang. Kembali beberapa saat lagi', 422);
        }

        $cek = DB::table('jobs')->select('id')->where('queue', 'forecast_import')->first();
        $failedJob = DB::table('failed_jobs')->select('id')->where('queue', 'forecast_import')->first();
        if ($cek || $failedJob) {
            return $this->response('Ada proses import forecast. Kembali beberapa saat lagi', 422);
        }

        if ($request->branch_id) {
            $data = $this->model->select('id', 'month', 'year', 'status', 'branch_id')
                ->with(['forecastConversionDetails', 'branch:id,name', 'forecastConversionDetails.conversionUnit:id,name', 'forecastConversionDetails.conversionRoundingUnit:id,name', 'forecastConversionDetails.productIngredient:id,name', 'forecastConversionDetails.conversionLatestRoundingUnit:id,name',])
                ->where('month', $month)
                ->where('year', date('Y'))
                ->where('branch_id', $request->branch_id)
                ->first();

            if ($data) {
                $data->forecast_conversion_details_sort = $data->forecastConversionDetails->sortBy('productIngredient.name')->values();
            }
        } else {
            $key = 'fc_show_' . $month;
            if (!Cache::has($key)) {
                $forecastIds = $this->model->where('month', $month)->where('year', date('Y'))->pluck('id');
                $product_ingredient_ids = ForecastConversionDetail::whereIn('forecast_conversion_id', $forecastIds)->whereNotNull('product_ingredient_id')->groupBy('product_ingredient_id')->pluck('product_ingredient_id');

                $productIngredients = ProductIngredient::select('id', 'name', 'product_recipe_unit_id')->whereIn('id', $product_ingredient_ids)->orderBy('name')->get();
                $forecast_conversion_details = [];
                foreach ($productIngredients as $value) {
                    $detail = ForecastConversionDetail::select('id', 'conversion_unit_id', 'conversion_rounding_unit_id', 'conversion_latest_rounding', 'conversion_latest_rounding_unit_id')->where('product_ingredient_id', $value->id)->whereIn('forecast_conversion_id', $forecastIds)->first();
                    $conversion_unit_id = $detail?->conversion_unit_id;
                    $conversion_unit = $detail?->conversionUnit;
                    $conversion_rounding_unit_id = $detail?->conversion_rounding_unit_id;
                    $conversion_rounding_unit = $detail?->conversionRoundingUnit;
                    $conversion_latest_rounding_unit_id = $detail?->conversion_latest_rounding_unit_id;
                    $conversion_latest_rounding_unit = $detail?->conversionLatestRoundingUnit;

                    $conversion_2 = ForecastConversionDetail::select('conversion_2')->where('product_ingredient_id', $value->id)->whereIn('forecast_conversion_id', $forecastIds)->sum('conversion_2');
                    $conversion = ForecastConversionDetail::select('conversion')->where('product_ingredient_id', $value->id)->whereIn('forecast_conversion_id', $forecastIds)->sum('conversion');
                    $conversion_total = ForecastConversionDetail::select('conversion_total')->where('product_ingredient_id', $value->id)->whereIn('forecast_conversion_id', $forecastIds)->sum('conversion_total');
                    // $buffer = ForecastConversionDetail::select('buffer')->where('product_ingredient_id', $value->id)->whereIn('forecast_conversion_id', $forecastIds)->sum('buffer');

                    $unit = ProductRecipeUnit::where('id', $value->product_recipe_unit_id)->first();
                    $pembagi = 1;
                    if ($unit) {
                        if ($unit->parent_id_4) {
                            $pembagi = $unit->parent_id_2_conversion * $unit->parent_id_3_conversion * $unit->parent_id_4_conversion;
                        } elseif ($unit->parent_id_3) {
                            $pembagi = $unit->parent_id_2_conversion * $unit->parent_id_3_conversion;
                        } elseif ($unit->parent_id_2) {
                            $pembagi = $unit->parent_id_2_conversion;
                        } else{
                            $pembagi = 1;
                        }
                    }

                    //kalau ini dirubah, harus diubah juga pas submit
                    $conversion_latest_rounding = round(forecast_rounding($conversion_total / $pembagi), 2);
                    $forecastBuffer = ForecastBuffer::where('product_ingredient_id', $value->id)->first();
                    $buffer = 0;
                    if ($forecastBuffer) {
                        $buffer = round($forecastBuffer->buffer / 100 * $conversion_latest_rounding);
                    }

                    $forecast_conversion_details[] = [
                        'id' => 0,
                        'forecast_conversion_id' => 0,
                        'product_ingredient_id' => $value->id,
                        'product_ingredient' => [
                            'id' => $value->id,
                            'name' => $value->name,
                        ],
                        'conversion' => $conversion,
                        'conversion_total' => $conversion_total,
                        'buffer' => $buffer,
                        'conversion_2' => round($conversion_2, 2),
                        'conversion_unit_id' => $conversion_unit_id,
                        'conversion_unit' => $conversion_unit,
                        'conversion_rounding' => round(forecast_rounding($conversion_2), 2),
                        'conversion_rounding_unit_id' => $conversion_rounding_unit_id,
                        'conversion_rounding_unit' => $conversion_rounding_unit,
                        'conversion_latest' => round($conversion_total / $pembagi, 2),
                        'conversion_latest_rounding' => $conversion_latest_rounding,
                        'conversion_latest_rounding_total' => $conversion_latest_rounding + $buffer,
                        'conversion_latest_rounding_unit_id' => $conversion_latest_rounding_unit_id,
                        'conversion_latest_rounding_unit' => $conversion_latest_rounding_unit,
                    ];
                }

                $data = [
                    'id' => 0,
                    'month' => $month,
                    'month_indo' => month_indo($month),
                    'year' => date('Y'),
                    'branch_id' => 0,
                    'branch' => [
                        'id' => 0,
                        'name' => 'Semua Cabang',
                    ],
                    'status' => $this->model->where('month', $month)->where('year', date('Y'))->first()?->status,
                    'forecast_conversion_details' => $forecast_conversion_details,
                    'forecast_conversion_details_sort' => $forecast_conversion_details,
                ];

                Cache::put($key, $data, 86400);
            } else {
                $data = Cache::get($key);
            }
        }

        return $this->response($data);
    }

    public function additional_show(Request $request, $month)
    {
        $cek = ForecastConversion::select('id')->where('month', $month)->where('additional', 1)->whereIn('status_generate', ['running', 'calculation'])->where('year', date('Y'))->first();
        if ($cek) {
            return $this->response('Data masih dalam proses generate ulang. Kembali beberapa saat lagi', 422);
        }

        $cek = DB::table('jobs')->select('id')->where('queue', 'forecast_import')->first();
        $failedJob = DB::table('failed_jobs')->select('id')->where('queue', 'forecast_import')->first();
        if ($cek || $failedJob) {
            return $this->response('Ada proses import forecast. Kembali beberapa saat lagi', 422);
        }

        if ($request->branch_id) {
            $data = $this->model->select('id', 'month', 'year', 'status', 'branch_id')
                ->with(['forecastConversionDetails', 'branch:id,name', 'forecastConversionDetails.conversionUnit:id,name', 'forecastConversionDetails.conversionRoundingUnit:id,name', 'forecastConversionDetails.productIngredient:id,name', 'forecastConversionDetails.conversionLatestRoundingUnit:id,name',])
                ->where('month', $month)
                ->where('year', date('Y'))
                ->where('branch_id', $request->branch_id)
                ->where('additional', 1)
                ->first();

            if ($data) {
                $data->forecast_conversion_details_sort = $data->forecastConversionDetails->sortBy('productIngredient.name')->values();
            }
        } else {
            $key = 'fc_show_' . $month;
            if (!Cache::has($key)) {
                $forecastIds = $this->model->where('month', $month)->where('year', date('Y'))->where('additional', 1)->pluck('id');
                $product_ingredient_ids = ForecastConversionDetail::whereIn('forecast_conversion_id', $forecastIds)->whereNotNull('product_ingredient_id')->groupBy('product_ingredient_id')->pluck('product_ingredient_id');

                $productIngredients = ProductIngredient::select('id', 'name', 'product_recipe_unit_id')->whereIn('id', $product_ingredient_ids)->orderBy('name')->get();
                $forecast_conversion_details = [];
                foreach ($productIngredients as $value) {
                    $detail = ForecastConversionDetail::select('id', 'conversion_unit_id', 'conversion_rounding_unit_id', 'conversion_latest_rounding', 'conversion_latest_rounding_unit_id')->where('product_ingredient_id', $value->id)->whereIn('forecast_conversion_id', $forecastIds)->first();
                    $conversion_unit_id = $detail?->conversion_unit_id;
                    $conversion_unit = $detail?->conversionUnit;
                    $conversion_rounding_unit_id = $detail?->conversion_rounding_unit_id;
                    $conversion_rounding_unit = $detail?->conversionRoundingUnit;
                    $conversion_latest_rounding_unit_id = $detail?->conversion_latest_rounding_unit_id;
                    $conversion_latest_rounding_unit = $detail?->conversionLatestRoundingUnit;

                    $conversion_2 = ForecastConversionDetail::select('conversion_2')->where('product_ingredient_id', $value->id)->whereIn('forecast_conversion_id', $forecastIds)->sum('conversion_2');
                    $conversion = ForecastConversionDetail::select('conversion')->where('product_ingredient_id', $value->id)->whereIn('forecast_conversion_id', $forecastIds)->sum('conversion');
                    $conversion_total = ForecastConversionDetail::select('conversion_total')->where('product_ingredient_id', $value->id)->whereIn('forecast_conversion_id', $forecastIds)->sum('conversion_total');
                    // $buffer = ForecastConversionDetail::select('buffer')->where('product_ingredient_id', $value->id)->whereIn('forecast_conversion_id', $forecastIds)->sum('buffer');

                    $unit = ProductRecipeUnit::where('id', $value->product_recipe_unit_id)->first();
                    $pembagi = 1;
                    if ($unit) {
                        if ($unit->parent_id_4) {
                            $pembagi = $unit->parent_id_2_conversion * $unit->parent_id_3_conversion * $unit->parent_id_4_conversion;
                        } elseif ($unit->parent_id_3) {
                            $pembagi = $unit->parent_id_2_conversion * $unit->parent_id_3_conversion;
                        } elseif ($unit->parent_id_2) {
                            $pembagi = $unit->parent_id_2_conversion;
                        } else{
                            $pembagi = 1;
                        }
                    }

                    //kalau ini dirubah, harus diubah juga pas submit
                    $conversion_latest_rounding = round(forecast_rounding($conversion_total / $pembagi), 2);
                    $forecastBuffer = ForecastBuffer::where('product_ingredient_id', $value->id)->first();
                    $buffer = 0;
                    if ($forecastBuffer) {
                        $buffer = round($forecastBuffer->buffer / 100 * $conversion_latest_rounding);
                    }

                    $forecast_conversion_details[] = [
                        'id' => 0,
                        'forecast_conversion_id' => 0,
                        'product_ingredient_id' => $value->id,
                        'product_ingredient' => [
                            'id' => $value->id,
                            'name' => $value->name,
                        ],
                        'conversion' => $conversion,
                        'conversion_total' => $conversion_total,
                        'buffer' => $buffer,
                        'conversion_2' => round($conversion_2, 2),
                        'conversion_unit_id' => $conversion_unit_id,
                        'conversion_unit' => $conversion_unit,
                        'conversion_rounding' => round(forecast_rounding($conversion_2), 2),
                        'conversion_rounding_unit_id' => $conversion_rounding_unit_id,
                        'conversion_rounding_unit' => $conversion_rounding_unit,
                        'conversion_latest' => round($conversion_total / $pembagi, 2),
                        'conversion_latest_rounding' => $conversion_latest_rounding,
                        'conversion_latest_rounding_total' => $conversion_latest_rounding + $buffer,
                        'conversion_latest_rounding_unit_id' => $conversion_latest_rounding_unit_id,
                        'conversion_latest_rounding_unit' => $conversion_latest_rounding_unit,
                    ];
                }

                $data = [
                    'id' => 0,
                    'month' => $month,
                    'month_indo' => month_indo($month),
                    'year' => date('Y'),
                    'branch_id' => 0,
                    'branch' => [
                        'id' => 0,
                        'name' => 'Semua Cabang',
                    ],
                    'status' => $this->model->where('month', $month)->where('year', date('Y'))->first()?->status,
                    'forecast_conversion_details' => $forecast_conversion_details,
                    'forecast_conversion_details_sort' => $forecast_conversion_details,
                ];

                Cache::put($key, $data, 86400);
            } else {
                $data = Cache::get($key);
            }
        }

        return $this->response($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $data = $this->validate($request, [
            'month' => 'required|min:1|max:12',
            'branch_id' => 'required',
            'status' => 'required|in:submitted',
        ]);

        if ($data['branch_id'] == '0') {
            $data['branch_id'] = null;
        }

        //validasi submit
        $cek = ForecastConversionApproval::where('month', $data['month'])->where('year', date('Y'))->first();
        if ($cek) {
            return $this->response('Bulan tersebut sudah submit', 422);
        }

        $model = DB::connection('mysql')->transaction(function () use ($data) {
            return $this->model->where('month', $data['month'])->where('year', date('Y'))->update([
                'status' => $data['status'],
                'submitted_by' => Auth::id(),
                'submitted_at' => date('Y-m-d H:i:s')
            ]);
        });

        if ($model) {
            $data['submitted_by'] = Auth::id();
            $data['submitted_at'] = date('Y-m-d H:i:s');
            $data['year'] = date('Y');
            if($model->additional){
                $data['additional'] = 1;
            }
            $model = ForecastConversionApproval::create($data);

            $forecastIds = $this->model->where('month', $data['month'])->where('year', date('Y'))->pluck('id');
            $product_ingredient_ids = ForecastConversionDetail::whereIn('forecast_conversion_id', $forecastIds)->whereNotNull('product_ingredient_id')->groupBy('product_ingredient_id')->pluck('product_ingredient_id');
            foreach ($product_ingredient_ids as $value) {
                $conversion = ForecastConversionDetail::select('conversion')->where('product_ingredient_id', $value)->whereIn('forecast_conversion_id', $forecastIds)->sum('conversion');
                $conversion_total = ForecastConversionDetail::select('conversion_total')->where('product_ingredient_id', $value)->whereIn('forecast_conversion_id', $forecastIds)->sum('conversion_total');
                // $buffer = ForecastConversionDetail::select('buffer')->where('product_ingredient_id', $value)->whereIn('forecast_conversion_id', $forecastIds)->sum('buffer');
                $conversion_2 = round(ForecastConversionDetail::select('conversion_2')->where('product_ingredient_id', $value)->whereIn('forecast_conversion_id', $forecastIds)->sum('conversion_2'), 2);
                $conversion_rounding = round(forecast_rounding($conversion_2), 2);

                $detail = ForecastConversionDetail::select('id', 'conversion_unit_id')->where('product_ingredient_id', $value)->whereIn('forecast_conversion_id', $forecastIds)->first();
                $conversion_unit_id = $detail?->conversion_unit_id;

                $detail = ForecastConversionDetail::select('id', 'conversion_rounding_unit_id')->where('product_ingredient_id', $value)->whereIn('forecast_conversion_id', $forecastIds)->first();
                $conversion_rounding_unit_id = $detail?->conversion_rounding_unit_id;

                $detail = ForecastConversionDetail::select('id', 'conversion_latest_rounding_unit_id')->where('product_ingredient_id', $value)->whereIn('forecast_conversion_id', $forecastIds)->first();
                $conversion_latest_rounding_unit_id = $detail?->conversion_latest_rounding_unit_id;

                $productIngredients = ProductIngredient::find($value);
                $unit = ProductRecipeUnit::where('id', $productIngredients?->product_recipe_unit_id)->first();
                $pembagi = 1;
                if ($unit) {
                    if ($unit->parent_id_4) {
                        $pembagi = $unit->parent_id_2_conversion * $unit->parent_id_3_conversion * $unit->parent_id_4_conversion;
                    } elseif ($unit->parent_id_3) {
                        $pembagi = $unit->parent_id_2_conversion * $unit->parent_id_3_conversion;
                    } elseif ($unit->parent_id_2) {
                        $pembagi = $unit->parent_id_2_conversion;
                    } else{
                        $pembagi = 1;
                    }
                }

                //kalau ini dirubah, harus diubah juga pas index
                $conversion_latest_rounding =  round(forecast_rounding($conversion_total / $pembagi), 2);
                $forecastBuffer = ForecastBuffer::where('product_ingredient_id', $value)->first();
                $buffer = 0;
                if ($forecastBuffer) {
                    $buffer = round($forecastBuffer->buffer / 100 * $conversion_latest_rounding);
                }

                ForecastConversionApprovalDetail::create([
                    'forecast_conversion_approval_id' => $model->id,
                    'product_ingredient_id' => $value,
                    'conversion' => $conversion,
                    'conversion_total' => $conversion_total,
                    'buffer' => $buffer,
                    'conversion_2' => $conversion_2,
                    'conversion_unit_id' => $conversion_unit_id,
                    'conversion_rounding' => $conversion_rounding,
                    'conversion_rounding_unit_id' => $conversion_rounding_unit_id,
                    'conversion_latest' => round($conversion_total / $pembagi, 2),
                    'conversion_latest_rounding' => $conversion_latest_rounding,
                    'conversion_latest_rounding_total' => $conversion_latest_rounding + $buffer,
                    'conversion_latest_rounding_unit_id' => $conversion_latest_rounding_unit_id,
                ]);
            }

            // $forecastIds = $this->model->where('month', $data['month'])->where('year', date('Y'))->get();
            // foreach ($forecastIds as $row) {
            //     $forecastConversionDetails = ForecastConversionDetail::where('forecast_conversion_id', $row->id)->get();
            //     foreach ($forecastConversionDetails as $forecastConversionDetail) {
            //         ForecastConversionApprovalDetailBranch::create([
            //             'forecast_conversion_approval_id' => $model->id,
            //             'branch_id' => $row->branch_id,
            //             'product_ingredient_id' => $forecastConversionDetail->product_ingredient_id,
            //             'qty_real' => $forecastConversionDetail->conversion_latest_rounding,
            //             'qty_total' => round($forecastConversionDetail->conversion_latest_rounding * 50 / 100),
            //             'buffer' => $forecastConversionDetails->buffer,
            //         ]);
            //     }
            // }

            dispatch(new GetUserApprovalNotificationToken($model->id, 'forecast_conversions', $model->pr_id));

            $key = 'fc_show_' . $data['month'];
            Cache::forget($key);

            return $this->response($model ? true : false);
        }

        return $this->response(false);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function regenerate(Request $request)
    {
        $data = $this->validate($request, [
            'month' => 'required|min:1|max:12',
        ]);

        $cek = ForecastConversion::select('id')->whereIn('status_generate', ['running', 'calculation'])->first();
        if ($cek) {
            return $this->response('Data masih dalam proses generate ulang. Kembali beberapa saat lagi', 422);
        }

        $cek = DB::table('jobs')->select('id')->where('queue', 'forecast_import')->first();
        $failedJob = DB::table('failed_jobs')->select('id')->where('queue', 'forecast_import')->first();
        if ($cek || $failedJob) {
            return $this->response('Ada proses import forecast. Kembali beberapa saat lagi', 422);
        }

        $datas = ForecastConversion::where('month', $data['month'])->where('year', date('Y'))->get();
        // $datas = ForecastConversion::where('month', $data['month'])->where('year', date('Y'))->where('branch_id', 2)->get();
        if ($datas->first()) {
            if ($datas->first()->status != 'new') {
                return $this->response('Data yang bisa digenerate ulang hanya bisa data yang belum disubmit', 422);
            }
        } else {
            $branches = Forecast::where('month', $data['month'])->where('year', date('Y'))->pluck('branch_id')->unique();
            foreach ($branches as $branch) {
                ForecastConversion::create([
                    'month' => $data['month'],
                    'year' => date('Y'),
                    'branch_id' => $branch,
                    'status_generate' => 'running',
                ]);
            }

            $datas = ForecastConversion::where('month', $data['month'])->where('year', date('Y'))->get();
        }

        $forecastConversionIds = $datas->pluck('id');
        ForecastConversionDetail::whereIn('forecast_conversion_id', $forecastConversionIds)->delete();
        ForecastConversionDetailLog::whereIn('forecast_conversion_id', $forecastConversionIds)->delete();

        $forecasts = Forecast::where('month', $data['month'])->where('year', date('Y'))->where('sale', '!=', 0)->get();
        $ProductRecipes = ProductRecipe::select('master_packaging_id', 'product_id', 'product_ingredient_id', 'product_recipe_unit_id', 'measure')
            ->whereIn('product_id', $forecasts->pluck('product_id')->unique())
            ->get();
        ForecastConversion::where('month', $data['month'])->where('year', date('Y'))->update([
            'status_generate' => 'running'
        ]);

        foreach ($datas as $value) {
            // $value->update([
            //     'status_generate' => 'running'
            // ]);

            //ini pake job
            $forecast = $forecasts->where('branch_id', $value->branch_id);
            dispatch(new ForecastConversion2([
                'forecast' => $forecast,
                'forecast_conversion_id' => $value->id,
                'product_recipes' => $ProductRecipes,
            ]));

            // foreach ($forecast as $row) {
            //     $recipes = $ProductRecipes->where('product_id', $row->product_id);
            //     if ($recipes) {
            //         $data = [
            //             'product_id' => $row->product_id,
            //             'sale' => $row->sale,
            //             'forecast_conversion_id' => $value->id,
            //             'recipes' => $recipes
            //         ];

            //         dispatch(new PurchasingForecastConversion($data));
            //     }
            // }
        }

        $key = 'fc_show_' . $data['month'];
        Cache::forget($key);

        $key = 'fc_show_detail_' . $data['month'];
        Cache::forget($key);

        return $this->response(true);
    }

    /**
     * Export
     *
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request, $month)
    {
        $branch_id = $request->branch_id;

        $fileName = 'forecast conversion-' . $month . '-' . rand(0, 1000) . '.csv';
        return Excel::download(new ReportingForecastConversion($month, $branch_id), $fileName);
    }
}
