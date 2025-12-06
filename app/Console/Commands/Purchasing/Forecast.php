<?php

namespace App\Console\Commands\Purchasing;

use App\Jobs\Purchasing\ForecastConversion;
use App\Models\Branch;
use App\Models\OrderProduct;
use App\Models\ProductRecipe;
use App\Models\Purchasing\Forecast as PurchasingForecast;
use App\Models\Purchasing\ForecastBuffer;
use App\Models\Purchasing\ForecastConversion as PurchasingForecastConversion;
use App\Models\Purchasing\Trend;
use App\Services\Inventory\ProductService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Forecast extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'purchasing:forecast {--month=} {--year=} {--branch_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate forecast';

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
            $month = $this->option('month');
            $year = $this->option('year');
            $branchId = $this->option('branch_id');
            if (!$month & !$year) {
                $now = date('Y-m');
                $date = date('Y-m', strtotime('-1 month', strtotime($now)));
                $date = explode('-', $date);
                $month = $date[1];
                $year = $date[0];
            }

            $branches = Branch::select('id', 'name');
            if ($branchId) {
                $branches = $branches->where('id', $branchId);
            }
            $branches = $branches->get();

            // $trendInflasi = Trend::where('month', $month)->first();
            // $trend = $trendInflasi?->trend;
            // $inflasi = $trendInflasi?->inflation;

            $this->info('Insert Data..');
            $bar = $this->output->createProgressBar(count($branches));
            $bar->start();

            foreach ($branches as $row) {
                $cek = PurchasingForecast::where('month', $month)->where('year', $year)->where('branch_id', $row->id)->count();
                if ($cek == 0) {
                    //create forecast conversion
                    $forecastConversion = PurchasingForecastConversion::create([
                        'month' => $month,
                        'year' => $year,
                        'branch_id' => $row->id,
                    ]);

                    $service = app(ProductService::class);
                    $products = $service->getAllWhereHaveRecipe();
                    $ProductRecipes = ProductRecipe::select('master_packaging_id', 'product_id', 'product_ingredient_id', 'product_recipe_unit_id', 'measure')->whereIn('product_id', $products->pluck('id')->unique())->get();

                    foreach ($products as $value) {
                        $order = OrderProduct::where('product_id', $value->id)
                            ->whereHas('orders', function($query) use ($row) {
                                $query->where('branch_id', $row->id)->where('type', 'cashier');
                            })
                            ->whereMonth('created_at', $month)
                            ->whereYear('created_at', $year)
                            ->sum('qty');

                        // $trendTotal = $trend ? round($trend / 100 * $order) : 0;
                        // $inflasiTotal = $inflasi ? round($inflasi / 100 * $order) : 0;
                        $sale = $order;

                        $data = [
                            'month' => $month,
                            'year' => $year,
                            'branch_id' => $row->id,
                            'product_id' =>  $value->id,
                            'real_sale' => $order,
                            'sale' => $sale,
                            // 'trend' => $trendTotal,
                            // 'inflation' => $inflasiTotal,
                            'forecast_conversion_id' => $forecastConversion->id,
                        ];

                        PurchasingForecast::create($data);

                        $recipes = $ProductRecipes->where('product_id', $value->id);
                        $data['recipes'] = $recipes;

                        dispatch(new ForecastConversion($data));
                    }
                }

                $bar->advance();
            }

            $key = 'fc_show_' . $month;
            Cache::forget($key);
            $key = 'fc_show_detail_' . $month;
            Cache::forget($key);

            $bar->finish();
            $this->output->newLine();

            $this->info('Successfully');
        } catch (\Throwable $th) {
            Log::error($this->description . ': ' . $th->getMessage());
            $this->error('Error: ' . $th->getMessage());
        }
    }
}
