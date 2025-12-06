<?php

namespace App\Http\Controllers\Api\V1\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Inventory\TransferStock;
use App\Models\Inventory\TransferStockProduct;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Services\Inventory\StockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransferStockController extends Controller
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
    public function __construct(TransferStock $model, StockService $stockService)
    {
        $this->middleware('permission:transfer-stok.lihat|transfer-stok.show', [
            'only' => ['index', 'show']
        ]);
        $this->middleware('permission:transfer-stok.tambah', [
            'only' => ['store', 'listProduct', 'listBranch']
        ]);
        $this->middleware('permission:transfer-stok.ubah', [
            'only' => ['update', 'listProduct', 'listBranch']
        ]);
        $this->middleware('permission:transfer-stok.hapus', [
            'only' => ['destroy']
        ]);
        $this->model = $model;
        $this->stockService = $stockService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = $this->model
            ->where('date', '>=', $request->start_date)->where('date', '<=', $request->end_date)
            ->with([
                'branch:id,name',
                'branch_sender:id,name',
                'branch_receiver:id,name',
                'created_by:id,name',
                'products',
            ])
            ->where(function ($query) {
                if (Auth::user()->branch_id != 1) {
                    $query->where('branch_id', Auth::user()->branch_id)->orWhere('branch_receiver_id', Auth::user()->branch_id);
                }
            })
            ->search($request)
            ->sort($request);

        if ($request->delivery_number) {
            $data = $data->where('delivery_number', $request->delivery_number);
        }

        $data = $data->paginate($this->perPage($data));
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listProduct(Request $request)
    {
        $data = Product::select('id', 'code', 'name', 'price')->search($request)->orderBy('name')->get();
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listIngredient(Request $request)
    {
        $data = ProductIngredient::select('id', 'code', 'name')->search($request)->orderBy('name')->get();
        return $this->response($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listBranch(Request $request)
    {
        if ($request->all) {
            $data = Branch::select('id', 'name')->search($request)->orderBy('name')->get();
        } else {
            $data = Branch::select('id', 'name')->branch()->search($request)->orderBy('name')->get();
        }
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
        $data = $this->validate($request, [
            'branch_sender_id' => 'required|exists:branches,id',
            'branch_receiver_id' => 'required|exists:branches,id',
            'sender' => 'required|string',
            'date' => 'required|date',
            'products' => 'nullable|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.box' => 'required|in:box1,box2,box3,box4,box5,box6,box7,box8,box9',
            'products.*.qty' => 'required|min:1',
            'product_ingredients' => 'nullable|array',
            'product_ingredients.*.id' => 'required|exists:product_ingredients,id',
            'product_ingredients.*.box' => 'required|in:box1,box2,box3,box4,box5,box6,box7,box8,box9',
            'product_ingredients.*.qty' => 'required|min:1',
        ]);

        $auth = Auth::user();
        if ($auth->branch_id != 1) {
            if ($auth->branch_id != $data['branch_sender_id']) {
                return $this->response('Cabang pengirim tidak valid.', 'error', 422);
            }
        }

        $products = [];
        if (isset($data['products']) && $data['products']) {
            foreach ($data['products'] as $value) {
                $product = Product::select('id', 'code', 'name', 'price')->find($value['id']);
                $value['product_id'] = $value['id'];
                $value['code'] = $product->code;
                $value['price'] = $product->price;
                $value['total_price'] = $product->price * $value['qty'];
                $products[] = $value;
            }
        }

        if (isset($data['product_ingredients']) && $data['product_ingredients']) {
            foreach ($data['product_ingredients'] as $value) {
                $product = ProductIngredient::select('id', 'code', 'name')->find($value['id']);
                $value['product_ingredient_id'] = $value['id'];
                $value['code'] = $product->code;
                $products[] = $value;
            }
        }

        $data = DB::connection('mysql')->transaction(function () use ($data, $products) {
            $model = $this->model->create($data);
            foreach ($products as $value) {
                $model->products()->create($value);
                if (isset($data['products']) && $data['products']) {
                    $this->stockService->create($value['id'], $data['branch_receiver_id'], $value['qty'], 'Transfer Stok', 'transfer_stock_products', $model->id);
                    $this->stockService->create($value['id'], $data['branch_sender_id'], $value['qty'] * -1, 'Transfer Stok', 'transfer_stock_products', $model->id);
                }
            }
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
        $model = $this->model->with([
            'branch:id,name',
            'branch_sender:id,name',
            'branch_receiver:id,name',
            'created_by:id,name',
        ])
        ->where(function ($query) {
            if (Auth::user()->branch_id != 1) {
                $query->where('branch_id', Auth::user()->branch_id)->orWhere('branch_receiver_id', Auth::user()->branch_id);
            }
        })
        ->findOrFail($id);

        $model->products = TransferStockProduct::where('transfer_stock_id', $id)->whereNotNull('product_id')->get();
        $model->product_ingredients = TransferStockProduct::where('transfer_stock_id', $id)->whereNotNull('product_ingredient_id')->get();

        return $this->response($model);
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
            'branch_sender_id' => 'required|exists:branches,id',
            'branch_receiver_id' => 'required|exists:branches,id',
            'sender' => 'required|string',
            'date' => 'required|date',
            'products' => 'nullable|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.box' => 'required|in:box1,box2,box3,box4,box5,box6,box7,box8,box9',
            'products.*.qty' => 'required|min:1',
            'product_ingredients' => 'nullable|array',
            'product_ingredients.*.id' => 'required|exists:product_ingredients,id',
            'product_ingredients.*.box' => 'required|in:box1,box2,box3,box4,box5,box6,box7,box8,box9',
            'product_ingredients.*.qty' => 'required|min:1',
        ]);

        $products = [];
        if (isset($data['products']) && $data['products']) {
            foreach ($data['products'] as $value) {
                $product = Product::select('id', 'code', 'name', 'price')->find($value['id']);
                $value['product_id'] = $value['id'];
                $value['code'] = $product->code;
                $value['price'] = $product->price;
                $value['total_price'] = $product->price * $value['qty'];
                $products[] = $value;
            }
        }

        if (isset($data['product_ingredients']) && $data['product_ingredients']) {
            foreach ($data['product_ingredients'] as $value) {
                $product = ProductIngredient::select('id', 'code', 'name')->find($value['id']);
                $value['product_ingredient_id'] = $value['id'];
                $value['code'] = $product->code;
                $products[] = $value;
            }
        }

        $model = $this->model->findOrFail($id);
        $auth = Auth::user();
        $data = DB::connection('mysql')->transaction(function () use ($data, $model, $products, $auth) {
            $model->update($data);
            $stockOld =  $model->products;
            $model->products()->delete();

            $productIds = [];
            foreach ($products as $value) {
                if (isset($data['products']) && $data['products']) {
                    $old = $stockOld->where('product_id', $value['product_id'])->first();
                    if ($old) { //update data lama
                        $oldQty = $old->qty;
                        if ($oldQty > $value['qty']) {
                            $qty = $value['qty'] - $oldQty;
                            $this->stockService->create($value['product_id'], $data['branch_receiver_id'], $qty, 'Transfer Stok', 'transfer_stock_products', $model->id);
                            $this->stockService->create($value['product_id'], $data['branch_sender_id'], $qty * -1, 'Transfer Stok', 'transfer_stock_products', $model->id);
                        } elseif ($oldQty < $value['qty']) {
                            $qty = $oldQty - $value['qty'];
                            $this->stockService->create($value['product_id'], $data['branch_receiver_id'], $qty * -1, 'Transfer Stok', 'transfer_stock_products', $model->id);
                            $this->stockService->create($value['product_id'], $data['branch_sender_id'], $qty, 'Transfer Stok', 'transfer_stock_products', $model->id);
                        }
                    } else { //tambah baru
                        $qty = $value['qty'];
                        $this->stockService->create($value['product_id'], $data['branch_receiver_id'], $qty, 'Transfer Stok', 'transfer_stock_products', $model->id);
                        $this->stockService->create($value['product_id'], $data['branch_sender_id'], $qty * -1, 'Transfer Stok', 'transfer_stock_products', $model->id);
                    }
                    $productIds[] = $value['product_id'];
                }
                $model->products()->create($value);
            }

            if (isset($data['products']) && $data['products']) {
                if (count($productIds) > 0) {
                    $productIdOlds = $stockOld->whereNotIn('product_id', $productIds);
                    foreach ($productIdOlds as $value) {
                        $this->stockService->create($value->product_id, $data['branch_receiver_id'], $value->qty * -1, 'Transfer Stok', 'transfer_stock_products', $model->id);
                        $this->stockService->create($value->product_id, $data['branch_sender_id'], $value->qty, 'Transfer Stok', 'transfer_stock_products', $model->id);
                    }
                }
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
            foreach ($data['id'] as $value) {
                $model = $this->model->with(['products'])->find($value);
                if ($model && $model->products) {
                    foreach ($model->products as $row) {
                        $this->stockService->create($row->product_id, $row->branch_receiver_id, $row->qty * -1, 'Transfer Stok', 'transfer_stock_products', $model->id);
                        $this->stockService->create($row->product_id, $row->branch_sender_id, $row->qty, 'Transfer Stok', 'transfer_stock_products', $model->id);
                    }
                }
            }
            return $this->model->whereIn('id', $data['id'])->delete();
        });

        return $this->response($data ? true : false);
    }
}
