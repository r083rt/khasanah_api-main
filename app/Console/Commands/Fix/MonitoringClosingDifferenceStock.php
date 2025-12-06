<?php

namespace App\Console\Commands\Fix;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Reporting\MonitoringClosingDifferenceStock as ModelMonitoringClosingDifferenceStock;

class MonitoringClosingDifferenceStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:monitoring-closing-diff-stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix monitoring closing different stock';

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
            $datas = ModelMonitoringClosingDifferenceStock::get();
            $this->info('Fixing Data..');
            $bar = $this->output->createProgressBar(count($datas));

            foreach ($datas as $value) {
                $product = Product::select('code')->where('name', $value->product_name)->first();
                if ($product) {
                    $value->update([
                        'product_code' => $product->code,
                    ]);
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
