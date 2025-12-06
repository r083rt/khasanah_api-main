<?php

namespace App\Console\Commands\Production;

use App\Models\Distribution\PoOrderProduct;
use App\Models\Production\BrowniesStoreProduction;
use App\Models\ProductStock;
use App\Services\Inventory\StockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BrowniesStoreStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'production:brownies-store-stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update brownies store stock';

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

            $datas = BrowniesStoreProduction::with(['product'])->where('date', $date)->where('recipe_production', '>', 0)->get();
dd($datas->toArray());
            $this->info('Insert Data..');
            $bar = $this->output->createProgressBar(count($datas));
            $bar->start();
            $stockService = app(StockService::class);

            foreach ($datas as $value) {
                if ($value->product_id) {
                    $products = [$value->product_id];
                } else {
                    $products = $value->product_ids;
                }

                foreach ($products as $row) {
                    $stock = ProductStock::where('branch_id', $value->branch_id)->where('product_id', $row)->first();
                    if ($stock) {
                        $oldStock = $stock->stock;
                        $stock->update([
                            'stock' => $oldStock + $row->qty
                        ]);

                        $stockService->createStockLog([
                            'branch_id' => $value->branch_id,
                            'product_id' =>  $row,
                            'stock' => $row->qty,
                            'stock_old' => $oldStock,
                            'from' => 'Po Brownis',
                            'table_reference' => 'po_order_products',
                            'table_id' => $value->id
                        ]);
                    } else {
                        ProductStock::create([
                            'branch_id' => $value->branch_id,
                            'product_id' => $row,
                            'stock' => $row->qty,
                        ]);

                        $stockService->createStockLog([
                            'branch_id' => $value->branch_id,
                            'product_id' =>  $row,
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

    public function prorate()
    {

    }
}
