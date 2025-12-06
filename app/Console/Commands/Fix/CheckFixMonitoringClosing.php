<?php

namespace App\Console\Commands\Fix;

use App\Models\Branch;
use App\Models\ProductCategory;
use App\Models\Reporting\MonitoringClosingSummary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckFixMonitoringClosing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:check-fix-monitoring-closing {--start_date=} {--end_date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Fix Monitoring Closing';

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
     * @return void
     */
    public function handle()
    {
        try {
            $startDate = $this->option('start_date');
            $endDate = $this->option('end_date');

            $datas = Branch::select('id')->get();
            $productCategoryTotal = ProductCategory::select('id')->count();

            $this->info('Fixing Duplication..');
            $bar = $this->output->createProgressBar(count($datas));

            $dateRange = $this->dateRange($startDate, $endDate);
            $results = [];
            foreach ($dateRange as $row) {
                $branchIds = [];
                foreach ($datas as $value) {
                    $cek = MonitoringClosingSummary::select('id')->where([
                        'branch_id' => $value->id,
                        'date' => $row,
                    ])->count();
                    if ($cek > $productCategoryTotal) {
                        $branchIds[] = $value->id;
                    }

                    $bar->advance();
                }

                if (!empty($branchIds)) {
                    $results[] = [
                        'date' => $row,
                        'branch_id' => implode(',', $branchIds),
                    ];
                }
            }

            $bar->finish();
            $this->output->newLine();
            $this->output->newLine();


            $this->info(json_encode($results));
        } catch (\Throwable $th) {
            Log::error($this->description . ': ' . $th->getMessage());
            $this->error('Error: ' . $th->getMessage());
        }
    }

    public function dateRange($from, $to)
    {
        return array_map(function($arg) {
            return date('Y-m-d', $arg);
        }, range(strtotime($from), strtotime($to), 86400));
    }
}
