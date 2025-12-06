<?php

namespace App\Http\Controllers\Api\V1\Production;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Inventory\Packaging;
use App\Models\Inventory\ProductStockLogTemp;
use App\Models\Product;
use App\Services\Production\BrowniesStoreProductionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class BrowniesStoreProductionController extends Controller
{
    /**
     * The user repository instance.
     */
    protected $browniesStoreProductionService;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(BrowniesStoreProductionService $browniesStoreProductionService)
    {
        $this->middleware('permission:brownies-toko-po.lihat|brownies-toko-po.show', [
            'only' => ['index', 'show', 'listBranch']
        ]);
        $this->browniesStoreProductionService = $browniesStoreProductionService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $date = $request->date;
        $branchId = $request->branch_id;
        $day = date_to_day($date);
        if (!$branchId) {
            $branchId = Auth::user()->branch_id;
        }

        $datas = $this->browniesStoreProductionService->getProduction($date, $day, $branchId);

        return $this->response($datas);
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
            'date' => 'required|date',
            'datas' => 'required|array',
            'datas.*.master_packaging_id' => 'nullable|integer',
            'datas.*.product_id' => 'nullable|integer',
            'datas.*.product_ids' => 'nullable|array',
            'datas.*.product_ids.*' => 'required|integer',
            'datas.*.total_po' => 'required|integer',
            'datas.*.grind' => 'required|integer',
            'datas.*.pcs' => 'required|integer',
        ]);

        $branchId = Auth::user()->branch_id;
        $cek = $this->browniesStoreProductionService->checkData($data['date'], $branchId);
        if ($cek->count() > 0) {
            return $this->response('Tanggal PO ' . $data['date'] . ' Produksi sudah disubmit', 'error', 422);
        }

        $data = DB::connection('mysql')->transaction(function () use ($data, $branchId) {
            foreach ($data['datas'] as $value) {
                if (isset($value['product_id']) && $value['product_id']) {
                    $product = Product::find($value['product_id']);
                    $value['master_packaging_id'] = null;
                } else {
                    $product = Packaging::find($value['master_packaging_id']);
                    $value['product_id'] = null;
                }
                $value['branch_id'] = $branchId;
                $value['date'] = $data['date'];
                $value['product_name'] = $product ? $product->name : null;
                $value['product_code'] = $product ? $product->code : null;

                if (!empty($value['product_ids'])) {
                    $products = [];
                    $prorate = $this->prorate(($value['grind'] * $value['pcs']), count($value['product_ids']));
                    foreach ($value['product_ids'] as $key => $row) {
                        $products[] = [
                            'id' => $row,
                            'value' => $prorate[$key],
                        ];
                    }

                    $value['product_ids'] = $products;
                }

                $model = $this->browniesStoreProductionService->create($value);

                if ($model->product_ids) {
                    foreach($model->product_ids as $row) {
                        ProductStockLogTemp::create([
                            'date' => date('Y-m-d', strtotime('+1 days', strtotime($data['date']))),
                            'branch_id' => $branchId,
                            'product_id' => $row['id'],
                            'stock' => $row['value'],
                            'from' => "Po Brownis Toko",
                            'table_reference' => "brownies_store_productions",
                            'table_id' => $model->id,
                            'created_by' => Auth::id()
                        ]);
                    }
                }

                if (isset($value['product_id']) && $value['product_id']) {
                    ProductStockLogTemp::create([
                        'date' => date('Y-m-d', strtotime('+1 days', strtotime($data['date']))),
                        'branch_id' => $branchId,
                        'product_id' => $value['product_id'],
                        'stock' => $value['grind'] * $value['pcs'],
                        'from' => "Po Brownis Toko",
                        'table_reference' => "brownies_store_productions",
                        'table_id' => $model->id,
                        'created_by' => Auth::id()
                    ]);
                }
            }

            return $model;
        });

        return $this->response($data ? true : false);
    }

    public function prorate($totalProduction, $totalProduct)
    {
        $c = floor($totalProduction/$totalProduct);
        $d = [];
        for ($i=0; $i < $totalProduct; $i++) {
            $d[$i] = ($c);
        }

        if (fmod($totalProduction, $totalProduct)) {
            $total = array_sum($d);
            $result = $totalProduction - $total;
            for ($i=0; $i < $result ; $i++) {
                $d[$i] = $d[$i] + 1;
            }
        }

        return $d;
    }
}
