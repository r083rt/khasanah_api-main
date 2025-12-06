<?php

namespace App\Console\Commands\Production;

use App\Models\Inventory\ProductStockLogTemp;
use App\Models\ProductStock;
use App\Services\Inventory\StockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateStockPoProductionCookie extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'production:update-stock-cookie';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update stock po production cookie';

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
            $datas = ProductStockLogTemp::where('date', $now)->get();

            $this->info('Update Data..');
            $bar = $this->output->createProgressBar(count($datas));
            $bar->start();
            $stockService = app(StockService::class);

            foreach ($datas as $value) {
                if ($productStock = ProductStock::where('product_id', $value->product_id)->where('branch_id', $value->branch_id)->first()) {
                    $oldStock = $productStock->stock;
                    $totalStock = $oldStock + ($value->stock);
                    $productStock->update([
                        'stock' => $totalStock
                    ]);

                    $stockService->createStockLog([
                        'branch_id' => $value->branch_id,
                        'product_id' => $value->product_id,
                        'stock' => $value->stock,
                        'stock_old' => $oldStock,
                        'from' => $value->from,
                        'table_reference' => $value->table_reference,
                        'table_id' => $value->table_id,
                        'created_by' => $value->created_by,
                    ]);
                } else {
                    $totalStock = $value->stock;
                    ProductStock::create([
                        'branch_id' => $value->branch_id,
                        'product_id' => $value->product_id,
                        'stock' => $totalStock,
                    ]);

                    $stockService->createStockLog([
                        'branch_id' => $value->branch_id,
                        'product_id' => $value->product_id,
                        'stock' => $value->stock,
                        'stock_old' => 0,
                        'from' => $value->from,
                        'table_reference' => $value->table_reference,
                        'table_id' => $value->table_id,
                        'created_by' => $value->created_by,
                    ]);
                }

                $this->checkData($value->product_id, $value->branch_id, $totalStock);
                $value->delete();

                $bar->advance();
            }

            $bar->finish();
            $this->output->newLine();

            $this->info('Successfully');
            // Log::info('Info: ' . json_encode($datas));
        } catch (\Throwable $th) {
            Log::error($this->description . ': ' . $th->getMessage());
            $this->error('Error: ' . $th->getMessage());
        }
    }

    private function checkData($product_id, $branch_id, $stock)
    {
        $productStock = ProductStock::select('stock', 'id')->where('product_id', $product_id)->where('branch_id', $branch_id)->first();
        if ($productStock->stock != $stock) {
            $productStock->update([
                'stock' => $stock
            ]);
        }
    }
}
