<?php

namespace App\Http\Controllers\Api\V1\Distribution;

use App\Exports\Distribution\PoManual as DistributionPoManual;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Imports\PoManualImport;
use App\Models\Distribution\PoManual;
use App\Models\Distribution\PoManualDetail;
use App\Models\Distribution\PoManualImport as DistributionPoManualImport;
use App\Models\Distribution\PoManualNote;
use App\Models\Distribution\PoManualStatusLog;
use App\Models\Distribution\PoSj;
use App\Models\Inventory\ProductIngredientBrand;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Services\Management\BranchService;
use App\Services\Management\ShippingService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Maatwebsite\Excel\Facades\Excel;

class PoManualController extends Controller
{
    /**
     * The user repository instance.
     */
    protected $model;

    protected $branchService;

    protected $shippingService;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(PoManual $model, BranchService $branchService, ShippingService $shippingService)
    {
        $this->middleware('permission:po-manual.lihat1|po-manual.show|list-po-manual.lihat', [
            'only' => ['index', 'show']
        ]);
        $this->middleware('permission:po-manual.tambah', [
            'only' => ['store']
        ]);
        $this->middleware('permission:po-manual.ubah|list-po-manual.ubah', [
            'only' => ['update', 'centralApproval']
        ]);
        $this->middleware('permission:po-manual.hapus', [
            'only' => ['destroy']
        ]);
        $this->middleware('permission:po-manual.tambah|po-manual.ubah', [
            'only' => ['listBahan']
        ]);
        $this->model = $model;
        $this->branchService = $branchService;
        $this->shippingService = $shippingService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $from = $request->from;
        $status = $request->status;

        $data = $this->model->with(['createdBy:id,name', 'branch:id,name'])
            ->branch()
            ->search($request, false, ['branch']);

        if (!$from) {
            if ($startDate && $endDate) {
                $data = $data->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate);
            }
        }

        if ($request->sort) {
            $data = $data->sort($request);
        } else {
            $data = $data->orderByDesc('created_at');
        }

        if ($status_shipping = $request->status_shipping) {
            $data = $data->where('status_shipping', $status_shipping);
        }

        if ($branchId = $request->branch_id) {
            $data = $data->where('branch_id', $branchId);
        }

        if ($from) {
            $data = $data->where('status', 'po-accepted');
        }

        if ($status) {
            $data = $data->where('status', $status);
        }

        $data = $data->paginate($this->perPage($data));
        return $this->response($data);
    }

    /**
     * Export
     *
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $fileName = 'Po Manual-' . date('Y-m-d') . '-' . rand(0, 1000) . '.csv';
        return Excel::download(new DistributionPoManual($startDate, $endDate), $fileName);
    }

    /**
     * Import
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function import(Request $request)
    {
        $data = $this->validate($request, [
            'branch_id' => 'nullable',
            'is_urgent' => 'nullable',
            'type' => 'required|in:product,ingredient',
            'file' => 'required|mimes:xlsx,xls|max:10000',
        ]);

        try {
            DistributionPoManualImport::where('created_by', Auth::id())->delete();
            Excel::import(new PoManualImport($data['type'], Auth::id()), $request->file);

            $datas = DistributionPoManualImport::where('created_by', Auth::id())->get();
            $model = PoManual::create([
                'branch_id' => $data['branch_id'],
                'type' => $data['type'],
                'status' => 'pending',
                'status_shipping' => 'today',
                'is_urgent' => $data['is_urgent'],
            ]);

            foreach ($datas as $value) {
                if ($value->type == 'product') {
                    $product = Product::where('code', $value->product_code)->first();
                    if ($product) {
                        $model->details()->create([
                            'product_id' => $product->id,
                            'qty' => $value->qty,
                        ]);
                    }
                } else {
                    $barcode = ProductIngredientBrand::where('barcode', $value->product_code)->first();
                    if ($barcode) {
                        $product = ProductIngredient::find($barcode->product_ingredient_id);
                        if ($product) {

                            $model->details()->create([
                                'product_ingredient_id' => $product->id,
                                'product_ingredient_unit_id' => $barcode->product_recipe_unit_id,
                                'qty' => $value->qty,
                            ]);
                        }
                    }
                }
            }

            return $this->response('Berhasil Import Data');
        } catch (\Throwable $th) {
            return $this->response('Terjadi kesalahan. Silahkan import kembali dan pastikan file sesuai format', 422);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listBahan(Request $request)
    {
        $poId = $request->po_id;
        $barcode = $request->barcode;
        if ($poId) {
            $key = "po-ingredient-manual-" . $poId;
            // if (!Cache::has($key)) {
            $product_ingredient_id = PoManualDetail::select('product_ingredient_id')->where('po_manual_id', $poId)->pluck('product_ingredient_id');

            $data = ProductIngredient::select('id', 'name', 'product_recipe_unit_id', 'barcode', 'product_ingredient_unit_delivery_id')
                // ->with(['unit:id,name', 'unitDelivery:id,name'])
                ->whereIn('id', $product_ingredient_id)
                ->orderBy('name')
                ->get();

            foreach ($data as $value) {
                $value->barcode = barcode_unit_2($value->id, $value->product_ingredient_unit_delivery_id);
                $value->code = barcode_unit_2($value->id, $value->product_ingredient_unit_delivery_id);
                $value->unit_delivery = unit_2($value->product_recipe_unit_id);
            }

            // Cache::forever($key, $data);
            if ($barcode) {
                $data = $data->where('barcode', $barcode);
            }
            $data = $data->values();
            // } else {
            //     $data = Cache::get($key);
            //     if (!is_null($barcode)) {
            //         $data = $data->where('barcode', $barcode);
            //     }
            //     $data = $data->values();
            // }
        } else {
            $data = ProductIngredient::select('id', 'code', 'name', 'product_recipe_unit_id', 'barcode', 'product_ingredient_unit_delivery_id');
            // ->with(['unit:id,name', 'unitDelivery:id,name']);

            if ($barcode) {
                $data = $data->where('barcode', $barcode);
            }

            $data = $data->orderBy('name')->get();
            foreach ($data as $value) {
                $value->unit_delivery = unit_2($value->product_recipe_unit_id);
            }
        }

        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     */
    public function listStatus()
    {
        $data = [
            [
                'name' => 'Pending',
                'value' => 'pending',
                'is_selected' => true
            ],
            [
                'name' => 'PO Dicetak',
                'value' => 'print-po',
                'is_selected' => false
            ],
            [
                'name' => 'PO Diterima',
                'value' => 'po-accepted',
                'is_selected' => false
            ],
            [
                'name' => 'PO Ditolak',
                'value' => 'po-rejected',
                'is_selected' => false
            ],
            [
                'name' => 'Siap Dikirim',
                'value' => 'product_accepted',
                'is_selected' => false
            ],
            [
                'name' => 'Telah cetak surat jalan',
                'value' => 'print',
                'is_selected' => false
            ],
            [
                'name' => 'Total Produk Disesuaikan',
                'value' => 'product_incomplete',
                'is_selected' => false
            ],
            [
                'name' => 'Selesai',
                'value' => 'done',
                'is_selected' => false
            ],
            [
                'name' => 'Ditolak Selisih SJ',
                'value' => 'rejected',
                'is_selected' => false
            ],
        ];

        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listBranch()
    {
        return $this->response($this->branchService->getAll());
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listAllProduct()
    {
        $product = Product::select('id', 'name')->get();
        $ingredient = ProductIngredient::select('id', 'name')->get();
        $poBrownies = PoManual::select('id')->with('details')->get();
        $datas = [];
        foreach ($poBrownies as $value) {
            if ($value->type == 'product') {
                $products = $product->whereIn('id', $value->details->pluck('product_id'))->values();
            } else {
                $products = $ingredient->whereIn('id', $value->details->pluck('product_ingredient_id'))->values();
            }
            $datas[] = [
                'id' => $value->id,
                'products' => $products,
            ];
        }

        return $datas;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listShipping(Request $request)
    {
        return $this->response($this->shippingService->getAll());
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listProduct(Request $request)
    {
        $poId = $request->po_id;
        $product_category_id = $request->product_category_id;
        $barcode = $request->barcode;

        if ($product_category_id) {
            $data = Product::select('id', 'code', 'name', 'barcode')->where('product_category_id', $product_category_id);
        } else {
            $data = Product::select('id', 'code', 'name', 'barcode');
        }
        $data = $data->with(['stocks']);

        if ($barcode) {
            $data = $data->where('barcode', $barcode);
        }

        if ($poId) {
            $productIds = PoManualDetail::select('product_id')->where('po_manual_id', $poId)->pluck('product_id');
            $data = $data->whereIn('id', $productIds);
        }

        $data = $data->available()->search($request)->orderBy('name')->get();
        return $this->response($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $branchId = Auth::user()->branch_id;
        $validate = [
            'type' => 'required|in:product,ingredient',
            'is_urgent' => 'required|boolean',
            'products' => 'required|array',
            'products.*.product_ingredient_id' => 'required_if:type,ingredient|exists:product_ingredients,id',
            'products.*.product_ingredient_unit_id' => 'required_if:type,ingredient|exists:product_recipe_units,id',
            'products.*.product_id' => 'required_if:type,product|exists:products,id',
            'products.*.qty' => 'required|integer',
        ];

        if ($branchId == 1) {
            $validate['branch_id'] = 'required|exists:branches,id';
        }

        $data = $this->validate($request, $validate);

        if (strtotime(date('Y-m-d H:i:s')) < strtotime(date('Y-m-d ') . '14:00:00')) {
            $data['status_shipping'] = 'today';
        } else {
            if ($data['is_urgent'] == 1) {
                $data['status_shipping'] = 'today';
            } else {
                $data['status_shipping'] = 'tomorrow';
            }
        }
        $data['status'] = 'pending';

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            $model = $this->model->create($data);
            if ($data['type'] == 'product') {
                foreach ($data['products'] as $value) {
                    $model->details()->create(Arr::only($value, ['product_id', 'qty']));
                }
            } else {
                foreach ($data['products'] as $value) {
                    $model->details()->create(Arr::except($value, ['product_id']));
                }
            }

            $model->statusLogs()->create(['status' => 'new']);
            $model->statusLogs()->create(['status' => 'pending']);

            return $model;
        });

        return $this->response($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function centralApproval(Request $request, $id)
    {
        $data = $this->validate($request, [
            'status' => 'required|in:po-accepted,po-rejected',
            'note' => 'required_if:status,po-rejected'
        ]);

        $model = $this->model->findOrFail($id);

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            $model->update($data);
            $model->statusLogs()->updateOrCreate(
                [
                    'status' => $data['status']
                ],
                [
                    'status' => $data['status'],
                    'note' => isset($data['note']) && !empty($data['note']) ? $data['note'] : null
                ],
            );

            return $model;
        });

        return $this->response($data);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $model = $this->model->with(['branch:id,name', 'details', 'statusLogs', 'createdBy:id,name', 'details.ingredient:id,name,code', 'details.unit:id,name', 'details.product:id,name,code,product_unit_id,product_unit_delivery_id,unit_value', 'details.product.unit:id,name', 'details.product.unitDelivery:id,name', 'branch:id,name'])->branch()->findOrFail($id);
        $model->date_packaging = null;
        foreach ($model->statusLogs as $value) {
            if ($value->status == 'product_accepted') {
                $model->date_packaging = tanggal_indo($value->created_at, false, false);
            }
        }

        foreach ($model->details as $value) {
            if ($value->product) {
                $unit = $value->product->unit ? $value->product->unit->name : null;
                $value->product->total_unit = $value->qty . ' ' . $unit;
                $unit_delivery = $value->product->unitDelivery ? $value->product->unitDelivery->name : null;
                $value->product->total_unit_delivery = Product::getTotalUnitDelivery($value->qty, $value->product->unit_value) . ' ' . $unit_delivery;
            }

            if ($value->ingredient) {
                $unit = $value->unit ? $value->unit->name : null;

                $productIngredient = ProductIngredient::with(['unit', 'unit.parentId2'])->find($value->product_ingredient_id);
                $unitIngredient = $productIngredient?->unit?->parentId2?->name;
                $unitValue = $productIngredient ? $productIngredient->unit_value : 0;
                $value->ingredient->total_unit = $value->qty . ' ' . $unitIngredient;
                $value->ingredient->total_unit_delivery = $value->qty . ' ' . $unit;

                $barcode = ProductIngredientBrand::where('product_ingredient_id', $value->product_ingredient_id)
                    ->where('product_recipe_unit_id', $value->product_ingredient_unit_id)
                    ->first();
                $value->barcode = $barcode?->barcode;

                $value->unit_delivery_name = $unit;
            }
        }

        return $this->response($model);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storePackaging(Request $request, $id)
    {
        $data = $this->validate($request, [
            'boxs' => 'required|array',
            'boxs.*.name' => 'required|string',
            'boxs.*.barcode' => 'required|string',
            'boxs.*.products' => 'required|array',
            'boxs.*.products.*.product_ingredient_id' => 'nullable|exists:product_ingredients,id',
            'boxs.*.products.*.product_id' => 'nullable|exists:products,id',
            'boxs.*.products.*.qty' => 'required|integer|min:1',
            'note' => 'nullable|array',
            'note.*.product_id' => 'nullable|exists:products,id',
            'note.*.product_ingredient_id' => 'nullable|exists:product_ingredients,id',
            'note.*.note' => 'required|string',
            'shipping_id' => 'required|exists:shippings,id'
        ], [
            'boxs.*.products.*.product_ingredient_id.exists' => 'Ada bahan yang tidak valid',
            'boxs.*.products.*.product_id.exists' => 'Ada Item yang tidak valid',
        ]);

        $model = $this->model->findOrFail($id);
        if ($model->type == 'product') {
            $products = PoManualDetail::select('product_id')->where('po_manual_id', $id)->pluck('product_id')->toArray();
            $productReq = [];
            foreach ($data['boxs'] as $key => $value) {
                foreach ($value['products'] as $row) {
                    $productReq[] = $row['product_id'];
                }
            }

            $diff = array_diff($products, $productReq);
            if (count($diff) > 0) {
                if (!isset($data['note'])) {
                    $products = Product::whereIn('id', $diff)->get();
                    $datas = [];
                    foreach ($products as $value) {
                        $datas[] = [
                            'product_id' => $value->id,
                            'message' => 'Barang ' . $value->name . ' tidak diinput. Silahkan tambahkan keterangan'
                        ];
                    }

                    return response()->json([
                        'status' => 'error',
                        'data' => $datas,
                        'message' => 'Ada Barang tidak diinput. Silahkan tambahkan keterangan'
                    ], 406);
                }
            }
        } else {
            $products = PoManualDetail::select('product_ingredient_id')->where('po_manual_id', $id)->pluck('product_ingredient_id')->toArray();
            $productReq = [];
            foreach ($data['boxs'] as $key => $value) {
                foreach ($value['products'] as $row) {
                    $productReq[] = $row['product_ingredient_id'];
                }
            }

            $diff = array_diff($products, $productReq);
            if (count($diff) > 0) {
                if (!isset($data['note'])) {
                    $products = ProductIngredient::whereIn('id', $diff)->get();
                    $datas = [];
                    foreach ($products as $value) {
                        $datas[] = [
                            'product_ingredient_id' => $value->id,
                            'message' => 'Barang ' . $value->name . ' tidak diinput. Silahkan tambahkan keterangan'
                        ];
                    }

                    return response()->json([
                        'status' => 'error',
                        'data' => $datas,
                        'message' => 'Ada Barang tidak diinput. Silahkan tambahkan keterangan'
                    ], 406);
                }
            }
        }

        $data['status'] = 'product_accepted';

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            $model->update($data);
            $model->packagings()->delete();

            $sj = PoSj::firstOrCreate(
                [
                    'shipping_id' => $data['shipping_id'],
                    'delivery_date' => date('Y-m-d')
                ],
                [
                    'shipping_id' => $data['shipping_id'],
                    'delivery_date' => date('Y-m-d')
                ]
            );
            foreach ($data['boxs'] as $value) {
                foreach ($value['products'] as $key => $item) {
                    if ($model->type == 'product') {
                        $value['products'][$key]['product_ingredient_id'] = null;
                    } else {
                        $value['products'][$key]['product_id'] = null;
                    }
                }

                $packaging = $model->packagings()->create($value);
                if ($model->type == 'product') {
                    $packaging->products()->attach($value['products']);
                } else {
                    $packaging->ingredients()->attach($value['products']);
                }

                foreach ($value['products'] as $row) {
                    if ($model->type == 'product') {
                        $product = Product::select('code', 'name', 'product_unit_id', 'id', 'product_unit_delivery_id', 'unit_value', 'price_sale')->with(['unit', 'unitDelivery'])->where('id', $row['product_id'])->first();
                        $sj->items()->firstOrCreate(
                            [
                                'po_id' => $model->id,
                                'type' => 'po_manual_product',
                                'branch_id' => $model->branch_id,
                                'box_name' => $value['name'],
                                'product_id' => $product ? $product->id : ''
                            ],
                            [
                                'po_id' => $model->id,
                                'po_date' => date('Y-m-d', strtotime($model->created_at)),
                                'type' => 'po_manual_product',
                                'branch_id' => $model->branch_id,
                                'box_name' => $value['name'],
                                'product_id' => $product ? $product->id : '',
                                'code_item' => $product ? $product->code : '',
                                'name_item' => $product ? $product->name : '',
                                'qty' => Product::getTotalUnit($row['qty'], $product->unit_value),
                                'hpp' =>  $product ? $product->price_sale : null,
                                'unit_name' => $product ? $product->unit ? $product->unit->name : null : null,
                                'qty_delivery' => $row['qty'],
                                'unit_name_delivery' => $product ? $product->unitDelivery ? $product->unitDelivery->name : null : null,
                            ]
                        );
                    } else {
                        $product = ProductIngredient::select('code', 'name', 'product_recipe_unit_id', 'id', 'product_ingredient_unit_delivery_id', 'unit_value', 'hpp')->with(['unit', 'unitDelivery'])->where('id', $row['product_ingredient_id'])->first();
                        $sj->items()->firstOrCreate(
                            [
                                'po_id' => $model->id,
                                'type' => 'po_manual_ingredient',
                                'branch_id' => $model->branch_id,
                                'box_name' => $value['name'],
                                'product_ingredient_id' => $product ? $product->id : ''
                            ],
                            [
                                'po_id' => $model->id,
                                'po_date' => date('Y-m-d', strtotime($model->created_at)),
                                'type' => 'po_manual_ingredient',
                                'branch_id' => $model->branch_id,
                                'box_name' => $value['name'],
                                'product_ingredient_id' => $product ? $product->id : '',
                                'code_item' => $product ? $product->code : '',
                                'name_item' => $product ? $product->name : '',
                                'qty' => ProductIngredient::getTotalUnit($row['qty'], $product->unit_value),
                                'hpp' =>  $product ? $product->hpp : null,
                                'unit_name' => $product ? $product->unit ? $product->unit->name : null : null,
                                'qty_delivery' => $row['qty'],
                                'unit_name_delivery' => $product ? $product->unitDelivery ? $product->unitDelivery->name : null : null,
                            ]
                        );
                    }
                }
            }

            PoManualStatusLog::updateOrCreate(
                [
                    'po_manual_id' => $model->id,
                    'status' => 'processed',
                ],
                [
                    'po_manual_id' => $model->id,
                    'status' => 'processed',
                ]
            );

            if (isset($data['note'])) {
                foreach ($data['note'] as $value) {
                    if (isset($value['product_id'])) {
                        PoManualNote::updateOrCreate(
                            [
                                'po_manual_id' => $model->id,
                                'product_id' => $value['product_id'],
                            ],
                            [
                                'po_manual_id' => $model->id,
                                'product_id' => $value['product_id'],
                                'note' => $value['note'],
                            ]
                        );
                    } else {
                        PoManualNote::updateOrCreate(
                            [
                                'po_manual_id' => $model->id,
                                'product_ingredient_id' => $value['product_ingredient_id'],
                            ],
                            [
                                'po_manual_id' => $model->id,
                                'product_ingredient_id' => $value['product_ingredient_id'],
                                'note' => $value['note'],
                            ]
                        );
                    }
                }
                PoManualStatusLog::updateOrCreate(
                    [
                        'po_manual_id' => $model->id,
                        'status' => 'product_rejected',
                    ],
                    [
                        'po_manual_id' => $model->id,
                        'status' => 'product_rejected',
                    ]
                );
            }

            PoManualStatusLog::updateOrCreate(
                [
                    'po_manual_id' => $model->id,
                    'status' => 'product_accepted',
                ],
                [
                    'po_manual_id' => $model->id,
                    'status' => 'product_accepted',
                ]
            );

            return $model;
        });

        $key = "po-" . $model->type . "-manual-" . $id;
        Cache::forget($key);

        return $this->response($data);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showPackaging($id)
    {
        $model = $this->model->with(['packagings', 'packagings.ingredients:id,name,code', 'packagings.products:id,name,code'])->findOrFail($id);
        return $this->response($model);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function storePrint(Request $request, $id)
    {
        $data = $this->validate($request, [
            'shipping_id' => 'required|exists:shippings,id'
        ]);

        $data['status'] = 'print';

        $model = $this->model->findOrFail($id);

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            $model->update($data);
            $model->statusLogs()->updateOrCreate(
                [
                    'status' => $data['status']
                ],
                [
                    'status' => $data['status']
                ],
            );

            return $model;
        });

        return $this->response($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function print($id)
    {
        $data = $this->model->with(['shipping', 'shipping.tracks:id,name'])->findOrFail($id);
        return $this->response($data);
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
            'type' => 'required|in:product,ingredient',
            'is_urgent' => 'required|boolean',
            'products' => 'required|array',
            'products.*.product_ingredient_id' => 'required_if:type,ingredient|exists:product_ingredients,id',
            'products.*.product_ingredient_unit_id' => 'required_if:type,ingredient|exists:product_recipe_units,id',
            'products.*.product_id' => 'required_if:type,product|exists:products,id',
            'products.*.qty' => 'required|integer',
        ]);

        if (strtotime(date('Y-m-d H:i:s')) < strtotime(date('Y-m-d ') . '14:00:00')) {
            $data['status_shipping'] = 'today';
        } else {
            if ($data['is_urgent'] == 1) {
                $data['status_shipping'] = 'today';
            } else {
                $data['status_shipping'] = 'tomorrow';
            }
        }

        foreach ($data['products'] as $key => $value) {
            if ($data['type'] == 'product') {
                $data['products'][$key]['product_ingredient_id'] = null;
                $data['products'][$key]['product_ingredient_unit_id'] = null;
            } else {
                $data['products'][$key]['product_id'] = null;
            }
        }

        $model = $this->model->findOrFail($id);

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            $model->update($data);
            foreach ($data['products'] as $value) {
                $model->details()->where('id', $value['po_manual_detail_id'])->update(Arr::except($value, ['po_manual_detail_id']));
            }

            return $model;
        });

        return $this->response($model);
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
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            return $this->model->select('*')->whereIn('id', $data['id'])->branch()->delete();
        });

        return $this->response($data ? true : false);
    }
}
