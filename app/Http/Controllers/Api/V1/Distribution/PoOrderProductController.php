<?php

namespace App\Http\Controllers\Api\V1\Distribution;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Distribution\PoOrderProduct;
use App\Models\Distribution\PoOrderProductDetail;
use App\Models\Distribution\PoOrderProductNote;
use App\Models\Distribution\PoOrderProductStatusLog;
use App\Models\Distribution\PoSj;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Production\BrowniesTargetPlanWarehouse;
use App\Services\Management\BranchService;
use App\Services\Management\ShippingService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PoOrderProductController extends Controller
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
    public function __construct(PoOrderProduct $model, BranchService $branchService, ShippingService $shippingService)
    {
        $this->middleware('permission:po-pesanan-produk.lihat|po-pesanan-produk.show|list-po-pesanan-produk.lihat', [
            'only' => ['index', 'show']
        ]);
        $this->middleware('permission:po-pesanan-produk.tambah', [
            'only' => ['store']
        ]);
        $this->middleware('permission:po-pesanan-produk.ubah', [
            'only' => ['update']
        ]);
        $this->middleware('permission:po-pesanan-produk.hapus', [
            'only' => ['destroy']
        ]);
        $this->middleware('permission:po-pesanan-produk.tambah|po-pesanan-produk.ubah', [
            'only' => ['listProduct', 'listCategory']
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
        $status = $request->status;

        $data = $this->model->with(['createdBy:id,name', 'branch:id,name'])
            ->order()
            ->branch()
            ->available()
            ->search($request);

        if ($startDate && $endDate) {
            $data = $data->whereDate('available_at', '>=', $startDate)->whereDate('available_at', '<=', $endDate);
        }

        if ($branchId = $request->branch_id) {
            $data = $data->where('branch_id', $branchId);
        }

        if ($request->sort) {
            $data = $data->sort($request);
        } else {
            $data = $data->orderByDesc('available_at');
        }

        if ($status) {
            $data = $data->where('status', $status);
        }

        $data = $data->paginate($this->perPage($data));
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
                'name' => 'Baru',
                'value' => 'new',
                'is_selected' => true
            ],
            [
                'name' => 'Pending',
                'value' => 'pending',
                'is_selected' => false
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
    public function listBranch(Request $request)
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
        $poBrownies = PoOrderProduct::select('id')->with('details')->order()->get();
        $datas = [];
        foreach ($poBrownies as $value) {
            $datas[] = [
                'id' => $value->id,
                'products' => $product->whereIn('id', $value->details->pluck('product_id'))->values(),
            ];
        }

        return $datas;
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

        if ($barcode) {
            $data = $data->where('barcode', $barcode);
        }

        if ($poId) {
            $productIds = PoOrderProductDetail::select('product_id')->where('po_order_product_id', $poId)->pluck('product_id');
            $data = $data->whereIn('id', $productIds);
        }

        $data = $data->available()->search($request)->orderBy('name')->get();
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listCategory(Request $request)
    {
        $data = ProductCategory::select('id', 'name')->search($request)->orderBy('name')->get();
        return $this->response($data);
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $this->validate($request, [
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.qty' => 'required|integer',
        ]);
        $data['available_at'] = date('Y-m-d');

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            $model = $this->model->where('branch_id', Auth::user()->branch_id)->where('available_at', $data['available_at'])->order()->first();
            if (!$model) {
                $model = $this->model->create($data);
                $model->statusLogs()->create(['status' => 'new']);
            }

            foreach ($data['products'] as $value) {
                $model->details()->create($value);
            }

            return $model;
        });

        return $this->response($data);
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
            'boxs.*.products.*.product_id' => 'required|exists:products,id',
            'boxs.*.products.*.qty' => 'required|integer|min:1',
            'note' => 'nullable|array',
            'note.*.product_id' => 'required|exists:products,id',
            'note.*.note' => 'required|string',
            'shipping_id' => 'required|exists:shippings,id'
        ]);

        $products = PoOrderProductDetail::select('product_id')->where('po_order_product_id', $id)->pluck('product_id')->toArray();
        $productReq = [];
        foreach ($data['boxs'] as $value) {
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

        $data['status'] = 'product_accepted';
        $model = $this->model->findOrFail($id);

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
                $packaging = $model->packagings()->create($value);
                $packaging->products()->attach($value['products']);

                foreach ($value['products'] as $row) {
                    $product = Product::select('code', 'name', 'product_unit_id', 'id', 'product_unit_delivery_id', 'unit_value', 'price_sale')->with(['unit', 'unitDelivery'])->where('id', $row['product_id'])->first();
                    $sj->items()->firstOrCreate(
                        [
                            'po_id' => $model->id,
                            'type' => 'po_order_product',
                            'branch_id' => $model->branch_id,
                            'box_name' => $value['name'],
                            'product_id' => $product ? $product->id : ''
                        ],
                        [
                            'po_id' => $model->id,
                            'po_date' => date('Y-m-d', strtotime($model->created_at)),
                            'type' => 'po_order_product',
                            'branch_id' => $model->branch_id,
                            'box_name' => $value['name'],
                            'product_id' => $product ? $product->id : '',
                            'code_item' => $product ? $product->code : '',
                            'name_item' => $product ? $product->name : '',
                            'qty' => Product::getTotalUnitDelivery($row['qty'], $product->unit_value),
                            'hpp' => $product ? $product->price_sale : null,
                            'unit_name' => $product ? $product->unit ? $product->unit->name : null : null,
                            'qty_delivery' => $row['qty'],
                            'unit_name_delivery' => $product ? $product->unitDelivery ? $product->unitDelivery->name : null : null,
                        ]
                    );
                }

                /**
                 * Update data ke po brownies gudang
                 */
                BrowniesTargetPlanWarehouse::where('po_order_product_id', $model->id)->update([
                    'barcode_po' => $value['barcode'],
                ]);
            }

            PoOrderProductStatusLog::updateOrCreate(
                [
                    'po_order_product_id' => $model->id,
                    'status' => 'processed',
                ],
                [
                    'po_order_product_id' => $model->id,
                    'status' => 'processed',
                ]
            );

            if (isset($data['note'])) {
                foreach ($data['note'] as $value) {
                    PoOrderProductNote::updateOrCreate(
                        [
                            'po_order_product_id' => $model->id,
                            'product_id' => $value['product_id'],
                        ],
                        [
                            'po_order_product_id' => $model->id,
                            'product_id' => $value['product_id'],
                            'note' => $value['note'],
                        ]
                    );
                }
                PoOrderProductStatusLog::updateOrCreate(
                    [
                        'po_order_product_id' => $model->id,
                        'status' => 'product_rejected',
                    ],
                    [
                        'po_order_product_id' => $model->id,
                        'status' => 'product_rejected',
                    ]
                );
            }

            PoOrderProductStatusLog::updateOrCreate(
                [
                    'po_order_product_id' => $model->id,
                    'status' => 'product_accepted',
                ],
                [
                    'po_order_product_id' => $model->id,
                    'status' => 'product_accepted',
                ]
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
        $model = $this->model->with(['branch:id,name', 'details', 'statusLogs', 'createdBy:id,name', 'details.product:id,name,code,product_unit_id,product_unit_delivery_id,unit_value', 'details.product.unit:id,name', 'branch:id,name'])->branch()->available()->findOrFail($id);
        $model->date_packaging = null;
        foreach ($model->statusLogs as $value) {
            if ($value->status == 'product_accepted') {
                $model->date_packaging = tanggal_indo($value->created_at, false, false);
            }
        }

        foreach ($model->details as $key => $value) {
            if ($value->product) {
                $unit = $value->product->unit ? $value->product->unit->name : null;
                $value->product->total_unit = $value->qty . ' ' . $unit;
                $unit_delivery = $value->product->unitDelivery ? $value->product->unitDelivery->name : null;
                $value->product->total_unit_delivery = Product::getTotalUnitDelivery($value->qty, $value->product->unit_value) . ' ' . $unit_delivery;
            }
        }

        return $this->response($model);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showPackaging($id)
    {
        $model = $this->model->with(['packagings', 'packagings.products:id,name,code'])->findOrFail($id);
        return $this->response($model);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function status(Request $request, $id)
    {
        $data = $this->validate($request, [
            'status' => 'required|in:product_accepted,product_rejected,print,product_incomplete,print-po',
            'note' => 'required_if:status,product_rejected,product_incomplete',
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
                    'note' => $data['note']
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
            'products' => 'required|array',
            'products.*.po_order_product_detail_id' => 'required|exists:po_order_product_details,id',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.qty' => 'required|integer',
        ]);

        $model = $this->model->findOrFail($id);

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            $model->update($data);
            foreach ($data['products'] as $value) {
                $model->details()->where('id', $value['po_order_product_detail_id'])->update(Arr::except($value, ['po_order_product_detail_id']));
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
            return $this->model->whereIn('id', $data['id'])->delete();
        });

        return $this->response($data ? true : false);
    }
}
