<?php

namespace App\Console\Commands\Fix;

use App\Models\ProductStock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DuplicateStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:product-stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix product stock';

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
            $datas = DB::table('product_stocks')
                ->select('branch_id', 'product_id')
                ->groupBy('branch_id', 'product_id')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            $this->info('Fixing Duplication..');
            $bar = $this->output->createProgressBar(count($datas));

            foreach ($datas as $value) {
                $ProductStock = ProductStock::where('branch_id', $value->branch_id)->where('product_id', $value->product_id)->orderByDesc('created_at')->first();
                if ($ProductStock) {
                    $ProductStock->delete();
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
