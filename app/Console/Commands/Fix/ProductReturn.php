<?php

namespace App\Console\Commands\Fix;

use App\Models\Inventory\ProductReturn as InventoryProductReturn;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProductReturn extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:product-return';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix Product Return';

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
            $datas = InventoryProductReturn::whereNotNull('product_id')->get();

            $this->info('Product Return..');
            $bar = $this->output->createProgressBar(count($datas));

            foreach ($datas as $value) {
                $product = Product::find($value->product_id);
                if ($product) {
                    $value->update([
                        'price' => $product->price,
                        'total_price' => $product->price * $value->qty,
                        'hpp' => $product->price_sale,
                        'total_hpp' => $product->price_sale * $value->qty,
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
