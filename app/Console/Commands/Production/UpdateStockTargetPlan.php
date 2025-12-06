<?php

namespace App\Console\Commands\Production;

use App\Models\Production\TargetPlan;
use App\Models\ProductStock;
use App\Services\Inventory\StockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateStockTargetPlan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'production:update-stock-target-plan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update stock target plan';

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
            $date = date('Y-m-d', strtotime('-1 days', strtotime($now)));
            $data = TargetPlan::select('branch_id', 'id')->with(['details'])->where('date', $date)->get();

            $this->info('Update stock product');
            $bar = $this->output->createProgressBar(count($data));
            $bar->start();
            $stockService = app(StockService::class);

            foreach ($data as $value) {
                foreach ($value->details as $row) {
                    $stock = ProductStock::where([
                        'branch_id' => $value->branch_id,
                        'product_id' => $row->product_id,
                    ])->first();

                    if ($stock) {
                        $oldStock = $stock->stock;
                        $stock->update([
                            'stock' => $oldStock + $row->tomorrow_plan
                        ]);

                        $stockService->createStockLog([
                            'branch_id' => $value->branch_id,
                            'product_id' =>  $row->product_id,
                            'stock' => $row->tomorrow_plan,
                            'stock_old' => $oldStock,
                            'from' => 'Target Plan',
                            'table_reference' => 'target_plans',
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
