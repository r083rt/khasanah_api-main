<?php

namespace App\Console\Commands\Product;

use App\Models\Branch;
use App\Models\Menu;
use App\Models\Product;
use App\Models\ProductAvailable;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class FillProductAvailable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:fill-product-available';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fill product available to all branch';

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
            $branch = Branch::select('id')->get();

            $this->info('Fill Product to all Branch');
            $bar = $this->output->createProgressBar(count($branch));
            $bar->start();

            ProductAvailable::select('id')->delete();
            foreach ($branch as $value) {
                $product = Product::select('id')->get();
                foreach ($product as $item) {
                    ProductAvailable::create([
                            'product_id' => $item->id,
                            'branch_id' => $value->id
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
