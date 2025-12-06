<?php

namespace App\Console\Commands\Fix;

use App\Models\ProductIngredient;
use App\Models\Purchasing\ForecastBuffer as PurchasingForecastBuffer;
use App\Models\Purchasing\ForecastConversion;
use App\Models\Purchasing\ForecastConversionApprovalDetail;
use App\Models\Purchasing\ForecastConversionDetail;
use App\Models\Purchasing\PoSupplier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ForecastBuffer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:forecast-buffer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix Forecast Buffer';

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
            $datas = ForecastConversionDetail::select('id', 'conversion')->whereNull('conversion_total')->get();

            $this->info('Processing..');
            $bar = $this->output->createProgressBar(count($datas));

            foreach ($datas as $value) {
                $value->update([
                    'conversion_total' => $value->conversion,
                    'buffer' => 0,
                ]);

                $bar->advance();
            }

            $bar->finish();
            $this->output->newLine();

            $datas = ForecastConversionApprovalDetail::select('id', 'conversion')->whereNull('conversion_total')->get();

            $this->info('Processing..');
            $bar = $this->output->createProgressBar(count($datas));

            foreach ($datas as $value) {
                $value->update([
                    'conversion_total' => $value->conversion,
                    'buffer' => 0,
                ]);

                $bar->advance();
            }

            $bar->finish();
            $this->output->newLine();

            $datas = ProductIngredient::select('id')->get();

            $this->info('Processing..');
            $bar = $this->output->createProgressBar(count($datas));

            foreach ($datas as $value) {
                $model = PurchasingForecastBuffer::where('product_ingredient_id', $value->id)->first();
                if (is_null($model)) {
                    PurchasingForecastBuffer::create([
                        'product_ingredient_id' => $value->id,
                        'buffer' => 15,
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
