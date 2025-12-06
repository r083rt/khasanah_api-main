<?php

namespace App\Console\Commands\Fix;

use App\Models\OrderProduct;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderProductCategoryId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:order-product-category';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix Order Product Category Id';

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
            $this->info('Fill Data Order Products...');

            DB::table('order_products')->select('id', 'product_id', 'product_code', 'product_name')->whereNull('product_category_id')->orderBy('id')->chunk(500, function ($datas) {
                $product = Product::select('id', 'product_category_id', 'name', 'code')->get();

                foreach ($datas as $value) {
                    $checkProduct = $product->where('id', $value->product_id)->first();
                    if ($checkProduct) {
                        OrderProduct::where('id', $value->id)->update([
                            'product_category_id' => $checkProduct->product_category_id
                        ]);
                    } else {
                        $checkProduct = $product->where('code', $value->product_code)->first();
                        if ($checkProduct) {
                            OrderProduct::where('id', $value->id)->update([
                                'product_category_id' => $checkProduct->product_category_id
                            ]);
                        } else {
                            $checkProduct = $product->where('name', $value->product_name)->first();
                            if ($checkProduct) {
                                OrderProduct::where('id', $value->id)->update([
                                    'product_category_id' => $checkProduct->product_category_id
                                ]);
                            }
                        }
                    }
                }
            });

            $this->output->newLine();

            $this->info('Successfully');
        } catch (\Throwable $th) {
            Log::error($this->description . ': ' . $th->getMessage());
            $this->error('Error: ' . $th->getMessage());
        }
    }
}
