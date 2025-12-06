<?php

namespace App\Jobs\Purchasing;

use App\Jobs\Job;
use App\Models\Inventory\ProductIngredientStockDailyLog;
use App\Models\Purchasing\StockOpname;
use App\Services\Inventory\IngredientStockService;

class StockOpnameStockCopy extends Job
{
    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
        $this->onQueue('so_stock');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $id = $this->data['id'];
        $status = $this->data['status'];

        $model = StockOpname::find($id);
        if ($model) {
            if ($status == 'approved' && $model->is_last_stock) {
                if ($model->month != date('m')) { //jika bulan yang diinput tidak sesuai dengan bulan berjalan
                    $day = 1;
                    $month = $model->month + 1;
                    $year = date('Y');
                    $date = $year . '-' . $month . '-' . $day;
                } else {
                    $date = $model->created_at; //bulan berjalan
                }

                if (date('Y-m-d', strtotime($date)) == date('Y-m-d')) {
                    //dihari yang sama, maka langsung update ke table utama
                    foreach ($model->stockOpnameIngredient as $value) {
                        foreach ($value->stockOpnameIngredientDetail as $row) {
                            $ingredientStockService = app(IngredientStockService::class);
                            $ingredientStockService->createFromStockOpname(
                                $row->product_ingredient_id,
                                $row->branch_id,
                                $row->product_recipe_unit_id,
                                $row->stock_real,
                                $row->id,
                                true
                            );
                        }
                    }
                } else {
                    //TODO ni harus dibuat job terpisah
                    //hari berbeda, maka update ke table utama dan ke table log
                    foreach ($model->stockOpnameIngredient as $value) {
                        foreach ($value->stockOpnameIngredientDetail as $row) {
                            $ingredientStockService = app(IngredientStockService::class);
                            $ingredientStockService->createFromStockOpname(
                                $row->product_ingredient_id,
                                $row->branch_id,
                                $row->product_recipe_unit_id,
                                $row->stock_real,
                                $row->id,
                                false
                            );

                            $dates = date_range(date('Y-m-d', strtotime($date)), date('Y-m-d')); //ini harusnya bukan dari created at tapi dari month input data + 1 mulai dari tanggal 1
                            foreach ($dates as $values) {
                                if ($values != date('Y-m-d')) {
                                    $dailyStock = ProductIngredientStockDailyLog::select('id', 'stock')->where([
                                        'branch_id' => $row->branch_id,
                                        'product_ingredient_id' => $row->product_ingredient_id,
                                        'product_recipe_unit_id' => $row->product_recipe_unit_id,
                                        'date' => $values
                                    ])->first();
                                    if ($dailyStock) {
                                        $oldStock = $dailyStock->stock;
                                        $dailyStock->update([
                                            'stock' => $oldStock + $row->stock_real,
                                        ]);
                                    } else {
                                        $oldStock = 0;
                                        ProductIngredientStockDailyLog::create([
                                            'branch_id' => $row->branch_id,
                                            'product_ingredient_id' => $row->product_ingredient_id,
                                            'product_recipe_unit_id' => $row->product_recipe_unit_id,
                                            'stock' => $row->stock_real,
                                            'date' => $values,
                                            'created_at' => date('Y-m-d H:i:s'),
                                            'updated_at' => date('Y-m-d H:i:s'),
                                        ]);
                                    }
                                    $ingredientStockService = app(IngredientStockService::class);
                                    $ingredientStockService->createStockLog([
                                        'branch_id' => $row->branch_id,
                                        'product_ingredient_id' =>  $row->product_ingredient_id,
                                        'product_recipe_unit_id' => $row->product_recipe_unit_id,
                                        'stock' => $row->stock_real,
                                        'stock_old' => $oldStock,
                                        'from' => 'Stock Opname',
                                        'table_reference' => 'stock_opname_ingredient_details',
                                        'table_id' => $row->id,
                                        'created_at' => $values,
                                        'updated_at' => $values,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
       //
    }
}
