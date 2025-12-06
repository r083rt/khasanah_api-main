<?php

namespace App\Http\Controllers\Api\V1\Distribution;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Distribution\PoManual;
use App\Models\Distribution\PoManualDetail;
use App\Models\Distribution\PoManualPackaging;
use App\Models\Distribution\PoManualPackagingDetail;
use App\Models\Distribution\PoOrderIngredient;
use App\Models\Distribution\PoOrderIngredientDetail;
use App\Models\Distribution\PoOrderIngredientPackaging;
use App\Models\Distribution\PoOrderIngredientPackagingIngredient;
use App\Models\Distribution\PoOrderProduct;
use App\Models\Distribution\PoOrderProductDetail;
use App\Models\Distribution\PoOrderProductPackaging;
use App\Models\Distribution\PoOrderProductPackagingProduct;
use App\Models\Distribution\PoSj;
use App\Models\Distribution\PoSjItem;
use App\Models\Management\Shipping;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Models\ProductStock;
use App\Services\Inventory\StockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PoReceiveController extends Controller
{
    /**
     * The user repository instance.
     */
    protected $model;
    protected $stockService;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(PoSj $model, StockService $stockService)
    {
        $this->middleware('permission:po-receive.lihat|po-receive.lihat', [
            'only' => ['checkBarcode']
        ]);
        $this->middleware('permission:po-receive.ubah|po-receive.ubah', [
            'only' => ['store']
        ]);
        $this->model = $model;
        $this->stockService = $stockService;
    }

    /**
     * Check barcode
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function checkBarcode($barcode)
    {
        $branch_id = Auth::user()->branch_id;
        $data = collect([]);

        $is_editable = true;
        $type = substr($barcode, 0, 1);
        if ($type == 1 || $type == 4) {
            $packaging = PoOrderProductPackaging::where('barcode', $barcode)->first();
            $typePo = 'product';
            if ($packaging) {
                $data = PoSjItem::where('po_id', $packaging->po_order_product_id)->where('box_name', $packaging->name)->whereIn('type', ['po_order_product', 'po_brownies_product'])->where('branch_id', $branch_id)->get();
                foreach ($data as $value) {
                    if ($value->is_submitted == 1) {
                        $is_editable = false;
                    }
                    if (is_null($value->qty_real)) {
                        $is_editable = true;
                        $qty_real = $value->qty_delivery;

                        $product = Product::select('product_category_id')->find($value->product_id);
                        if ($product && !in_array($product->product_category_id, config('production.cookie_categories'))) { //jika bukan roti manis
                            $po_id = $packaging->po_order_product_id;
                            $status = 'done';
                            $po = PoOrderProduct::where('id', $po_id)->first();
                            if ($po) {
                                $po->update([
                                    'status' => $status
                                ]);

                                $po->statusLogs()->updateOrCreate(
                                    [
                                        'status' => $status,
                                    ],
                                    [
                                        'status' => $status
                                    ]
                                );
                            }

                            $poSjItem = $value;
                            if ($poSjItem) {
                                $qty = $qty_real;

                                $poSjItem->update([
                                    'qty_real' => $qty_real,
                                    'received_date' => date('Y-m-d'),
                                    'branch_receiver_id' => $branch_id,
                                ]);

                                $product = Product::select('unit_value')->find($poSjItem->product_id);
                                if ($product) {
                                    $qty = Product::getTotalUnit($qty, $product->unit_value);
                                    $stock = ProductStock::where('branch_id', $branch_id)->where('product_id', $poSjItem->product_id)->first();
                                    if ($stock) {
                                        $oldStock = $stock->stock;
                                        $stock->update([
                                                'stock' => $oldStock + $qty
                                            ]);

                                        $this->stockService->createStockLog([
                                                'branch_id' => $branch_id,
                                                'product_id' =>  $poSjItem->product_id,
                                                'stock' => $qty,
                                                'stock_old' => $oldStock,
                                                'from' => $type == 1 ? 'Po Pesanan' : "Po Brownis",
                                                'table_reference' => 'po_sj_items',
                                                'table_id' => $poSjItem->id
                                            ]);
                                    } else {
                                        ProductStock::create([
                                                'branch_id' =>  $branch_id,
                                                'product_id' =>  $poSjItem->product_id,
                                                'stock' =>  $qty
                                            ]);

                                        $this->stockService->createStockLog([
                                                'branch_id' => $branch_id,
                                                'product_id' =>  $poSjItem->product_id,
                                                'stock' => $qty,
                                                'stock_old' => 0,
                                                'from' => $type == 1 ? 'Po Pesanan' : "Po Brownis",
                                                'table_reference' => 'po_sj_items',
                                                'table_id' => $poSjItem->id
                                            ]);
                                    }
                                }
                            }
                        } else {
                            $is_editable = true;
                        }
                    }
                }
            }
        } elseif ($type == 2) {
            $packaging = PoOrderIngredientPackaging::where('barcode', $barcode)->first();
            $typePo = 'ingredient';
            if ($packaging) {
                $data = PoSjItem::where('po_id', $packaging->po_order_ingredient_id)->where('box_name', $packaging->name)->where('type', 'po_order_ingredient')->where('branch_id', $branch_id)->get();
                foreach ($data as $value) {
                    if ($value->is_submitted == 1) {
                        $is_editable = false;
                    }
                    if (is_null($value->qty_real)) {
                        $value->qty_real = $value->qty_delivery;
                    }
                }
            }
        } elseif ($type == 3) {
            $packaging = PoManualPackaging::where('barcode', $barcode)->first();
            if ($packaging) {
                $cek = PoManual::find($packaging->po_manual_id);
                $type = $cek ? $cek->type == 'product' ? 'po_manual_product' : 'po_manual_ingredient' : '';
                if ($type == 'po_manual_product') {
                    $typePo = 'product';
                } else {
                    $typePo = 'ingredient';
                }
                $data = PoSjItem::where('po_id', $packaging->po_manual_id)->where('box_name', $packaging->name)->where('type', $type)->where('branch_id', $branch_id)->get();
                foreach ($data as $value) {
                    if ($value->is_submitted == 1) {
                        $is_editable = false;
                    }
                    if (is_null($value->qty_real)) {
                        $qty_real = $value->qty_delivery;

                        if ($type == 'po_manual_product') {
                            $product = Product::select('product_category_id')->find($value->product_id);
                            if ($product && !in_array($product->product_category_id, config('production.cookie_categories'))) { //jika bukan roti manis
                                $po_id = $packaging->po_manual_id;
                                $status = 'done';
                                $po = PoManual::where('id', $po_id)->first();
                                if ($po) {
                                    $po->update([
                                        'status' => $status
                                    ]);

                                    $po->statusLogs()->updateOrCreate(
                                        [
                                            'status' => $status,
                                        ],
                                        [
                                            'status' => $status
                                        ]
                                    );
                                }

                                $poSjItem = $value;
                                if ($poSjItem) {
                                    $qty = $qty_real;

                                    $poSjItem->update([
                                        'qty_real' => $qty_real,
                                        'received_date' => date('Y-m-d'),
                                        'branch_receiver_id' => $branch_id,
                                    ]);

                                    $product = Product::select('unit_value')->find($poSjItem->product_id);
                                    if ($product) {
                                        $qty = Product::getTotalUnit($qty, $product->unit_value);
                                        $stock = ProductStock::where('branch_id', $branch_id)->where('product_id', $poSjItem->product_id)->first();
                                        if ($stock) {
                                            $oldStock = $stock->stock;
                                            $stock->update([
                                                    'stock' => $oldStock + $qty
                                                ]);

                                            $this->stockService->createStockLog([
                                                    'branch_id' => $branch_id,
                                                    'product_id' =>  $poSjItem->product_id,
                                                    'stock' => $qty,
                                                    'stock_old' => $oldStock,
                                                    'from' => 'Po Manual',
                                                    'table_reference' => 'po_sj_items',
                                                    'table_id' => $poSjItem->id
                                                ]);
                                        } else {
                                            ProductStock::create([
                                                    'branch_id' =>  $branch_id,
                                                    'product_id' =>  $poSjItem->product_id,
                                                    'stock' =>  $qty
                                                ]);

                                            $this->stockService->createStockLog([
                                                    'branch_id' => $branch_id,
                                                    'product_id' =>  $poSjItem->product_id,
                                                    'stock' => $qty,
                                                    'stock_old' => 0,
                                                    'from' => 'Po Manual',
                                                    'table_reference' => 'po_sj_items',
                                                    'table_id' => $poSjItem->id
                                                ]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($data->count() == 0) {
            return $this->response([
                'is_editable' => false,
                'type' => 'product',
                'data' => []
            ]);
        } else {
            $result = [
                'is_editable' => $is_editable,
                'type' => $typePo,
                'data' => $data
            ];

            return $this->response($result);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $this->validate($request, [
            'datas' => 'required|array',
            'datas.*.id' => 'required|exists:po_sj_items,id',
            'datas.*.qty_real' => 'required|integer',
            'products' => 'nullable|array',
            'products.*.product_id' => 'nullable|exists:products,id',
            'products.*.product_ingredient_id' => 'nullable|exists:product_ingredients,id',
            'products.*.qty_real' => 'required|integer',
            'note' => 'nullable|string',
        ]);

        $status = 'done';
        $po_id = null;
        $po_sj_id = null;
        $po_type = null;
        $box_name = null;
        $po_date = null;
        foreach ($data['datas'] as $value) {
            $posj = PoSjItem::find($value['id']);
            $po_id = $posj->po_id;
            $po_sj_id = $posj->po_sj_id;
            $box_name = $posj->box_name;
            $po_type = $posj->type;
            $po_date = $posj->po_date;
            if ($value['qty_real'] < $posj->qty_delivery) {
                $status = 'product_incomplete';
                if (!isset($data['note']) || $data['note'] == '') {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Ada jumlah produk/bahan yang lebih kecil. Silahkan isi keterangan.'
                    ], 406);
                }
            }
        }

        $branchId = Auth::user()->branch_id;
        $data = DB::connection('mysql')->transaction(function () use ($data, $status, $po_id, $po_type, $branchId, $po_sj_id, $box_name, $po_date) {
            foreach ($data['datas'] as $value) {
                PoSjItem::where('id', $value['id'])->update([
                    'qty_real' => $value['qty_real'],
                    'received_date' => date('Y-m-d'),
                    'branch_receiver_id' => $branchId,
                    'is_submitted' => 1
                ]);

                if ($po_type == 'po_order_product' || $po_type == 'po_brownies_product') {
                    $po = PoOrderProduct::where('id', $po_id)->first();
                    if ($po) {
                        if ($po->status != 'product_incomplete') {
                            $po->update([
                                'status' => $status
                            ]);
                        }

                        $po->statusLogs()->updateOrCreate(
                            [
                                'status' => $status,
                            ],
                            [
                                'status' => $status,
                                'note' => isset($data['note']) ? $data['note'] : null,
                            ]
                        );
                    }

                    $poSjItem = PoSjItem::select('id', 'branch_id', 'product_id', 'qty', 'qty_real', 'qty_delivery')->where('id', $value['id'])->first();
                    if ($poSjItem) {
                        $diff = $poSjItem->qty_real - $poSjItem->qty_delivery;
                        if ($diff == 0 || $poSjItem->qty_real > $poSjItem->qty_delivery) {
                            $qty =  $poSjItem->qty_real;

                            $product = Product::select('unit_value', 'product_category_id')->find($poSjItem->product_id);
                            if ($product && in_array($product->product_category_id, config('production.cookie_categories'))) {
                                $qty = Product::getTotalUnit($qty, $product->unit_value);
                                $stock = ProductStock::where('branch_id', $poSjItem->branch_id)->where('product_id', $poSjItem->product_id)->first();
                                if ($stock) {
                                    $oldStock = $stock->stock;
                                    $stock->update([
                                            'stock' => $oldStock + $qty
                                        ]);

                                    $this->stockService->createStockLog([
                                            'branch_id' => $poSjItem->branch_id,
                                            'product_id' =>  $poSjItem->product_id,
                                            'stock' => $qty,
                                            'stock_old' => $oldStock,
                                            'from' => $po_type == 'po_order_product' ? 'Po Pesanan' : "Po Brownis",
                                            'table_reference' => 'po_sj_items',
                                            'table_id' => $poSjItem->id
                                        ]);
                                } else {
                                    ProductStock::create([
                                            'branch_id' =>  $poSjItem->branch_id,
                                            'product_id' =>  $poSjItem->product_id,
                                            'stock' =>  $qty
                                        ]);

                                    $this->stockService->createStockLog([
                                            'branch_id' => $poSjItem->branch_id,
                                            'product_id' =>  $poSjItem->product_id,
                                            'stock' => $qty,
                                            'stock_old' => 0,
                                            'from' => $po_type == 'po_order_product' ? 'Po Pesanan' : "Po Brownis",
                                            'table_reference' => 'po_sj_items',
                                            'table_id' => $poSjItem->id
                                        ]);
                                }
                            }
                        }
                    }
                } elseif ($po_type == 'po_order_ingredient') {
                    $po = PoOrderIngredient::where('id', $po_id)->first();
                    if ($po) {
                        if ($po->status != 'product_incomplete') {
                            $po->update([
                                'status' => $status
                            ]);
                        }

                        $po->statusLogs()->updateOrCreate(
                            [
                                'status' => $status
                            ],
                            [
                                'status' => $status,
                                'note' => isset($data['note']) ? $data['note'] : null,
                            ]
                        );
                    }
                } else {
                    $po = PoManual::where('id', $po_id)->first();
                    if ($po) {
                        if ($po->status != 'product_incomplete') {
                            $po->update([
                                'status' => $status
                            ]);
                        }

                        $po->statusLogs()->updateOrCreate(
                            [
                                'status' => $status,
                            ],
                            [
                                'status' => $status,
                                'note' => isset($data['note']) ? $data['note'] : null,
                            ]
                        );
                    }

                    if ($po_type == 'po_manual_product') {
                        $poSjItem = PoSjItem::select('id', 'branch_id', 'product_id', 'qty', 'qty_real', 'qty_delivery')->where('id', $value['id'])->first();
                        if ($poSjItem) {
                            $diff = $poSjItem->qty_real - $poSjItem->qty_delivery;
                            if ($diff == 0 || $poSjItem->qty_real > $poSjItem->qty_delivery) {
                                $qty =  $poSjItem->qty_real;

                                $product = Product::select('unit_value', 'product_category_id')->find($poSjItem->product_id);
                                if ($product && in_array($product->product_category_id, config('production.cookie_categories'))) {
                                    $qty = Product::getTotalUnit($qty, $product->unit_value);
                                    $stock = ProductStock::where('branch_id', $poSjItem->branch_id)->where('product_id', $poSjItem->product_id)->first();
                                    if ($stock) {
                                        $oldStock = $stock->stock;
                                        $stock->update([
                                                'stock' => $oldStock + $qty
                                            ]);

                                        $this->stockService->createStockLog([
                                                'branch_id' => $poSjItem->branch_id,
                                                'product_id' =>  $poSjItem->product_id,
                                                'stock' => $qty,
                                                'stock_old' => $oldStock,
                                                'from' => 'Po Manual',
                                                'table_reference' => 'po_sj_items',
                                                'table_id' => $poSjItem->id
                                            ]);
                                    } else {
                                        ProductStock::create([
                                                'branch_id' =>  $poSjItem->branch_id,
                                                'product_id' =>  $poSjItem->product_id,
                                                'stock' =>  $qty
                                            ]);

                                        $this->stockService->createStockLog([
                                                'branch_id' => $poSjItem->branch_id,
                                                'product_id' =>  $poSjItem->product_id,
                                                'stock' => $qty,
                                                'stock_old' => 0,
                                                'from' => 'Po Manual',
                                                'table_reference' => 'po_sj_items',
                                                'table_id' => $poSjItem->id
                                            ]);
                                    }
                                }
                            }
                        }
                    }
                }


            }

            foreach ($data['products'] as $value) {
                if (isset($value['product_id']) && $value['product_id']) {
                    switch ($po_type) {
                        case 'po_order_product':
                            $from = 'Penyesuaian Po Pesanan';
                            break;

                        case 'po_manual_product':
                            $from = 'Penyesuaian Po Manual';
                            break;

                        case 'po_brownies_product':
                            $from = 'Penyesuaian Po Brownis';
                            break;

                        default:
                            $from = null;
                            break;
                    }

                    if ($po_type == 'po_manual_product') {
                        $packaging = PoManualPackaging::where('po_manual_id', $po_id)->where('name', $box_name)->first();
                        PoManualPackagingDetail::create([
                            'po_manual_packaging_id' => $packaging ? $packaging->id : null,
                            'product_id' => $value['product_id'],
                            'qty' => $value['qty_real']
                        ]);

                        PoManualDetail::create([
                            'po_manual_id' => $po_id,
                            'product_id' => $value['product_id'],
                            'qty' => $value['qty_real']
                        ]);

                        $po = PoManual::where('id', $po_id)->first();
                        if ($po) {
                            $po->update([
                                'status' => 'product_incomplete'
                            ]);

                            $po->statusLogs()->updateOrCreate(
                                [
                                    'status' => 'product_incomplete',
                                ],
                                [
                                    'status' => 'product_incomplete'
                                ]
                            );
                        }
                    } elseif ($po_type == 'po_order_product' || $po_type == 'po_brownies_product') {
                        $packaging = PoOrderProductPackaging::where('po_order_product_id', $po_id)->where('name', $box_name)->first();
                        PoOrderProductPackagingProduct::create([
                            'po_order_product_packaging_id' => $packaging ? $packaging->id : null,
                            'product_id' => $value['product_id'],
                            'qty' => $value['qty_real']
                        ]);

                        PoOrderProductDetail::create([
                            'po_order_product_id' => $po_id,
                            'product_id' => $value['product_id'],
                            'qty' => $value['qty_real']
                        ]);

                        $po = PoOrderProduct::where('id', $po_id)->first();
                        if ($po) {
                            $po->update([
                                'status' => 'product_incomplete'
                            ]);

                            $po->statusLogs()->updateOrCreate(
                                [
                                    'status' => 'product_incomplete',
                                ],
                                [
                                    'status' => 'product_incomplete'
                                ]
                            );
                        }
                    }

                    $product = Product::find($value['product_id']);
                    $po = PoSjItem::create([
                        'po_sj_id' => $po_sj_id,
                        'po_id' => $po_id,
                        'branch_id' => $branchId,
                        'branch_receiver_id' => $branchId,
                        'type' => $po_type,
                        'box_name' => $box_name,
                        'product_id' => $value['product_id'],
                        'code_item' => $product ? $product->code : '',
                        'name_item' => $product ? $product->name : '',
                        'qty' => $value['qty_real'],
                        'qty_real' =>$value['qty_real'],
                        'qty_delivery' => $value['qty_real'],
                        'unit_name' => $product ? $product->unit ? $product->unit->name : null : null,
                        'unit_name_delivery' => $product ? $product->unit ? $product->unit->name : null : null,
                        'hpp' => $product ? $product->price_sale : null,
                        'received_date' => date('Y-m-d'),
                        'is_added' => 1,
                        'is_submitted' => 1,
                        'po_date' => $po_date
                    ]);

                    // $stock = ProductStock::where('branch_id', $branchId)->where('product_id', $value['product_id'])->first();
                    // if ($stock) {
                    //     $oldStock = $stock->stock;
                    //     $stock->update([
                    //         'stock' => $oldStock + $value['qty_real'],
                    //     ]);

                    //     $this->stockService->createStockLog([
                    //         'branch_id' => $branchId,
                    //         'product_id' => $value['product_id'],
                    //         'stock' =>  $value['qty_real'],
                    //         'stock_old' => $oldStock,
                    //         'from' => $from,
                    //         'table_reference' => 'po_sj_items',
                    //         'table_id' => $po->id
                    //     ]);
                    // } else {
                    //     ProductStock::create([
                    //         'branch_id' => $branchId,
                    //         'product_id' => $value['product_id'],
                    //         'stock' =>   $value['qty_real'],
                    //     ]);

                    //     $this->stockService->createStockLog([
                    //         'branch_id' => $branchId,
                    //         'product_id' => $value['product_id'],
                    //         'stock' =>  $value['qty_real'],
                    //         'stock_old' => 0,
                    //         'from' => $from,
                    //         'table_reference' => 'po_sj_items',
                    //         'table_id' => $po->id
                    //     ]);
                    // }
                } else {
                    if ($po_type == 'po_manual_ingredient') {
                        $packaging = PoManualPackaging::where('po_manual_id', $po_id)->where('name', $box_name)->first();
                        PoManualPackagingDetail::create([
                            'po_manual_packaging_id' => $packaging ? $packaging->id : null,
                            'product_ingredient_id' => $value['product_ingredient_id'],
                            'qty' => $value['qty_real']
                        ]);

                        PoManualDetail::create([
                            'po_manual_id' => $po_id,
                            'product_ingredient_id' => $value['product_ingredient_id'],
                            'qty' => $value['qty_real']
                        ]);

                        $po = PoManual::where('id', $po_id)->first();
                        if ($po) {
                            $po->update([
                                'status' => 'product_incomplete'
                            ]);

                            $po->statusLogs()->updateOrCreate(
                                [
                                    'status' => 'product_incomplete',
                                ],
                                [
                                    'status' => 'product_incomplete'
                                ]
                            );
                        }
                    } elseif ($po_type == 'po_order_ingredient') {
                        $packaging = PoOrderIngredientPackagingIngredient::where('po_order_ingredient_id', $po_id)->where('name', $box_name)->first();
                        PoOrderIngredientPackagingIngredient::create([
                            'po_order_ingredient_packaging_id' => $packaging ? $packaging->id : null,
                            'product_ingredient_id' => $value['product_ingredient_id'],
                            'qty' => $value['qty_real']
                        ]);

                        PoOrderIngredientDetail::create([
                            'po_order_ingredient_id' => $po_id,
                            'product_ingredient_id' => $value['product_id'],
                            'qty' => $value['qty_real']
                        ]);

                        $po = PoOrderIngredient::where('id', $po_id)->first();
                        if ($po) {
                            $po->update([
                                'status' => 'product_incomplete'
                            ]);

                            $po->statusLogs()->updateOrCreate(
                                [
                                    'status' => 'product_incomplete',
                                ],
                                [
                                    'status' => 'product_incomplete'
                                ]
                            );
                        }
                    }

                    $product = ProductIngredient::find($value['product_ingredient_id']);
                    PoSjItem::create([
                        'po_sj_id' => $po_sj_id,
                        'po_id' => $po_id,
                        'branch_id' => $branchId,
                        'branch_receiver_id' => $branchId,
                        'type' => 'po_manual_ingredient',
                        'box_name' => $box_name,
                        'product_ingredient_id' => $value['product_ingredient_id'],
                        'code_item' => $product ? $product->code : '',
                        'name_item' => $product ? $product->name : '',
                        'qty' =>$value['qty_real'],
                        'qty_real' =>$value['qty_real'],
                        'qty_delivery' =>$value['qty_real'],
                        'unit_name' => $product ? $product->unit ? $product->unit->name : null : null,
                        'unit_name_delivery' => $product ? $product->unit ? $product->unit->name : null : null,
                        'hpp' => $product ? $product->hpp : null,
                        'received_date' => date('Y-m-d'),
                        'is_added' => 1,
                        'is_submitted' => 1,
                        'po_date' => $po_date
                    ]);
                }
            }

            return true;
        });

        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listProduct()
    {
        $data = Product::select('id', 'code', 'name', 'price')->whereIn('product_category_id', [1, 4])->orderBy('name')->get();
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listBahan(Request $request)
    {
        $data = ProductIngredient::select('id', 'name', 'code')->search($request)->orderBy('name')->get();
        return $this->response($data);
    }
}
