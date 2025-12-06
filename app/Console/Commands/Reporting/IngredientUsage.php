<?php

namespace App\Console\Commands\Reporting;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Production\BrowniesTargetPlanWarehouse;
use App\Models\Production\CookieProduction;
use App\Models\ProductRecipe;
use App\Models\Reporting\IngredientUsage as ReportingIngredientUsage;
use App\Models\Reporting\IngredientUsageStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class IngredientUsage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reporting:ingredient-usage {--date=} {--branch_id=} {--type=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update ingredient usage';

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
            $date = $this->option('date');
            $branchId = $this->option('branch_id');
            $branchName = Branch::select('name')->find($branchId);
            $branchName = $branchName ? $branchName->name : '';

            if ($this->option('type') == 'cookie') {
                $this->cookie($date, $branchId, $branchName);
            } else {
                $this->brownies($date, $branchId, $branchName);
            }

            $this->info('Successfully');
        } catch (\Throwable $th) {
            Log::error($this->description . ': ' . $th->getMessage());
            $this->error('Error: ' . $th->getMessage());
        }
    }

    public function cookie($date, $branchId, $branchName)
    {
        $datas = CookieProduction::select('id', 'product_id')->with(['grinds'])->where('date', $date)->where('branch_id', $branchId)->get();
        $this->calculate($datas, $date, $branchId, $branchName, 'cookie');
        IngredientUsageStatus::where([
            'date' => $date,
            'branch_id' => $branchId,
        ])->update([
            'status_po_production_cookie' => 'done',
        ]);
    }

    public function brownies($date, $branchId, $branchName)
    {
        $datas = BrowniesTargetPlanWarehouse::select('id', 'product_id', 'total')->where('date', $date)->where('branch_id', $branchId)->get();
        $this->calculate($datas, $date, $branchId, $branchName, 'brownies');
        IngredientUsageStatus::where([
            'date' => $date,
            'branch_id' => $branchId,
        ])->update([
            'status_po_production_brownies' => 'done',
        ]);
    }

    public function calculate($datas, $date, $branchId, $branchName, $type)
    {
        $this->info('Insert Data..');
        $bar = $this->output->createProgressBar(count($datas));
        $bar->start();

        foreach ($datas as $row) {
            $productId = $row->product_id;
            if (isset($row->total)) {
                $total = $row->total;
            } else {
                $total = $row->grinds->sum('total');
            }

            $recipe = ProductRecipe::with(['unit', 'ingredient'])->where('product_id', $productId)->get();
            foreach ($recipe as $value) {
                $ingredientUsage = ReportingIngredientUsage::where([
                    'date' => $date,
                    'branch_id' => $branchId,
                    'ingredient_id' => $value->product_ingredient_id,
                ])->first();

                $qtyDraft = $value->measure * $total;
                if ($qtyDraft > 0) {
                    if ($ingredientUsage) {
                        $ingredientUsage->update([
                            'qty' => $ingredientUsage->qty + $qtyDraft,
                        ]);
                    } else {
                        if ($type == 'cookie') {
                            $product_category_id = 14;
                            $product_category_name = 'ROTI MANIS';
                        } else {
                            $product = Product::with(['category:id,name'])->find($productId);
                            $product_category_id = $product->product_category_id;
                            $product_category_name = $product->category ? $product->category->name : null;
                        }

                        ReportingIngredientUsage::create([
                            'date' => $date,
                            'branch_id' => $branchId,
                            'ingredient_id' => $value->product_ingredient_id,
                            'branch_name' => $branchName,
                            'code' => $value->ingredient ? $value->ingredient->code : '',
                            'name' => $value->ingredient ? $value->ingredient->name : '',
                            'qty' => $qtyDraft,
                            'unit_name' => $value->unit ? $value->unit->name : '',
                            'product_category_id' => $product_category_id,
                            'product_category_name' => $product_category_name,
                        ]);
                    }
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->output->newLine();
    }
}
