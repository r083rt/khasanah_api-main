<?php

namespace App\Console\Commands\Reporting;

use App\Jobs\Reporting\ReportTransactionUpdate;
use App\Models\Order;
use App\Models\OrderProduct;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportTransactionCalculate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reporting:report-transaction-calculate {--start_date=} {--end_date=} {--branch_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update report transaction calculate';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $startDate = $this->option('start_date');
            $endDate = $this->option('end_date');
            $branchId = $this->option('branch_id');

            $update = DB::table('report_transactions')->where('date', '>=', $startDate)->where('date', '<=', $endDate);
            if ($branchId) {
                $update = $update->where('branch_id', $branchId);
            }
            $update->update([
                'qty' => 0,
                'total_price' => 0,
            ]);

            $datas = OrderProduct::select('id', 'order_id', 'product_category_id', 'qty', 'total_price', 'created_at')
                ->with(['orders:id,branch_id'])
                ->whereHas('orders', function ($query) use ($startDate, $endDate, $branchId) {
                    $query = $query
                        ->whereDate('created_at', '>=', $startDate)
                        ->whereDate('created_at', '<=', $endDate)
                        ->where('type', 'cashier')
                        ->where('status', 'completed');
                    if ($branchId) {
                        $query = $query->where('branch_id', $branchId);
                    }
                })
                ->whereNotNull('product_category_id')
                ->get();

            $this->info('Insert Data..');
            $bar = $this->output->createProgressBar(count($datas));
            $bar->start();

            foreach ($datas as $value) {
                $qty = $value->qty;
                $total_price = $value->total_price;
                $date = $value->created_at->format('Y-m-d');
                $time = $value->created_at->format('H:i:s');
                $product_category_id = $value->product_category_id;
                $branch_id = $value->orders ? $value->orders->branch_id : null;
                dispatch(new ReportTransactionUpdate([
                    'qty' => $qty,
                    'total_price' => $total_price,
                    'date' => $date,
                    'time' => $time,
                    'product_category_id' => $product_category_id,
                    'branch_id' => $branch_id,
                ]))->onQueue('report_transaction');

                $bar->advance();
            }

            $datas = OrderProduct::select('id', 'order_id', 'product_category_id', 'qty', 'total_price', 'created_at')
                ->with(['orders:id,branch_id,received_date'])
                ->whereHas('orders', function ($query) use ($startDate, $endDate, $branchId) {
                    $query = $query
                        ->whereDate('received_date', '>=', $startDate)
                        ->whereDate('received_date', '<=', $endDate)
                        ->where('type', 'order');
                    if ($branchId) {
                        $query = $query->where('branch_id', $branchId);
                    }
                })
                ->whereNotNull('product_category_id')
                ->get();

            $this->output->newLine();
            $this->info('Insert Data..');
            $bar = $this->output->createProgressBar(count($datas));
            $bar->start();

            foreach ($datas as $value) {
                $qty = $value->qty;
                $total_price = $value->total_price;
                $date = $value->orders ? $value->orders->received_date->format('Y-m-d') : null;
                $time = $value->orders ? $value->orders->received_date->format('H:i:s') : null;
                $product_category_id = $value->product_category_id;
                $branch_id = $value->orders ? $value->orders->branch_id : null;
                dispatch(new ReportTransactionUpdate([
                    'qty' => $qty,
                    'total_price' => $total_price,
                    'date' => $date,
                    'time' => $time,
                    'product_category_id' => $product_category_id,
                    'branch_id' => $branch_id,
                ]))->onQueue('report_transaction');

                $bar->advance();
            }

            $datas = Order:: select('id', 'created_at', 'branch_id')
                    ->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate)
                    ->where('type', 'cashier')
                    ->where('status', 'completed');

            if ($branchId) {
                $datas = $datas->where('branch_id', $branchId);
            }

            $datas = $datas->get();

            $this->output->newLine();
            $this->info('Insert Data..');
            $bar = $this->output->createProgressBar(count($datas));
            $bar->start();

            foreach ($datas as $value) {
                $qty = 1;
                $total_price = null;
                $date = $value->created_at->format('Y-m-d');
                $time = $value->created_at->format('H:i:s');
                $product_category_id = 'Cust.';
                $branch_id = $value->branch_id;
                dispatch(new ReportTransactionUpdate([
                    'qty' => $qty,
                    'total_price' => $total_price,
                    'date' => $date,
                    'time' => $time,
                    'product_category_id' => $product_category_id,
                    'branch_id' => $branch_id,
                ]))->onQueue('report_transaction');

                $bar->advance();
            }

            $datas = Order:: select('id', 'received_date', 'branch_id')
                    ->whereDate('received_date', '>=', $startDate)
                    ->whereDate('received_date', '<=', $endDate)
                    ->where('type', 'order');

            if ($branchId) {
                $datas = $datas->where('branch_id', $branchId);
            }

            $datas = $datas->get();

            $this->output->newLine();
            $this->info('Insert Data..');
            $bar = $this->output->createProgressBar(count($datas));
            $bar->start();

            foreach ($datas as $value) {
                $qty = 1;
                $total_price = null;
                $date = $value->received_date->format('Y-m-d');
                $time = $value->received_date->format('H:i:s');
                $product_category_id = 'Cust.';
                $branch_id = $value->branch_id;
                dispatch(new ReportTransactionUpdate([
                    'qty' => $qty,
                    'total_price' => $total_price,
                    'date' => $date,
                    'time' => $time,
                    'product_category_id' => $product_category_id,
                    'branch_id' => $branch_id,
                ]))->onQueue('report_transaction');

                $bar->advance();
            }

            $bar->finish();
            $this->output->newLine();

            $this->info('Successfully');
        } catch (\Throwable $th) {
            Log::error($this->description . ': ' . $th->getMessage());
            $this->error('Error: ' . $th->getMessage());
        }
    }
}
