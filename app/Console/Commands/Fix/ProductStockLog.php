<?php

namespace App\Console\Commands\Fix;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductStockLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:product-stock-log';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix Product Stock Log After';

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
            $datas = DB::table('product_stock_logs')->select('id', 'stock', 'stock_old')->whereNull('stock_after')->orderBy('id')->chunk(100000, function ($datas) {
                $this->info('Fix Product Stock Log..');
                $bar = $this->output->createProgressBar(count($datas));

                foreach ($datas as $value) {
                    DB::table('product_stock_logs')->where('id', $value->id)->update([
                        'stock_after' => $value->stock + $value->stock_old,
                    ]);

                    $bar->advance();
                }
                $bar->finish();
                $this->output->newLine();

                $this->info('Successfully');
            });

            $this->output->newLine();

            $this->info('All Done');
        } catch (\Throwable $th) {
            Log::error($this->description . ': ' . $th->getMessage());
            $this->error('Error: ' . $th->getMessage());
        }
    }
}
