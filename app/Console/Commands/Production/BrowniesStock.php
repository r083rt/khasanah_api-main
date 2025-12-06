<?php

namespace App\Console\Commands\Production;

use App\Models\Distribution\PoOrderProduct;
use App\Models\ProductStock;
use App\Services\Inventory\StockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BrowniesStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'production:brownies-stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update brownies stock';

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
            $now = date('Y-m-d');
            $date = date('Y-m-d', strtotime('-1 day', strtotime($now)));

            $datas = PoOrderProduct::select('id', 'branch_id')->with(['details'])->where('type', 'brownies')->where('available_at', $date)->get();

            $this->info('Insert Data..');
            $bar = $this->output->createProgressBar(count($datas));
            $bar->start();
            $stockService = app(StockService::class);

            foreach ($datas as $value) {
                foreach ($value->details as $row) {
                    $stock = ProductStock::where('branch_id', $value->branch_id)->where('product_id', $row->product_id)->first();
                    if ($stock) {
                        $oldStock = $stock->stock;
                        $stock->update([
                            'stock' => $oldStock + $row->qty
                        ]);

                        $stockService->createStockLog([
                            'branch_id' => $value->branch_id,
                            'product_id' =>  $row->product_id,
                            'stock' => $row->qty,
                            'stock_old' => $oldStock,
                            'from' => 'Po Brownis',
                            'table_reference' => 'po_order_products',
                            'table_id' => $value->id
                        ]);
                    } else {
                        ProductStock::create([
                            'branch_id' => $value->branch_id,
                            'product_id' => $row->product_id,
                            'stock' => $row->qty,
                        ]);

                        $stockService->createStockLog([
                            'branch_id' => $value->branch_id,
                            'product_id' =>  $row->product_id,
                            'stock' => $row->qty,
                            'stock_old' => 0,
                            'from' => 'Po Brownis',
                            'table_reference' => 'po_order_products',
                            'table_id' => $value->id
                        ]);
                    }
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
