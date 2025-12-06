<?php

namespace App\Console\Commands\Purchasing;

use App\Jobs\Purchasing\PoSupplierEmail;
use App\Models\Purchasing\ForecastConversionApproval;
use App\Models\Purchasing\ForecastConversionSettingPo;
use App\Models\Purchasing\ForecastConversionSettingPoSupplier;
use App\Models\Purchasing\ForecastConversionSettingPoSupplierDelivery;
use App\Models\Purchasing\PoSupplier as PurchasingPoSupplier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PoSupplier extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'po:supplier {--forecast_conversion_approval_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Po Supplier';

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
            $this->info('Insert Data..');

            Log::error('Branches-first: ');
            $forecast_conversion_approval_id = $this->option('forecast_conversion_approval_id');
            $forecastConversionSettingPo = ForecastConversionSettingPo::where('forecast_conversion_approval_id', $forecast_conversion_approval_id)->get();
            $forecastConversionSettingPoIds = $forecastConversionSettingPo->pluck('id');
            $forecastConversionSettingPoSupplier = ForecastConversionSettingPoSupplier::whereIn('forecast_conversion_setting_po_id', $forecastConversionSettingPoIds)->get();
            $forecastConversionSettingPoSupplierIds = $forecastConversionSettingPoSupplier->pluck('id');
            $upplierIds = $forecastConversionSettingPoSupplier->pluck('purchasing_supplier_id')->unique();
            $datas = ForecastConversionSettingPoSupplierDelivery::whereIn('forecast_conversion_setting_po_supplier_id', $forecastConversionSettingPoSupplierIds)->get();

            $suppliers = [];
            foreach ($upplierIds as $supplierId) {
                $supplierSettingPoIds = $forecastConversionSettingPoSupplier->where('purchasing_supplier_id', $supplierId)->pluck('id');
                $datasDate = $datas->whereIn('forecast_conversion_setting_po_supplier_id', $supplierSettingPoIds)->where('qty', '>', 0);
                $groupDates = $datasDate->groupBy('date');
                // dd($datasDate->groupBy('date')->toArray());

                $settingPoIds = $forecastConversionSettingPoSupplier->where('purchasing_supplier_id', $supplierId);
                // dd($settingPoIds->toArray());

                $dates = [];
                foreach ($groupDates as $key => $row) {

                    $productIngredients = [];
                    // $datasDatePerDate = $datas->whereIn('forecast_conversion_setting_po_supplier_id', $settingPoIds)->where('date', $key)->groupBy('branch');
                    $datasDatePerDate = $datas->where('date', $key)->groupBy('branch');
                    
                    foreach($datasDatePerDate as $branch => $dataperbranch){
                        foreach ($settingPoIds as $settingPoId) {
                            // dd($settingPoId->toArray());
                            // $datasDatePerDate = $datas->whereIn('forecast_conversion_setting_po_supplier_id', $settingPoId->id)->where('date', $key)->first();
                                Log::error('Branches: ' . $branch );
                            
                                $settingPo = $forecastConversionSettingPo->where('id', $settingPoId->forecast_conversion_setting_po_id)->first();

                                $row_branch = $datas->whereIn('forecast_conversion_setting_po_supplier_id', $settingPoId->id)->where('date', $key)->where('branch', $branch)->first();

                                $productIngredients[] = [
                                    'product_ingredient_id' => $settingPo->product_ingredient_id,
                                    'product_recipe_unit_id' => $settingPo->product_recipe_unit_id,
                                    'brand_id' => $settingPo->brand_id,
                                    'barcode' => $settingPo->barcode,
                                    'qty' => $row_branch?->qty,
                                ];
                            

                            // if ($datasDatePerDate) {
                            //     // dd($datasDatePerDate->toArray());

                            //     $settingPo = $forecastConversionSettingPo->where('id', $settingPoId->forecast_conversion_setting_po_id)->first();
                            //     $productIngredients[] = [
                            //         'product_ingredient_id' => $settingPo->product_ingredient_id,
                            //         'product_recipe_unit_id' => $settingPo->product_recipe_unit_id,
                            //         'brand_id' => $settingPo->brand_id,
                            //         'barcode' => $settingPo->barcode,
                            //         'qty' => $datasDatePerDate->qty,
                            //     ];
                            // }
                        }

                        $dates[] = [
                            'date' => $key,
                            'branch' => $branch,
                            'ingredients' => $productIngredients
                        ];
                    }
                }
                // dd($dates);

                $suppliers[] = [
                    'purchasing_supplier_id' => $supplierId,
                    'dates' => $dates
                ];
            }

            $forecastConversionApproval = ForecastConversionApproval::find($forecast_conversion_approval_id);
            foreach ($suppliers as $supplier) {
                foreach ($supplier['dates'] as $value) {
                    $month = $forecastConversionApproval?->month;
                    $year = $forecastConversionApproval?->year;
                    $poSupplier = PurchasingPoSupplier::create([
                        'day' => $value['date'],
                        'month' => $month,
                        'year' => $year,
                        'date' => $year. '-' . $month . '-' . $value['date'],
                        'purchasing_supplier_id' => $supplier['purchasing_supplier_id'],
                        'forecast_conversion_approval_id' => $forecast_conversion_approval_id,
                        'status' => 'sent',
                        'branch_id' => $value['branch']
                    ]);

                    foreach ($value['ingredients'] as $row) {
                        $poSupplier->poSupplierDetails()->create([
                            'po_supplier_id' => $poSupplier->id,
                            'product_ingredient_id' => $row['product_ingredient_id'],
                            'product_recipe_unit_id' => $row['product_recipe_unit_id'],
                            'brand_id' => $row['brand_id'],
                            'qty' => $row['qty'],
                            'barcode' => $row['barcode'],
                        ]);
                    }
                }
            }

            if (env('SEND_MAIL')) {
                $poSuppliers = PurchasingPoSupplier::where('forecast_conversion_approval_id', $forecast_conversion_approval_id)->get();
                foreach ($poSuppliers as $model) {
                    $file = 'app/po_supplier/' . $model->po_number . '.pdf';
                    $data = [
                        'file' => $file,
                        'po_number' => $model->po_number,
                        'month' => month_indo($model->month),
                        'year' => $model->year,
                        'date' => tanggal_indo($model->date, false, false),
                        'supplier' => $model->purchasingSupplier?->name,
                        'file_path' => storage_path($file),
                        'to' => $model->purchasingSupplier?->email,
                        'id' => $model->id,
                        'branch' => $model->branch?->name
                    ];

                    $ingredients = [];
                    foreach ($model->poSupplierDetails as $value) {
                        $datas = [
                            'ingredient' => $value->productIngredient?->name,
                            'brand' => $value->brand?->name,
                            'barcode' => $value->barcode,
                            'qty' => $value->qty,
                            'unit' => $value->productRecipeUnit?->name,
                        ];

                        $ingredients[] = $datas;
                    }

                    $data['ingredients'] = $ingredients;

                    dispatch(new PoSupplierEmail($data));
                }
            }

            $this->info('Successfully');
        } catch (\Throwable $th) {
            Log::error($this->description . ': ' . $th->getMessage());
            $this->error('Error: ' . $th->getMessage());
        }
    }
}
