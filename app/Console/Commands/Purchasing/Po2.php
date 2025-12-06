<?php

namespace App\Console\Commands\Purchasing;

use App\Models\Inventory\ProductRecipeUnit;
use App\Models\ProductIngredient;
use App\Models\Purchasing\ForecastBuffer;
use App\Models\Purchasing\ForecastConversion;
use App\Models\Purchasing\ForecastConversionApproval;
use App\Models\Purchasing\ForecastConversionApprovalDetail;
use App\Models\Purchasing\ForecastConversionApprovalDetailBranch;
use App\Models\Purchasing\ForecastConversionDetail;
use App\Models\Purchasing\ForecastConversionSettingPo;
use App\Models\Purchasing\PoSupplier;
use App\Models\Purchasing\StockOpname;
use App\Models\Purchasing\StockOpnameIngredient;
use App\Models\Purchasing\StockOpnameIngredientDetail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class Po2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'purchasing:po-2 {--stock_opname_id=} {--submitted_by=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'purchasing po 2 ini digunakan untuk membuat po dari so';

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
            $stock_opname_id = $this->option('stock_opname_id');
            $submittedBy = $this->option('submitted_by');

            $datas = StockOpnameIngredient::where('stock_opname_id', $stock_opname_id)->where('branch_id', '!=', 1)->get();
            // $ingredients = StockOpnameIngredient::where('stock_opname_id', $stock_opname_id)->pluck('product_ingredient_id');
            // $datas = ProductIngredient::whereIn('id', $ingredients)->get();

            $stockOpname = StockOpname::select('month', 'created_at', 'year')->find($stock_opname_id);

            $this->info('Calculate Data 1...');
            $bar = $this->output->createProgressBar(count($datas));
            $bar->start();

            $nextYear = $stockOpname->year;
            $nextMonth = $stockOpname->month + 1;
            if ($nextMonth == 13) {
                $nextMonth = 1;
                $nextYear = $stockOpname->year + 1;
            }
            // $nextYear = $stockOpname->year + 1;

            //untuk proses data. po 1 next month
            $approval = ForecastConversionApproval::select('id')
                ->whereIn('status', ['approved', 'setting-po'])
                ->where('type', 'default')
                ->where('month', $nextMonth)
                ->where('year', $nextYear)
                ->first(); //harus ada adjust jika po supplier lebih dari 1
            $forecast_conversion_approval_id = $approval?->id; //approval id lama

            $settingPos = ForecastConversionSettingPo::where('forecast_conversion_approval_id', $forecast_conversion_approval_id)->get();

            //po 2
            $forecastConversionApproval = ForecastConversionApproval::create([
                'parent_id' => $forecast_conversion_approval_id,
                'stock_opname_id' => $stock_opname_id,
                'month' => $nextMonth,
                'year' => $nextYear,
                'type' => 'so',
                'status' => 'generating',
                'submitted_by' => $submittedBy,
                'approved_by' => $submittedBy,
                'submitted_at' => date('Y-m-d H:i:s'),
                'approved_at' => date('Y-m-d H:i:s'),
            ]);

            $forecastConversions = ForecastConversion::where('month', $nextMonth)->where('year', $nextYear)->get();
            foreach ($datas as $value) {
                $productIngredient = ProductIngredient::select('product_recipe_unit_id')->find($value->product_ingredient_id);
                $unit = ProductRecipeUnit::find($productIngredient->product_recipe_unit_id);
                if ($unit) {
                    if ($unit->parent_id_4) {
                        $latestUnit = $unit->parent_id_4;
                        $beforeUnit = $unit->parent_id_3;
                        $beforeUnitConversion = $unit->parent_id_4_conversion;
                    } elseif ($unit->parent_id_3) {
                        $latestUnit = $unit->parent_id_3;
                        $beforeUnit = $unit->parent_id_2;
                        $beforeUnitConversion = $unit->parent_id_3_conversion;
                    } elseif ($unit->parent_id_2) {
                        $latestUnit = $unit->parent_id_2;
                        $beforeUnit = $unit->id;
                        $beforeUnitConversion = $unit->parent_id_2_conversion;
                    } else {
                        $latestUnit = $unit->id;
                        $beforeUnit = 0;
                        $beforeUnitConversion = 0;
                    }

                    $stock_real = StockOpnameIngredientDetail::where('stock_opname_ingredient_id', $value->id)
                        ->where('product_ingredient_id', $value->product_ingredient_id)
                        ->where('product_recipe_unit_id', $latestUnit)
                        ->first();
                    $stock_real = $stock_real?->stock_real;

                    if ($beforeUnit != 0) {
                        $before_stock_real = StockOpnameIngredientDetail::where('stock_opname_ingredient_id', $value->id)
                            ->where('product_ingredient_id', $value->product_ingredient_id)
                            ->where('product_recipe_unit_id', $beforeUnit)
                            ->first();
                        if ($before_stock_real && ($beforeUnitConversion != 0 || !is_null($beforeUnitConversion))) {
                            $before_stock_real = $before_stock_real->stock_real / $beforeUnitConversion;
                        } else {
                            $before_stock_real = 0;
                        }
                    } else {
                        $before_stock_real = 0;
                    }

                    $lastStock = $stock_real + $before_stock_real;

                    $forecastConversion = $forecastConversions->where('branch_id', $value->branch_id)->first();
                    if ($forecastConversion) {
                        $forecastConversionDetail = ForecastConversionDetail::where('forecast_conversion_id', $forecastConversion->id)
                            ->where('product_ingredient_id', $value->product_ingredient_id)
                            ->first();
                        $stock_forecast = $forecastConversionDetail?->conversion_latest_rounding;

                        if ($stock_forecast - $lastStock < 0) {
                            $qtyTotal = 0;
                        } else {
                            $qtyTotal = $stock_forecast - $stock_real;
                            if ($qtyTotal < 0) {
                                $qtyTotal = 0;
                            }
                        }

                        ForecastConversionApprovalDetailBranch::create([
                            'forecast_conversion_approval_id' => $forecastConversionApproval->id,
                            'branch_id' => $value->branch_id,
                            'product_ingredient_id' => $value->product_ingredient_id,
                            'qty_forecast' => $stock_forecast,
                            'qty_so' => $stock_real,
                            'qty_total' => $qtyTotal,
                        ]);
                    }
                }

                $bar->advance();
            }

            $bar->finish();
            $this->output->newLine();

            $settingPos = ForecastConversionSettingPo::where('forecast_conversion_approval_id', $forecast_conversion_approval_id)->get();

            $ingredients = ForecastConversionApprovalDetailBranch::where('forecast_conversion_approval_id', $forecastConversionApproval->id)->pluck('product_ingredient_id')->unique();

            $this->info('Calculate Data 2...');
            $bar = $this->output->createProgressBar(count($ingredients));
            $bar->start();

            foreach ($ingredients as $row) {
                $cekSettingPo = $settingPos->where('product_ingredient_id', $row)->first();
                if ($cekSettingPo) {
                    $qty_so = ForecastConversionApprovalDetailBranch::where('forecast_conversion_approval_id', $forecastConversionApproval->id)
                        ->where('product_ingredient_id', $row)
                        ->sum('qty_total');

                    // $qty_forecast = ForecastConversionApprovalDetailBranch::where('forecast_conversion_approval_id', $forecastConversionApproval->id)
                    //     ->where('product_ingredient_id', $row)
                    //     ->sum('qty_forecast');

                    $qty_forecast = ForecastConversionDetail::select('conversion_latest_rounding')
                        ->whereIn('forecast_conversion_id', $forecastConversions->pluck('id'))
                        ->where('product_ingredient_id', $row)
                        ->sum('conversion_latest_rounding');

                    $buffer = ForecastBuffer::where('product_ingredient_id', $row)->first();
                    $totalBuffer = 0;
                    $percentageBuffer = 0;
                    if ($buffer) {
                        $percentageBuffer = $buffer->buffer;
                        $totalBuffer = round($percentageBuffer / 100 * $qty_forecast);
                    }

                    $soPusat = 0;
                    $productIngredient = ProductIngredient::select('product_recipe_unit_id')->find($row);
                    $unit = ProductRecipeUnit::find($productIngredient->product_recipe_unit_id);
                    if ($unit) {
                        if ($unit->parent_id_4) {
                            $latestUnit = $unit->parent_id_4;
                            $beforeUnit = $unit->parent_id_3;
                            $beforeUnitConversion = $unit->parent_id_4_conversion;
                        } elseif ($unit->parent_id_3) {
                            $latestUnit = $unit->parent_id_3;
                            $beforeUnit = $unit->parent_id_2;
                            $beforeUnitConversion = $unit->parent_id_3_conversion;
                        } elseif ($unit->parent_id_2) {
                            $latestUnit = $unit->parent_id_2;
                            $beforeUnit = $unit->id;
                            $beforeUnitConversion = $unit->parent_id_2_conversion;
                        } else {
                            $latestUnit = $unit->id;
                            $beforeUnit = 0;
                            $beforeUnitConversion = 0;
                        }

                        $before_stock_real = StockOpnameIngredientDetail::where('product_ingredient_id', $row)
                            ->where('stock_opname_id', $stock_opname_id)
                            ->where('branch_id', 1)
                            ->where('product_recipe_unit_id', $beforeUnit)
                            ->first();

                        if ($before_stock_real && ($beforeUnitConversion != 0 || !is_null($beforeUnitConversion))) {
                            $soPusatBefore = $before_stock_real->stock_real / $beforeUnitConversion;
                        } else {
                            $soPusatBefore = 0;
                        }

                        $soPusat = StockOpnameIngredientDetail::where('product_ingredient_id', $row)
                            ->where('stock_opname_id', $stock_opname_id)
                            ->where('branch_id', 1)
                            ->where('product_recipe_unit_id', $latestUnit)
                            ->sum('stock_real');
                    }

                    $so = $soPusat + $soPusatBefore;
                    $a = $qty_so - $so;
                    if ($a < 0) {
                        $a = 0;
                    }

                    $qty_total = $totalBuffer + $a;
                    if ($qty_total < 0) {
                        $qty_total = 0;
                    }

                    Log::info('po2: ' . json_encode([
                        'stock_opname_id' => $stock_opname_id,
                        'product_ingredient_id' => $row,
                        'qty_so' => $qty_so,
                        'qty_forecast' => $qty_forecast,
                        'percentage_buffer' => $percentageBuffer,
                        'buffer' => $totalBuffer,
                        'so_pusat_before' => $soPusatBefore,
                        'so_pusat' => $soPusat,
                        'qty' => $qty_total
                    ]));

                    $qty_total = ($qty_total - $cekSettingPo->qty_total) < 0 ? 0 : $qty_total - $cekSettingPo->qty_total;

                    ForecastConversionSettingPo::create([
                        'parent_id' => $cekSettingPo->id,
                        'forecast_conversion_approval_id' => $forecastConversionApproval->id,
                        'product_ingredient_id' => $row,
                        'brand_id' => $cekSettingPo->brand_id,
                        'barcode' => $cekSettingPo->barcode,
                        'qty_total' => $qty_total, //stok so setelah perhitungan -> ini yang ditampilin di fe
                        'qty_real' => 0, //dibuat 0 karena so
                        'qty_remaining' => 0, //dibuat 0 karena so
                        'product_recipe_unit_id' => $cekSettingPo->product_recipe_unit_id,
                        'created_by' => $submittedBy,
                    ]);
                }

                $bar->advance();
            }

            ForecastConversionApproval::where('id', $forecastConversionApproval->id)->update([
                'status' => 'approved'
            ]);

            $bar->finish();
            $this->output->newLine();

            $this->info('Successfully');
        } catch (\Throwable $th) {
            Log::error($this->description . ': ' . $th->getMessage());
            $this->error('Error: ' . $th->getMessage());
            if (isset($forecastConversionApproval)) {
                ForecastConversionApproval::where('id', $forecastConversionApproval->id)->update([
                    'status' => 'failed'
                ]);
            }
            dd($th);
        }
    }
}
