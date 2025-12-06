<?php

namespace App\Console\Commands\Production;

use App\Models\Branch;
use App\Services\Production\BrowniesTargetPlanService;
use Illuminate\Console\Command;

class GenerateBrowniesTargetPlan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:brownies-target-plan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Brownies Target Plan';

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
        $target = new BrowniesTargetPlanService();
        $date = date('Y-m-d');
        $productCategoryId = config('production.brownies_target_product_category_id');

        $branches = Branch::select('id')->get();
        foreach ($branches as $value) {
            $productCategoryId = config('production.brownies_target_product_category_id');
            foreach ($productCategoryId as $item) {
                $datas = $target->getData($date, $item, $value->id);
                $products = [];
                foreach ($datas['details'] as $key => $item) {
                    $products[$key]['product_id'] = $item->product_id;
                    $products[$key]['product_category_id'] = $item->product_category_id;
                    $products[$key]['first_stock'] = $item->first_stock;
                    $products[$key]['realization'] = $item->realization;
                    $products[$key]['four_oclock'] = $item->four_oclock;
                    $products[$key]['tomorrow_plan'] = $item->tomorrow_plan;
                    $products[$key]['current_stock'] = $item->current_stock;
                }

                $data = [
                    'date' => $date,
                    'branch_id' => $value->id,
                    'products' => $products
                ];
                $target->store($data);
            }
        }
    }
}
