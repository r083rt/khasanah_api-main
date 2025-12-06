<?php

namespace App\Console\Commands\Production;

use App\Models\Branch;
use App\Models\OrderProduct;
use App\Models\Production\CookieProduct;
use App\Models\Production\CookieSale as CookieSaleModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CookieSale extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'production:cookie-sale';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update cookie sale';

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
            $now = date('Y-m');
            $date = date('Y-m', strtotime('-1 month', strtotime($now)));
            $date = explode('-', $date);
            $month = $date[1];
            $year = $date[0];

            $branches = Branch::select('id')->get();
            $datas = [];
            foreach ($branches as $value) {
                $days = day();
                $dataDay = [];
                foreach ($days as $values) {
                    $dates = date_from_day($month, $year, $values);
                    $products = CookieProduct::select('product_id')->where('day', strtolower($values))->where('branch_id', $value->id)->where('is_production', 1)->pluck('product_id')->unique();
                    $productList = [];
                    foreach ($products as $row) {
                        $totalQty = OrderProduct::select('qty')
                                ->where('product_id', $row)
                                ->whereHas('orders', function ($query) use ($value, $dates) {
                                    $query = $query->whereIn(DB::raw("DATE(created_at)"), $dates)->where('branch_id', $value->id)->where('type', 'cashier');
                                })
                                ->sum('qty');
                        $qty = round($totalQty / count($dates));
                        $productList[] = [
                            'product_id' => $row,
                            'qty' => $qty,
                        ];
                    }
                    $dataDay[] = [
                        'day' => $values,
                        'products' => $productList,
                    ];
                }

                $datas[] = [
                    'branch_id' => $value->id,
                    'datas' => $dataDay
                ];
            }

            $this->info('Insert Data..');
            $bar = $this->output->createProgressBar(count($datas));
            $bar->start();

            foreach ($datas as $value) {
                foreach ($value['datas'] as $row) {
                    foreach ($row['products'] as $values) {
                        CookieSaleModel::updateOrCreate(
                            [
                                'branch_id' => $value['branch_id'],
                                'product_id' => $values['product_id'],
                                'day' => $row['day'],
                            ],
                            [
                                'branch_id' => $value['branch_id'],
                                'product_id' => $values['product_id'],
                                'day' => $row['day'],
                                'target' => $values['qty'],
                            ],
                        );
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
