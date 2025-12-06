<?php

namespace App\Http\Controllers\Api\V1\Purchasing;

use App\Exports\Purchasing\StockOpname as ReportingStockOpname;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Imports\StockOpnameImport as ImportsStockOpnameImport;
use App\Jobs\Purchasing\Po2;
use App\Jobs\Purchasing\StockOpnameStock;
use App\Models\Branch;
use App\Models\Inventory\ProductIngredientStock;
use App\Models\Inventory\ProductIngredientStockDailyLog;
use App\Models\Inventory\ProductRecipeUnit;
use App\Models\Purchasing\StockOpname;
use App\Models\ProductIngredient;
use App\Models\Purchasing\ForecastConversionApproval;
use App\Models\Purchasing\StockOpnameImport;
use App\Models\Purchasing\StockOpnameIngredient;
use App\Models\Purchasing\StockOpnameIngredientDetail;
use App\Services\Inventory\IngredientStockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class StockOpnameController extends Controller
{
    /**
     * The user repository instance.
     */
    protected $model;
    protected $ingredientStockService;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(StockOpname $model, IngredientStockService $ingredientStockService)
    {
        $this->middleware('permission:purchasing-stok-opname.lihat', [
            'only' => ['index', 'show']
        ]);
        $this->middleware('permission:purchasing-stok-opname.tambah', [
            'only' => ['store', 'listProductIngredient']
        ]);
        $this->middleware('permission:purchasing-stok-opname.ubah', [
            'only' => ['update']
        ]);
        $this->middleware('permission:purchasing-stok-opname.hapus', [
            'only' => ['destroy']
        ]);
        $this->model = $model;
        $this->ingredientStockService = $ingredientStockService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = $this->model->with(['createdBy:id,name', 'updatedBy:id,name'])->search($request)->sort($request);
        $data = $data->paginate($this->perPage($data));
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function listBranch(Request $request)
    {
        $data = Branch::select('id', 'name')->branch()->search($request);
        if (Auth::user()->branch_id != 1) {
            $data = $data->where('id', Auth::user()->branch_id);
        }
        $data = $data->orderBy('name')->get();
        return $this->response($data);
    }

     /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $branchId = $request->branch_id;
        $model = $this->model->find($id);
        if ($branchId) {
            $ingredients = StockOpnameIngredient::with([
                'branch:id,name',
                'stockOpnameIngredientDetail',
                'stockOpnameIngredientDetail.productRecipeUnit:id,name',
                'productIngredient'
            ])
            ->where('stock_opname_id', $id)
            ->where('branch_id', $branchId)
            ->get();

            $model->details = $ingredients;
        } else {
            $ingredients = StockOpnameIngredient::where('stock_opname_id', $id)->pluck('product_ingredient_id')->unique();
            $productIngredients = ProductIngredient::whereIn('id', $ingredients)->orderBy('name')->get();

            $details = [];
            foreach ($productIngredients as $value) {
                $units = StockOpnameIngredientDetail::where('stock_opname_id', $id)->where('product_ingredient_id', $value->id)->groupBy('product_recipe_unit_id')->get();
                $stock_opname_ingredient_detail = [];
                foreach ($units as $unit) {
                    $stock_real = StockOpnameIngredientDetail::select('stock_real')
                        ->where('stock_opname_id', $id)
                        ->where('product_ingredient_id', $value->id)
                        ->where('product_recipe_unit_id', $unit->product_recipe_unit_id)
                        ->sum('stock_real');
                    $stock_opname_ingredient_detail[] = [
                        'stock_real' => $stock_real,
                        'product_recipe_unit' => [
                            'name' => ProductRecipeUnit::select('name')->find($unit->product_recipe_unit_id)?->name
                        ],
                    ];
                }

                $details[] = [
                    'branch' => [
                        'name' => 'Semua Cabang'
                    ],
                    'stock_opname_ingredient_detail' => $stock_opname_ingredient_detail,
                    'product_ingredient' => [
                        'name' => $value->name
                    ]
                ];
            }

            $model->details = $details;
        }

        return $this->response($model);
    }

     /**
     * export
     *
     * @return Collection
     */
    public function export()
    {
        $branchId = Auth::user()->branch_id;

        $fileName = 'stok-opname.xlsx';
        return Excel::download(new ReportingStockOpname($branchId), $fileName, \Maatwebsite\Excel\Excel::XLSX);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    // public function store(Request $request)
    // {
    //     $data = $this->validate($request, [
    //         'week' => 'required|in:1,2,3,4',
    //         'branch_id' => 'required|exists:branches,id',
    //         'month' => 'required|in:0,1,2,3,4,5,6,7,8,9,10,11,12',
    //         'is_last_stock' => 'required|in:0,1',
    //         'ingredients' => 'required|array',
    //         'ingredients.*.product_ingredient_id' => 'nullable',
    //         'ingredients.*.stocks.*' => 'required|array',
    //         'ingredients.*.stocks.*.product_recipe_unit_id' => 'required',
    //         'ingredients.*.stocks.*.stock_system' => 'required',
    //         'ingredients.*.stocks.*.stock_real' => 'required',
    //         'ingredients.*.stocks.*.stock_difference' => 'required',
    //         'ingredients.*.stocks.*.note' => 'nullable',
    //     ], [
    //         //
    //     ], []);

    //     $month = date('m'); //validasi jika januari, boleh untuk desember
    //     if ($data['month'] == 0 && $month != 1) {
    //         return $this->response('Desember tahun lalu hanya bisa diisi pada maksimal bulan Januari tahun berjalan', 'error', 422);
    //     } else {
    //         if ($data['month'] > $month) {
    //             return $this->response('Hanya diperbolehkan mengisi bulan berjalan(' . month_indo(date('m')) . ') dan sebelumnya', 'error', 422);
    //         }
    //     }

    //     //validasi ada po supplier ga di bulan tsb
    //     $cek = ForecastConversionApproval::where('month', $data['month'])->where('year', date('Y'))->where('type', 'default')->whereIn('status', ['approved', 'setting-po'])->first();
    //     if (is_null($cek)) {
    //         return $this->response('Tidak ada PO di bulan ' . month_indo(date($data['month'])) . '. Pastikan sudah ada PO di bulan tersebut', 'error', 422);
    //     }

    //     //jika udah pernah sekali so, gabisa lagi
    //     if ($request->branch_id) {
    //         $branchId = $data['branch_id'];
    //     } else {
    //         $branchId = Auth::user()->branch_id;
    //     }

    //     // $cek = ForecastConversionApproval::where('month', $data['month'])->where('branch_id', $branchId)->where('year', date('Y'))->where('type', 'so')->whereIn('status', ['approved', 'setting-po'])->first();
    //     $cek = StockOpname::where('branch_id', $branchId)->where('month', $data['month'])->whereYear('created_at', date('Y'))->where('status', 'approved')->where('is_last_stock', 1)->first();
    //     if ($cek) {
    //         return $this->response('Sudah ada Stok Opname di bulan ' . month_indo(date($data['month'])), 'error', 422);
    //     }

    //     $data = DB::connection('mysql')->transaction(function () use ($data, $branchId) {
    //         $model = $this->model->create([
    //             'status' => 'new',
    //             'branch_id' => $branchId,
    //             'week' => $data['week'],
    //             'month' =>  $data['month'],
    //             'is_last_stock' =>  $data['is_last_stock'],
    //         ]);

    //         foreach ($data['ingredients'] as $value) {
    //             if ($value['product_ingredient_id']) {
    //                 $ingredient = StockOpnameIngredient::create([
    //                     'stock_opname_id' => $model->id,
    //                     'product_ingredient_id' => $value['product_ingredient_id'],
    //                 ]);

    //                 foreach ($value['stocks'] as $row) {
    //                     if ($row['product_recipe_unit_id']) {
    //                         StockOpnameIngredientDetail::create([
    //                             'stock_opname_ingredient_id' => $ingredient->id,
    //                             'stock_opname_id' => $model->id,
    //                             'product_ingredient_id' => $value['product_ingredient_id'],
    //                             'product_recipe_unit_id' => $row['product_recipe_unit_id'],
    //                             'stock_system' => $row['stock_system'],
    //                             'stock_real' => $row['stock_real'],
    //                             'stock_difference' => $row['stock_difference'],
    //                             'note' => isset($row['note']) ? $row['note'] : null,
    //                         ]);
    //                     }
    //                 }
    //             }
    //         }

    //         return $model;
    //     });

    //     return $this->response($data);
    // }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function import(Request $request)
    {
        $data = $this->validate($request, [
            'week' => 'required|in:1,2,3,4',
            'month' => 'required|in:0,1,2,3,4,5,6,7,8,9,10,11,12',
            'is_last_stock' => 'required|in:0,1',
            'file' => 'required|mimes:xlsx,xls|max:10000'
        ], []);

        // $month = date('m'); //validasi jika januari, boleh untuk desember
        // if ($data['month'] == 0 && $month != 1) {
        //     return $this->response('Desember tahun lalu hanya bisa diisi pada maksimal bulan Januari tahun berjalan', 'error', 422);
        // } else {
        //     if ($data['month'] > $month) {
        //         return $this->response('Hanya diperbolehkan mengisi bulan berjalan(' . month_indo(date('m')) . ') dan sebelumnya', 'error', 422);
        //     }
        // }

        //validasi ada po supplier ga di bulan tsb
        $month = $data['month'];
        $nextMonth = $month + 1;
        $next_year = false;
        if ($nextMonth == 13) {
            $nextMonth = 1;
            $next_year = true;
        }

        if ($next_year) {
            $nextYear = date('Y') + 1;
        } else {
            $nextYear = date('Y');
        }

        $cek = ForecastConversionApproval::where('month', $nextMonth)->where('year', $nextYear)->where('type', 'default')->whereIn('status', ['approved', 'setting-po'])->first();
        if (is_null($cek)) {
            return $this->response('Tidak ada PO di bulan ' . month_indo(date($nextMonth)) . '. Pastikan sudah ada PO di bulan tersebut', 'error', 422);
        }

        //jika udah pernah sekali so, gabisa lagi
        $cek = StockOpname::where('month', $data['month'])->whereYear('created_at', date('Y'))->where('status', 'approved')->where('is_last_stock', 1)->first();
        if ($cek) {
            return $this->response('Sudah ada Stok Opname di bulan ' . month_indo(date($data['month'])), 'error', 422);
        }

        try {
            StockOpnameImport::where('user_id', Auth::id())->delete();
            if ($data['month'] == 0) {
                $data['month'] = 12;
                $data['year'] = date('Y') - 1;
            } else {
                $data['month'] = $data['month'];
                $data['year'] = date('Y');
            }

            Excel::import(new ImportsStockOpnameImport($data), $request->file);

            return $this->response('Silahkan Preview data sebelum submit');
        } catch (\Throwable $th) {
            return $this->response('Terjadi kesalahan. Silahkan import kembali dan pastikan file sesuai format', 422);
        }

        return $this->response(true);
    }

     /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function importPreview(Request $request)
    {
        $job = DB::table('jobs')->select('id')->where('queue', 'so_import')->first();
        if ($job) {
            return response()->json([
                'status' => 'success',
                'message' => 'Data dalam proses validasi. Silahkan cek secara berkala dengan menekan tombol Preview Data.',
                'data' => null,
            ]);
        }

        $data = StockOpnameImport::orderBy('is_valid')->search($request)->sort($request);
        if ($branch_id = $request->branch_id) {
            $data = $data->where('branch_id', $branch_id);
        }

        if ($request->is_valid || $request->is_valid == '0') {
            $data = $data->where('is_valid', $request->is_valid);
        }
        $data = $data->where('user_id', Auth::id())->orderBy('branch_name')->paginate($this->perPage($data));

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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function importSubmit(Request $request)
    {
        $stockOpnameImport = StockOpnameImport::where('user_id', Auth::id())->where('is_valid', 1)->get();
        $data = [
            'month' => $stockOpnameImport->first()->month,
            'year' => $stockOpnameImport->first()->year,
        ];

        // $month = date('m'); //validasi jika januari, boleh untuk desember
        // if ($data['month'] == 0 && $month != 1) {
        //     return $this->response('Desember tahun lalu hanya bisa diisi pada maksimal bulan Januari tahun berjalan', 'error', 422);
        // } else {
        //     if ($data['month'] > $month) {
        //         return $this->response('Hanya diperbolehkan mengisi bulan berjalan(' . month_indo(date('m')) . ') dan sebelumnya', 'error', 422);
        //     }
        // }

        //validasi ada po supplier ga di bulan tsb

        $month = $data['month'];
        $nextMonth = $month + 1;
        $next_year = false;
        if ($nextMonth == 13) {
            $nextMonth = 1;
            $next_year = true;
        }

        if ($next_year) {
            $nextYear = date('Y') + 1;
        } else {
            $nextYear = date('Y');
        }

        $cek = ForecastConversionApproval::where('month', $nextMonth)->where('year', $nextYear)->where('type', 'default')->whereIn('status', ['approved', 'setting-po'])->first();
        if (is_null($cek)) {
            return $this->response('Tidak ada PO di bulan ' . month_indo(date($nextMonth)) . '. Pastikan sudah ada PO di bulan tersebut', 'error', 422);
        }

        //jika udah pernah sekali so, gabisa lagi
        $cek = StockOpname::where('month', $data['month'])->where('year', $data['year'])->where('status', 'approved')->where('is_last_stock', 1)->first();
        if ($cek) {
            return $this->response('Sudah ada Stok Opname di bulan ' . month_indo(date($data['month'])), 'error', 422);
        }

        $model = $this->model->create([
            'status' => 'new',
            'week' => $stockOpnameImport->first()->week,
            'month' =>  $stockOpnameImport->first()->month,
            'year' =>  $stockOpnameImport->first()->year,
            'is_last_stock' =>  $stockOpnameImport->first()->is_last_stock,
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($model, $stockOpnameImport) {
            foreach ($stockOpnameImport as $value) {
                $ingredient = StockOpnameIngredient::create([
                    'branch_id' => $value->branch_id,
                    'stock_opname_id' => $model->id,
                    'product_ingredient_id' => $value->product_ingredient_id,
                ]);

                if ($value->product_recipe_unit_id_1) {
                    StockOpnameIngredientDetail::create([
                        'branch_id' => $value->branch_id,
                        'stock_opname_ingredient_id' => $ingredient->id,
                        'stock_opname_id' => $model->id,
                        'product_ingredient_id' => $value->product_ingredient_id,
                        'product_recipe_unit_id' => $value->product_recipe_unit_id_1,
                        'stock_real' => $value->stock_1,
                        'note' => null,
                    ]);
                }

                if ($value->product_recipe_unit_id_2) {
                    StockOpnameIngredientDetail::create([
                        'branch_id' => $value->branch_id,
                        'stock_opname_ingredient_id' => $ingredient->id,
                        'stock_opname_id' => $model->id,
                        'product_ingredient_id' => $value->product_ingredient_id,
                        'product_recipe_unit_id' => $value->product_recipe_unit_id_2,
                        'stock_real' => $value->stock_2,
                        'note' => null,
                    ]);
                }

                if ($value->product_recipe_unit_id_3) {
                    StockOpnameIngredientDetail::create([
                        'branch_id' => $value->branch_id,
                        'stock_opname_ingredient_id' => $ingredient->id,
                        'stock_opname_id' => $model->id,
                        'product_ingredient_id' => $value->product_ingredient_id,
                        'product_recipe_unit_id' => $value->product_recipe_unit_id_3,
                        'stock_real' => $value->stock_3,
                        'note' => null,
                    ]);
                }
            }

            return $model;
        });

        if ($data) {
            StockOpnameImport::where('user_id', Auth::id())->delete();
        }

        return $this->response($data ? true : false);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = $this->validate($request, [
            'status' => 'required|in:approved,rejected,new',
        ]);
        $status = $data['status'];

        // $model = $this->model->with(['stockOpnameIngredient', 'stockOpnameIngredient.stockOpnameIngredientDetail'])->findOrFail($id);
        $model = $this->model->findOrFail($id);

        //jika udah pernah sekali so, gabisa lagi
        $cek = StockOpname::where('month', $model->month)->where('year', $model->year)->where('status', 'approved')->where('is_last_stock', 1)->first();
        if ($cek) {
            return $this->response('Sudah ada Stok Opname di bulan ' . month_indo(date($model->month)), 'error', 422);
        }

        $next_year = $model->year;
        $nextMonth = $model->month + 1;
        if ($nextMonth == 13) {
            $nextMonth = 1;
            $nextYear = $model->year + 1;
        }
        // $nextYear = $model->year + 1;

        //validasi harus cek ke setting po ada ga next month
        $cek = ForecastConversionApproval::where('month', $nextMonth)->where('year', $nextYear)->where('type', 'default')->whereIn('status', ['approved', 'setting-po'])->first();
        if (is_null($cek)) {
            return $this->response('Setting PO tidak ditemukan pada bulan ' . month_indo(date($nextMonth)), 'error', 422);
        }

        $data = DB::connection('mysql')->transaction(function () use ($data, $model, $id) {
            $model->update($data);

            dispatch(new StockOpnameStock([
                'id' => $model->id,
                'status' => $data['status']
            ]));
        });

        if ($status == 'approved' && $model->is_last_stock) {
            dispatch(new Po2([
                'id' => $id,
                'submitted_by' => Auth::id(),
            ]));
        }

        return $this->response($model ? true : false);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $data = $this->validate($request, [
            'id' => 'required|array',
            'id.*' => 'required'
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            return $this->model->whereIn('id', $data['id'])->where('status', 'new')->delete();
        });

        return $this->response($data ? true : false);
    }
}
