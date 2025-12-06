<?php

namespace App\Jobs\Reporting;

use App\Jobs\Job;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportTransactionUpdate extends Job
{
    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $qty = $this->data['qty'];
        $total_price = $this->data['total_price'];
        $date = $this->data['date'];
        $time = $this->data['time'];
        $branch_id = $this->data['branch_id'];

        $product_category_id = $this->data['product_category_id'];

        DB::connection('report')->transaction(function () use ($product_category_id, $date, $time, $branch_id, $total_price, $qty) {
            if ($product_category_id) {
                $cek = DB::connection('report')->table('report_transaction_currents')->select('id', 'qty', 'total_price')
                        ->where('date', $date)
                        ->where('start_time', '<=', $time)
                        ->where('end_time', '>=', $time)
                        ->where('branch_id', $branch_id);

                if ($product_category_id == 'Cust.') {
                    $cek = $cek->where('product_category_name', $product_category_id);
                } else {
                    $cek = $cek->where('product_category_id', $product_category_id);
                }

                $cek = $cek->lockForUpdate()->first();

                if ($cek) {
                    if ($total_price) {
                        DB::connection('report')->table('report_transaction_currents')->where('id', $cek->id)->update([
                            'qty' => $cek->qty + $qty,
                            'total_price' => $cek->total_price + $total_price,
                        ]);
                    } else {
                        DB::connection('report')->table('report_transaction_currents')->where('id', $cek->id)->update([
                            'qty' => $cek->qty + $qty
                        ]);
                    }
                }
            }
        });
    }
}
