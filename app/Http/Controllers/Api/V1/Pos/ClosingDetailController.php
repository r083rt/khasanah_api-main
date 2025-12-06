<?php

namespace App\Http\Controllers\Api\V1\Pos;

use App\Exports\Reporting\ClosingExportBendahara;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Pos\Closing;
use App\Models\Pos\ClosingDetail;
use App\Models\Pos\ClosingExport;
use App\Models\Pos\Expense;
use App\Models\Pos\OrderPayment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

class ClosingDetailController extends Controller
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
    public function __construct(ClosingDetail $model)
    {
        $this->middleware('permission:bendahara.lihat', [
            'only' => ['index', 'listBranch']
        ]);
        $this->middleware('permission:bendahara.ubah', [
            'only' => ['store']
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
        $created_at = $request->created_at;
        $branch_id = $request->branch_id;

        $data = Closing::with(['branch:id,name', 'detail', 'reference', 'createdBy:id,name'])->whereDate('created_at', $created_at);

        if ($branch_id) {
            $data = $data->where('branch_id', $branch_id);
        }
        $data = $data->get();

        foreach ($data as $value) {
            $branch = $value->branch ? $value->branch->name : null;
            $value->central_system_reference = $this->centralSystem($value->reference, $branch);
            $value->cost_reference = $this->cost($value->reference, $branch);
            $value->sales_cash_reference = $this->salesCash($value->reference, $branch);
            $value->sales_noncash_reference = $this->salesNonCash($value->reference, $branch);
            $value->payment_cash_reference = $this->paymentCash($value->reference, $branch);
            $value->payment_noncash_reference = $this->paymentNonCash($value->reference, $branch);
            $value->dp_cash_order_reference = $this->dpOrderCash($value->reference, $branch);
            $value->dp_noncash_order_reference = $this->dpOrderNonCash($value->reference, $branch);
            $value->dp_cash_withdrawal_reference = $this->dpWithdrawalCash($value->reference, $branch);
            $value->dp_noncash_withdrawal_reference = $this->dpWithdrawalNonCash($value->reference, $branch);
            $value->refund_reference = $this->refund($value->reference, $branch);
            $value->credit_reference = $this->credit($value->reference, $branch);
        }

        ClosingExport::where('user_id', Auth::id())->delete();
        if ($data->count() > 0) {
            $dataExport = [];
            foreach ($data as $value) {
                $dataExport[] = [
                    'user_id' => Auth::id(),
                    'closing_id' => $value->id,
                    'note' => $value->note,
                    'branch_name' => $value->branch ? $value->branch->name : null,
                    'local_system' => $value->detail ? $value->detail->local_system : null,
                    'central_system' => $value->detail ? $value->detail->central_system : null,
                    'cashier_income' => $value->cashier_income,
                    'deposit_difference' => $value->detail ? $value->detail->deposit_difference : null,
                    'cost' => $value->detail ? $value->detail->cost : null,
                    'payment_cash' => $value->detail ? $value->detail->payment_cash : null,
                    'payment_noncash' => $value->detail ? $value->detail->payment_noncash : null,
                    'sales_cash' => $value->detail ? $value->detail->sales_cash : null,
                    'sales_noncash' => $value->detail ? $value->detail->sales_noncash : null,
                    'initial_capital' => $value->initial_capital,
                    'local_central_difference' => $value->detail ? $value->detail->local_central_difference : null,
                    'dp_cash_order' => $value->detail ? $value->detail->dp_cash_order : null,
                    'dp_noncash_order' => $value->detail ? $value->detail->dp_noncash_order : null,
                    'dp_cash_withdrawal' => $value->detail ? $value->detail->dp_cash_withdrawal : null,
                    'dp_noncash_withdrawal' => $value->detail ? $value->detail->dp_noncash_withdrawal : null,
                    'credit' => $value->detail ? $value->detail->credit : null,
                    'created_by_name' => $value->createdBy ? $value->createdBy->name : null,
                    'date' => $value->created_at,
                    'status' => $value->status_indo,
                ];
            }

            ClosingExport::insert($dataExport);
        }

        return $this->response($data);
    }

    /**
     * Export
     *
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        $date = $request->created_at;
        $branchId = $request->branch_id;

        $fileName = 'Bendahara-' . $date . '-' . rand(0, 1000) . '.csv';
        return Excel::download(new ClosingExportBendahara($date, $branchId), $fileName);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listBranch(Request $request)
    {
        $data = Branch::select('id', 'name', 'code')->search($request)->orderBy('name')->branch()->get();
        return $this->response($data);
    }

    /**
     * Check Admin Credential
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function checkAdmin(Request $request)
    {
        $data = $this->validate($request, [
            'email' => 'required',
            'password' => 'required',
        ]);

        $user = User::where([
            'email' => $data['email'],
        ])
        ->where('role_id', 1)
        ->where('status', 'active')
        ->first();

        if ($user) {
            if (Hash::check($data['password'], $user->password)) {
                return $this->response(true);
            }
        }

        return $this->response(false);
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
            'note' => 'nullable',
            'cashier_income' => 'required|integer',
            'cost' => 'required|integer',
            'status' => 'required|in:approved',
        ]);

        $model = Closing::findOrFail($id);

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            Closing::withoutEvents(function () use ($model, $data) {
                $model->update($data);
            });

            $closingDetail = ClosingDetail::where('closing_id', $model->id)->first();
            $depositeDifference = ($data['cashier_income'] + $data['cost'] + $closingDetail->payment_noncash + $closingDetail->sales_noncash + $closingDetail->dp_noncash_order + $closingDetail->dp_noncash_withdrawal + $closingDetail->refund) - $closingDetail->central_system;
            $closingDetail->update([
                'deposit_difference' => $depositeDifference
            ]);

            return $model;
        });

        return $this->response($data ? true : false);
    }

    /**
     * Central System
     *
     * @param collection $orderReference
     * @param string $branch
     * @return array
     */
    private function centralSystem($orderReference, $branch)
    {
        if ($orderReference) {
            $reference = $orderReference->central_system_reference;

            $data_orders = [];
            foreach ($reference['orders'] as $value) {
                $data = Order::select('id', 'total_price', 'payment_name', 'created_by', 'created_at', 'type', 'status_payment', 'status_pickup')->find($value);
                if ($data) {
                    $user = User::find($data->created_by);

                    if ($data->type == 'order' && $data->status_payment == 'not-paid' && $data->status_pickup == 'done') {
                        $note = "Pesanan(Belum ada)";
                    } else {
                        $note = $data->type == 'order' ? 'Pesanan' : 'Penjualan';
                    }
                    $data_orders[] = [
                        'order_id' => $data->id,
                        'branch_name' => $branch,
                        'note' => $note,
                        'total_price' => $data->total_price,
                        'payment_type' => $data->payment_name,
                        'created_at' => $data->created_at,
                        'created_by' => $user ? $user->name : null,
                    ];
                }
            }

            return $data_orders;
        }

        return [];
    }

    /**
     * Cost
     *
     * @param collection $orderReference
     * @param string $branch
     * @return array
     */
    private function cost($orderReference, $branch)
    {
        if ($orderReference) {
            $reference = $orderReference->cost_reference;

            $result = [];
            foreach ($reference as $value) {
                $data = Expense::select('total_cost', 'created_by', 'date', 'note')->find($value);
                if ($data) {
                    $user = User::find($data->created_by);

                    $result[] = [
                        'branch_name' => $branch,
                        'note' => $data->note,
                        'total_price' => $data->total_cost,
                        'payment_type' => 'Cash',
                        'created_at' => $data->date,
                        'created_by' => $user ? $user->name : null,
                    ];
                }
            }
            return $result;
        }

        return [];
    }

    /**
     * Payment Cash
     *
     * @param collection $orderReference
     * @param string $branch
     * @return array
     */
    private function paymentCash($orderReference, $branch)
    {
        if ($orderReference) {
            $reference = $orderReference->payment_cash_reference;

            $result = [];
            foreach ($reference as $value) {
                foreach ($value['order_payment_ids'] as $row) {
                    $data = OrderPayment::select('total_price', 'created_by', 'payment_name', 'note', 'created_at')->find($row);
                    if ($data) {
                        $user = User::find($data->created_by);

                        $result[] = [
                            'branch_name' => $branch,
                            'note' => $data->note,
                            'total_price' => $data->total_price,
                            'payment_type' => $data->payment_name,
                            'created_at' => $data->created_at,
                            'created_by' => $user ? $user->name : null,
                        ];
                    }
                }
            }
            return $result;
        }

        return [];
    }

    /**
     * Payment Non Cash
     *
     * @param collection $orderReference
     * @param string $branch
     * @return array
     */
    private function paymentNonCash($orderReference, $branch)
    {
        if ($orderReference) {
            $reference = $orderReference->payment_noncash_reference;

            $result = [];
            foreach ($reference as $value) {
                foreach ($value['order_payment_ids'] as $row) {
                    $data = OrderPayment::select('total_price', 'created_by', 'payment_name', 'note', 'created_at')->find($row);
                    if ($data) {
                        $user = User::find($data->created_by);

                        $result[] = [
                            'branch_name' => $branch,
                            'note' => $data->note,
                            'total_price' => $data->total_price,
                            'payment_type' => $data->payment_name,
                            'created_at' => $data->created_at,
                            'created_by' => $user ? $user->name : null,
                        ];
                    }
                }
            }
            return $result;
        }

        return [];
    }

    /**
     * Sales Cash
     *
     * @param collection $orderReference
     * @param string $branch
     * @return array
     */
    private function salesCash($orderReference, $branch)
    {
        if ($orderReference) {
            $reference = $orderReference->sales_cash_reference;

            $result = [];
            foreach ($reference as $value) {
                $data = Order::select('total_price', 'created_by', 'payment_name', 'created_at')->find($value);
                if ($data) {
                    $user = User::find($data->created_by);

                    $result[] = [
                        'branch_name' => $branch,
                        'note' => 'Penjualan',
                        'total_price' => $data->total_price,
                        'payment_type' => $data->payment_name,
                        'created_at' => $data->created_at,
                        'created_by' => $user ? $user->name : null,
                    ];
                }
            }
            return $result;
        }

        return [];
    }

    /**
     * Sales Non Cash
     *
     * @param collection $orderReference
     * @param string $branch
     * @return array
     */
    private function salesNonCash($orderReference, $branch)
    {
        if ($orderReference) {
            $reference = $orderReference->sales_noncash_reference;

            $result = [];
            foreach ($reference as $value) {
                $data = Order::select('total_price', 'created_by', 'payment_name', 'created_at')->find($value);
                if ($data) {
                    $user = User::find($data->created_by);

                    $result[] = [
                        'branch_name' => $branch,
                        'note' => 'Penjualan',
                        'total_price' => $data->total_price,
                        'payment_type' => $data->payment_name,
                        'created_at' => $data->created_at,
                        'created_by' => $user ? $user->name : null,
                    ];
                }
            }
            return $result;
        }

        return [];
    }

    /**
     * DP Order Cash
     *
     * @param collection $orderReference
     * @param string $branch
     * @return array
     */
    private function dpOrderCash($orderReference, $branch)
    {
        if ($orderReference) {
            $reference = $orderReference->dp_cash_order_reference;

            $result = [];
            foreach ($reference as $value) {
                $data = Order::select('id', 'total_price', 'created_by', 'payment_name', 'created_at', 'customer_id', 'date_pickup', 'received_date', 'status', 'status_payment', 'status_pickup', 'note')
                ->with(['payments' => function ($query) use ($value) {
                    $query->whereIn('id', $value['order_payment_ids']);
                }])
                ->with(['createdBy:id,name', 'customer:id,name', 'products:id,product_name,order_id,qty,total_price,discount,product_price'])
                ->find($value['order_id']);

                if ($data) {
                    $data->branch_name = $branch;
                    $data->total_item = $data->products->sum('qty');
                    $data->total_shortage = $data->total_price - $data->payments->sum('total_price');
                    $data->total_payment = $data->payments->sum('total_price');
                    $data->total_dp = $data->payments->whereIn('type', ['dp', 'paid'])->where('payment_id', 1)->sum('total_price');

                    $result[] = $data->toArray();
                }
            }
            return $result;
        }

        return [];
    }

    /**
     * DP Order Non Cash
     *
     * @param collection $orderReference
     * @param string $branch
     * @return array
     */
    private function dpOrderNonCash($orderReference, $branch)
    {
        if ($orderReference) {
            $reference = $orderReference->dp_noncash_order_reference;

            $result = [];
            foreach ($reference as $value) {
                $data = Order::select('id', 'total_price', 'created_by', 'payment_name', 'created_at', 'customer_id', 'date_pickup', 'received_date', 'status', 'status_payment', 'status_pickup', 'note')
                ->with(['payments' => function ($query) use ($value) {
                    $query->whereIn('id', $value['order_payment_ids']);
                }])
                ->with(['createdBy:id,name', 'customer:id,name', 'products:id,product_name,order_id,qty,total_price,product_price'])
                ->find($value['order_id']);

                if ($data) {
                    $data->branch_name = $branch;
                    $data->total_item = $data->products->sum('qty');
                    $data->total_shortage = $data->total_price - $data->payments->sum('total_price');
                    $data->total_payment = $data->payments->sum('total_price');
                    $data->total_dp = $data->payments->whereIn('type', ['dp', 'paid'])->where('payment_id', '!=', 1)->sum('total_price');

                    $result[] = $data->toArray();
                }
            }
            return $result;
        }

        return [];
    }

    /**
     * DP Withdrawal Cash
     *
     * @param collection $orderReference
     * @param string $branch
     * @return array
     */
    private function dpWithdrawalCash($orderReference, $branch)
    {
        if ($orderReference) {
            $reference = $orderReference->dp_cash_withdrawal_reference;

            $result = [];
            foreach ($reference as $value) {
                $data = Order::select('id', 'total_price', 'created_by', 'payment_name', 'created_at', 'customer_id', 'date_pickup', 'received_date', 'status', 'status_payment', 'status_pickup', 'note')
                ->with(['payments' => function ($query) use ($value) {
                    $query->whereIn('id', $value['order_payment_ids']);
                }])
                ->with(['createdBy:id,name', 'customer:id,name', 'products:id,product_name,order_id,qty,total_price,product_price'])
                ->find($value['order_id']);

                if ($data) {
                    $data->branch_name = $branch;
                    $data->total_item = $data->products->sum('qty');
                    $data->total_shortage = $data->total_price - $data->payments->sum('total_price');
                    $data->total_payment = $data->payments->sum('total_price');
                    $data->total_dp = $data->payments->whereIn('type', ['dp', 'paid'])->where('payment_id', 1)->sum('total_price');

                    $result[] = $data->toArray();
                }
            }
            return $result;
        }

        return [];
    }

    /**
     * DP Withdrawal Non Cash
     *
     * @param collection $orderReference
     * @param string $branch
     * @return array
     */
    private function dpWithdrawalNonCash($orderReference, $branch)
    {
        if ($orderReference) {
            $reference = $orderReference->dp_noncash_withdrawal_reference;

            $result = [];
            foreach ($reference as $value) {
                $data = Order::select('id', 'total_price', 'created_by', 'payment_name', 'created_at', 'customer_id', 'date_pickup', 'received_date', 'status', 'status_payment', 'status_pickup', 'note')
                ->with(['payments' => function ($query) use ($value) {
                    $query->whereIn('id', $value['order_payment_ids']);
                }])
                ->with(['createdBy:id,name', 'customer:id,name', 'products:id,product_name,order_id,qty,total_price,product_price'])
                ->find($value['order_id']);

                if ($data) {
                    $data->branch_name = $branch;
                    $data->total_item = $data->products->sum('qty');
                    $data->total_shortage = $data->total_price - $data->payments->sum('total_price');
                    $data->total_payment = $data->payments->sum('total_price');
                    $data->total_dp = $data->payments->whereIn('type', ['dp', 'paid'])->where('payment_id', '!=', 1)->sum('total_price');

                    $result[] = $data->toArray();
                }
            }
            return $result;
        }

        return [];
    }

    /**
     * Refund
     *
     * @param collection $orderReference
     * @param string $branch
     * @return array
     */
    private function refund($orderReference, $branch)
    {
        if ($orderReference) {
            $reference = $orderReference->refund_reference;

            $result = [];
            foreach ($reference as $value) {
                $data = Order::select('id', 'total_price', 'created_by', 'payment_name', 'created_at', 'customer_id', 'date_pickup', 'received_date', 'status', 'status_payment', 'status_pickup', 'note')
                ->with(['payments' => function ($query) use ($value) {
                    $query->whereIn('id', $value['order_payment_ids']);
                }])
                ->with(['createdBy:id,name', 'customer:id,name', 'products:id,product_name,order_id,qty,total_price,product_price'])
                ->find($value['order_id']);

                if ($data) {
                    $data->branch_name = $branch;
                    $data->total_item = $data->products->sum('qty');
                    $data->total_shortage = $data->total_price - $data->payments->sum('total_price');
                    $data->total_payment = $data->payments->sum('total_price');

                    $result[] = $data->toArray();
                }
            }
            return $result;
        }

        return [];
    }

    /**
     * Credit
     *
     * @param collection $orderReference
     * @param string $branch
     * @return array
     */
    private function credit($orderReference, $branch)
    {
        if ($orderReference) {
            $reference = $orderReference->credit_reference;

            $result = [];
            foreach ($reference as $value) {
                $data = Order::select('id', 'total_price', 'created_by', 'payment_name', 'created_at', 'customer_id', 'date_pickup', 'received_date', 'status', 'status_payment', 'status_pickup', 'note', 'pay')
                    ->with(['createdBy:id,name', 'customer:id,name', 'products:id,product_name,order_id,qty,total_price,product_price'])
                    ->find($value);

                if ($data) {
                    $data->branch_name = $branch;
                    $data->total_item = $data ? $data->products->sum('qty') : null;
                    $data->total_shortage = $data ? ($data->total_price - $data->pay) : null;
                    $data->total_payment = $data ? $data->pay : null;
                    $data->total_dp = $data->payments->whereIn('type', ['dp', 'paid'])->sum('total_price');

                    $result[] = $data->toArray();
                }
            }
            return $result;
        }

        return [];
    }
}
