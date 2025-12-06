<?php

namespace App\Http\Controllers\Api\V1\Distribution;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Distribution\PoManual;
use App\Models\Distribution\PoOrderIngredient;
use App\Models\Distribution\PoOrderIngredientDetail;
use App\Models\Distribution\PoOrderProduct;
use App\Models\Distribution\PoOrderProductDetail;
use App\Models\Distribution\PoSj;
use App\Models\Distribution\PoSjItem;
use App\Models\Management\Shipping;
use App\Services\Management\BranchService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PrintController extends Controller
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
    public function __construct(PoSj $model)
    {
        $this->middleware('permission:po-print-sj.lihat|po-print-sj.lihat', [
            'only' => ['index', 'listBranch', 'store']
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
        $date = $request->date;
        $data = $this->model->where('delivery_date', $date)->with(['items', 'shipping', 'shipping.tracks:id,name', 'createdBy:id,name', 'branchSender:id,name'])->orderBy('id', 'desc');
        $data = $data->search($request)->sort($request)->paginate($this->perPage($data));

        foreach ($data->items() as $values) {
            $items = PoSjItem::where('po_sj_id', $values->id)->pluck('branch_id')->unique()->values();
            $itemDetails = [];
            foreach ($items as $value) {
                $branch = Branch::where('id', $value)->first();
                $itemDetails[] = [
                    'branch_id' => $value,
                    'branch_name' => $branch ? $branch->name : '',
                    'items' => $values->items->where('branch_id', $value)->values()->toArray()
                ];
            }

            $values->item_details = $itemDetails;

            $shippings = $values->shipping->tracks;
            foreach ($shippings as $value) {
                $detailBoxs = collect($itemDetails)->where('branch_id', $value->id);
                $qty = 0;
                foreach ($detailBoxs as $row) {
                    $qty = $qty + count($row['items']);
                }
                $value->qty = $qty;
            }
        }

        return $this->response($data);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listBranch(Request $request)
    {
        $data = BranchService::getAll($request);
        return $this->response($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $id)
    {
        $data = $this->validate($request, [
            'branch_sender_id' => 'nullable'
        ]);

        if (empty($data['branch_sender_id'])) {
            $data['branch_sender_id'] = null;
        }


        $model = $this->model->with('items')->findOrFail($id);
        if ($model->status != 'product_incomplete') {
            $data['status'] = 'print';
        }

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            $model->update($data);
            foreach ($model->items as $value) {
                if ($value->type == 'po_order_product' || $value->type == 'po_brownies_product') {
                    $model = PoOrderProduct::findOrFail($value->po_id);
                    $model->update($data);
                }

                if ($value->type == 'po_order_ingredient') {
                    $model = PoOrderIngredient::findOrFail($value->po_id);
                    $model->update($data);
                }

                if ($value->type == 'po_manual_product' || $value->type == 'po_manual_ingredient') {
                    $model = PoManual::findOrFail($value->po_id);
                    $model->update($data);
                }

                if ($model->status != 'product_incomplete') {
                    $model->statusLogs()->updateOrCreate(
                        [
                            'status' => $data['status']
                        ],
                        [
                            'status' => $data['status']
                        ],
                    );
                }
            }

            return $model;
        });

        return $this->response($data);
    }
}
