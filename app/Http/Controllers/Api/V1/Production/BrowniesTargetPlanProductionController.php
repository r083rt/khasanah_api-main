<?php

namespace App\Http\Controllers\Api\V1\Production;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Production\BrowniesTargetPlanWarehouseAdditionalPo;
use App\Services\Production\BrowniesTargetPlanProductionService;
use Illuminate\Support\Facades\DB;
use App\Services\Production\BrowniesTargetPlanWarehouseService;
use Illuminate\Support\Facades\Log;

class BrowniesTargetPlanProductionController extends Controller
{
    /**
     * The user repository instance.
     */
    protected $browniesTargetPlanProductionService;

    protected $browniesTargetPlanWarehouseService;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(BrowniesTargetPlanProductionService $browniesTargetPlanProductionService, BrowniesTargetPlanWarehouseService $browniesTargetPlanWarehouseService)
    {
        $this->middleware('permission:produksi-brownies-po.lihat|produksi-brownies-po.show', [
            'only' => ['index', 'show', 'listBranch']
        ]);
        $this->browniesTargetPlanProductionService = $browniesTargetPlanProductionService;
        $this->browniesTargetPlanWarehouseService = $browniesTargetPlanWarehouseService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $date = $request->date;
        $day = date_to_day($date);

        $datas = $this->browniesTargetPlanProductionService->getProduction($date, $day);

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
            'datas.*.id' => 'required|integer',
            'datas.*.barrel' => 'required|integer',
            'datas.*.barrel_conversion' => 'required|integer',
            'datas.*.edit_barrel' => 'required|integer',
            'datas.*.total_po' => 'required|integer',
        ]);

        $cek = $this->browniesTargetPlanProductionService->checkData($data['date']);
        if (empty($cek)) {
            return $this->response('Tanggal PO ' . $data['date'] . ' Produksi sudah disubmit', 'error', 422);
        }

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            $day = date_to_day($data['date']);
            foreach ($data['datas'] as $value) {
                $value['date'] = $data['date'];
                $value['day'] = $day;
                $value['product_id'] = $value['id'];
                if ($value['edit_barrel'] != $value['barrel_conversion']) {
                    $this->createAdditionalPo([
                        'date' => $data['date'],
                        'product_id' => $value['id'],
                        'total' =>  $value['edit_barrel'] * $value['barrel'],
                        'edit_barrel' => $value['edit_barrel'],
                        'type' => $value['edit_barrel'] < $value['barrel_conversion'] ? 'negative' : 'positive',
                    ]);
                }
                $model = $this->browniesTargetPlanProductionService->create($value);
            }

            return $model;
        });

        return $this->response($data ? true : false);
    }

    /**
     * Create Additional Po
     *
     * @param array $data
     */
    public function createAdditionalPo($data)
    {
        $total = $data['total'];
        $branches = Branch::select('id')->get();
        $products = [];
        foreach ($branches as $value) {
            $datas = $this->browniesTargetPlanWarehouseService->getWarehouse($data['date'], $value->id, $data['product_id']);
            if ($datas->count() > 0 && $datas->first()->po > 0) {
                $products[] = [
                    'branch_id' => $value->id,
                    'po' => $datas->first()->po,
                ];
            }
        }
        usort($products, function ($item1, $item2) {
            return $item2['po'] <=> $item1['po'];
        });

        if ($data['edit_barrel'] == 0) {
            foreach ($products as $value) {
                BrowniesTargetPlanWarehouseAdditionalPo::create([
                    'date' => $data['date'],
                    'product_id' => $data['product_id'],
                    'branch_id' => $value['branch_id'],
                    'po' => 0
                ]);
            }
        } else {
            $productFinal = $products;
            if ($data['type'] == 'positive') { //penambahan
                $totalEditBarrel = 0;
                $end = round($total / count($products));
                for ($i = 0; $i < $end; $i++) {
                    foreach ($products as $key => $value) {
                        if ($totalEditBarrel < $total) {
                            $productFinal[$key] = [
                                'branch_id' => $productFinal[$key]['branch_id'],
                                'po' => $productFinal[$key]['po'] + 1
                            ];
                            if ($i == 0) {
                                $totalEditBarrel = $totalEditBarrel + ($value['po'] + 1);
                            } else {
                                $totalEditBarrel = $totalEditBarrel + 1;
                            }
                        } else {
                            $end = 0;
                        }
                    }
                }
            } else { //pengurangan
                $totalEditBarrel = 0;
                $end = round($total / count($products));
                for ($i = 0; $i < $end; $i++) {
                    foreach ($products as $key => $value) {
                        if ($totalEditBarrel == 0 || $totalEditBarrel > $total) {
                            $productFinal[$key] = [
                                'branch_id' => $productFinal[$key]['branch_id'],
                                'po' => $productFinal[$key]['po'] - 1
                            ];
                            if ($i == 0) {
                                $totalEditBarrel = $totalEditBarrel + ($value['po'] - 1);
                            } else {
                                $totalEditBarrel = $totalEditBarrel - 1;
                            }
                        } else {
                            $end = 0;
                        }
                    }
                }
            }

            foreach ($productFinal as $value) {
                BrowniesTargetPlanWarehouseAdditionalPo::create([
                    'date' => $data['date'],
                    'product_id' => $data['product_id'],
                    'branch_id' => $value['branch_id'],
                    'po' => $value['po']
                ]);
            }
        }
    }
}
