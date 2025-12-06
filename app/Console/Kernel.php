<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\Menu\Management\MenuSupplier::class,
        \App\Console\Commands\Menu\Management\MenuDivision::class,

        \App\Console\Commands\Menu\Inventory\MenuMasterBahan::class,
        \App\Console\Commands\Menu\Inventory\MenuMasterPackaging::class,
        \App\Console\Commands\Menu\Inventory\MenuBrand::class,
        \App\Console\Commands\Menu\Inventory\MenuStokBahan::class,
        \App\Console\Commands\Menu\Inventory\MenuRecipeUnitChild::class,

        \App\Console\Commands\Menu\Distribution\MenuPoReceive::class,
        \App\Console\Commands\Menu\Distribution\MenuPoAdjustment::class,
        \App\Console\Commands\Menu\Distribution\MenuPoManual::class,
        \App\Console\Commands\Menu\Distribution\MenuListPo::class,
        \App\Console\Commands\Menu\Distribution\MenuPoBrownies::class,
        \App\Console\Commands\Menu\Distribution\MenuPoAdjustmentBrownies::class,

        \App\Console\Commands\Menu\MenuPoPesanan::class,
        \App\Console\Commands\Menu\MenuShipping::class,
        \App\Console\Commands\Menu\MenuSuratJalan::class,

        \App\Console\Commands\Menu\Reporting\MenuPenjualanPerJenis::class,
        \App\Console\Commands\Menu\Reporting\MenuHistoryStock::class,
        \App\Console\Commands\Menu\Reporting\MenuHistoryDistribution::class,
        \App\Console\Commands\Menu\Reporting\MenuHistoryMutasiStock::class,
        \App\Console\Commands\Menu\Reporting\MenuHistoryOrder::class,
        \App\Console\Commands\Menu\Reporting\MenuMaterialUsage::class,
        \App\Console\Commands\Menu\Reporting\MenuRealisasiPo::class,
        \App\Console\Commands\Menu\Reporting\MenuReportTransaction::class,
        \App\Console\Commands\Menu\Reporting\MenuReportPo::class,
        \App\Console\Commands\Menu\Reporting\MenuReportSupplierPerform::class,
        \App\Console\Commands\Menu\Reporting\MenuReportRecipe::class,

        \App\Console\Commands\Menu\Production\MenuBrowniesTargetPlan::class,
        \App\Console\Commands\Menu\Production\MenuBrowniesTargetPlanBufferTarget::class,
        \App\Console\Commands\Menu\Production\MenuRealGilingHistory::class,
        \App\Console\Commands\Menu\Production\MenuMonitoringSelisih::class,
        \App\Console\Commands\Menu\Production\MenuRotiManis::class,
        \App\Console\Commands\Menu\Production\MenuHistoryRetur::class,
        \App\Console\Commands\Menu\Production\MenuBrowniesStore::class,
        \App\Console\Commands\Menu\Production\MenuRealGilingHistoryBrowniesStore::class,

        \App\Console\Commands\Menu\Pos\MenuMasterExpense::class,

        \App\Console\Commands\Menu\Purchasing\MenuForecast::class,
        \App\Console\Commands\Menu\Purchasing\MenuForecastConversion::class,
        \App\Console\Commands\Menu\Purchasing\MenuApprovalForecastConversion::class,
        \App\Console\Commands\Menu\Purchasing\MenuMasterTrend::class,
        \App\Console\Commands\Menu\Purchasing\MenuMasterSupplier::class,
        \App\Console\Commands\Menu\Purchasing\MenuPoSupplier::class,
        \App\Console\Commands\Menu\Purchasing\MenuPoReceive::class,
        \App\Console\Commands\Menu\Purchasing\MenuStockOpname::class,
        \App\Console\Commands\Menu\Purchasing\MenuForecastBuffer::class,

        \App\Console\Commands\Production\BrowniesTargetSale::class,
        \App\Console\Commands\Production\CookieSale::class,
        \App\Console\Commands\Production\BrowniesStock::class,
        \App\Console\Commands\Production\UpdateStockPoProductionCookie::class,
        \App\Console\Commands\Production\GenerateBrowniesTargetPlan::class,
        \App\Console\Commands\Production\UpdateStockTargetPlan::class,
        \App\Console\Commands\Production\MonitoringClosing::class,
        \App\Console\Commands\Production\BrowniesStoreStock::class,
        \App\Console\Commands\Production\MonitoringClosingDate::class,

        \App\Console\Commands\Inventory\ProductIngredientStockDaily::class,
        \App\Console\Commands\Inventory\ProductIngredientStock::class,

        \App\Console\Commands\Backup\DB::class,
        \App\Console\Commands\Fix\BarcodePoWarehouseBrownies::class,
        \App\Console\Commands\Fix\CashierDeposit::class,
        \App\Console\Commands\Fix\DuplicateStock::class,
        \App\Console\Commands\Fix\MonitoringClosingDifferenceStock::class,
        \App\Console\Commands\Fix\PoSjItem::class,
        \App\Console\Commands\Fix\Order::class,
        \App\Console\Commands\Fix\OrderProductCategoryId::class,
        \App\Console\Commands\Fix\BranchReceiverIdPoSjItem::class,
        \App\Console\Commands\Fix\CheckFixMonitoringClosing::class,
        \App\Console\Commands\Fix\ProductReturn::class,
        \App\Console\Commands\Fix\ProductStockLog::class,
        \App\Console\Commands\Fix\BarcodePoWarehouseBrowniesBarcodePo::class,
        \App\Console\Commands\Fix\DuplicateProductStockLog::class,
        \App\Console\Commands\Fix\IngredientUnit::class,
        \App\Console\Commands\Fix\UnitDelivery::class,
        \App\Console\Commands\Fix\PoReciveNumber::class,
        \App\Console\Commands\Fix\ForecastBuffer::class,

        \App\Console\Commands\Product\FillProductAvailable::class,
        \App\Console\Commands\Ingredient\AddRecipe::class,

        \App\Console\Commands\Reporting\IngredientUsage::class,
        \App\Console\Commands\Reporting\ReportTransactionCalculateInitialFill::class,
        \App\Console\Commands\Reporting\ReportTransactionCalculate::class,
        \App\Console\Commands\Reporting\ReportTransactionMigration::class,
        \App\Console\Commands\Reporting\ReportRecipeGenerate::class,

        \App\Console\Commands\Purchasing\Forecast::class,
        \App\Console\Commands\Purchasing\PoSupplier::class,
        \App\Console\Commands\Purchasing\Po2::class,
        \App\Console\Commands\Purchasing\CekForecastGenerate::class,

        \App\Console\Commands\Import\ImportForecast::class,

        \App\Console\Commands\Job\ReservedAt::class,

        \App\Console\Commands\Test::class,

        /**
         * LaravelScout
         */
        // \Laravel\Scout\Console\ImportCommand::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('generate:brownies-target-plan')->dailyAt('16:00');
        // $schedule->command('production:update-stock-target-plan')->dailyAt('01:00');
        $schedule->command('production:brownies-target-sale')->monthlyOn(1, '00:30');
        $schedule->command('production:cookie-sale')->monthlyOn(1, '00:30');
        // $schedule->command('purchasing:forecast')->monthlyOn(1, '00:30'); //ini sementara dimatikan karena sudah ada fitur import
        // $schedule->command('production:brownies-stock')->dailyAt('01:00');
        // $schedule->command('backup:db')->dailyAt('23:15')->withoutOverlapping();

        $schedule->command('queue:retry all')->everyMinute()->withoutOverlapping();
        // $schedule->command('job:reserved-at')->everyMinute()->withoutOverlapping();
        $schedule->command('purchasing:forecast-conversion-generate-cek')->everyMinute()->withoutOverlapping();

        $schedule->command('stock:product-ingredient-daily-log')->dailyAt('23:58');
        $schedule->command('reporting:report-transaction-migration')->dailyAt('00:01');

        $schedule->exec('rm -f ' . storage_path('logs/*.log'))->daily();

        //move to cron
        // $schedule->command('production:update-stock-cookie')->dailyAt('00:05')->withoutOverlapping();
        // $schedule->command('reporting:report-transaction-fill')->dailyAt('01:00')->withoutOverlapping();
    }
}
