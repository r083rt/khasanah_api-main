<?php

namespace App\Console\Commands\Fix;

use App\Models\Order as ModelsOrder;
use App\Models\OrderProduct;
use App\Models\Pos\OrderPayment;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Order extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:order-migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix Order Migration';

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
            $datas = DB::connection('mysql')->table('orders_backup')->get();
            $this->info('Fixing Data Order Migrations...');
            $bar = $this->output->createProgressBar(count($datas));
            foreach ($datas as $value) {
                $value = json_decode(json_encode($value), true);
                $payload = Arr::except($value, ['id']);
                $modelOrderId = DB::connection('mysql')->table('orders')->insertGetId($payload);
                $payments = DB::connection('mysql')->table('order_payments_backup')->where('order_id', $value['id'])->get();
                foreach ($payments as $row) {
                    $row = json_decode(json_encode($row), true);
                    $payload = Arr::except($row, ['id', 'order_id']);
                    $payload['order_id'] = $modelOrderId;
                    DB::connection('mysql')->table('order_payments')->insertGetId($payload);
                }
                $products = DB::connection('mysql')->table('order_products_backup')->where('order_id', $value['id'])->get();
                foreach ($products as $row) {
                    $row = json_decode(json_encode($row), true);
                    $payload = Arr::except($row, ['id', 'order_id']);
                    $payload['order_id'] = $modelOrderId;
                    DB::connection('mysql')->table('order_products')->insertGetId($payload);
                }

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
