<?php

namespace App\Console\Commands\Fix;

use App\Models\Inventory\ProductStockLog;
use App\Models\ProductStock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DuplicateProductStockLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:product-stock-log {--date=} {--from=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix product stock log';

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
            $datas = DB::table('product_stock_logs')->select('branch_id', 'product_id', 'stock', 'table_reference', 'table_id', DB::raw('COUNT(*) AS duplicate'));

            if ($this->option('from')) {
                $datas = $datas->where('from', $this->option('from'));
            }

            if ($this->option('date')) {
                $datas = $datas->whereDate('created_at', $this->option('date'));
            } else {
                $datas = $datas->whereDate('created_at', date('Y-m-d'));
            }

            $datas = $datas->groupBy('branch_id', 'product_id', 'stock', 'table_reference', 'table_id')->having('duplicate', '>', 1)->get();

            $this->info('Fixing Duplication..');
            $bar = $this->output->createProgressBar(count($datas));

            foreach ($datas as $value) {
                $productStock = ProductStockLog::where('branch_id', $value->branch_id)
                    ->where('product_id', $value->product_id)
                    ->where('stock', $value->stock)
                    ->where('table_reference', $value->table_reference)
                    ->where('table_id', $value->table_id);

                if ($this->option('from')) {
                    $productStock = $productStock->where('from', $this->option('from'));
                }

                $productStock = $productStock->orderByDesc('stock_after')->first();

                if ($productStock) {

                    $productStock2 = ProductStockLog::where('branch_id', $value->branch_id)
                        ->where('product_id', $value->product_id)
                        ->where('stock', $value->stock)
                        ->where('table_reference', $value->table_reference)
                        ->where('table_id', $value->table_id);

                    if ($this->option('from')) {
                        $productStock2 = $productStock2->where('from', $this->option('from'));
                    }

                    $productStock2 = $productStock2->orderBy('stock_after')->first();
                    if ($productStock->stock_after != $productStock2->stock_after) {
                        $stock = ProductStock::where('branch_id', $value->branch_id)->where('product_id', $value->product_id)->first();
                        if ($stock) {
                            $max = max($productStock->stock_after, $productStock2->stock_after);
                            if ($stock->stock >= $max ) {
                                $stock->update([
                                    'stock' => $stock->stock - $productStock->stock
                                ]);
                            }
                        }
                    }

                    $productStock->delete();
                }

                $bar->advance();
            }

            $bar->finish();
            $this->output->newLine();

            $this->info('Successfully');
        } catch (\Throwable $th) {
            dd($th);
            Log::error($this->description . ': ' . $th->getMessage());
            $this->error('Error: ' . $th->getMessage());
        }
    }
}
