<?php

namespace App\Console\Commands\Production;

use App\Jobs\MonitoringClosingSummary\FillData;
use App\Models\Branch;
use App\Models\ProductCategory;
use App\Models\Reporting\MonitoringClosingSummary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitoringClosing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'production:monitoring-closing-summary {--start_date=} {--end_date=} {--branch_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update monitoring closing summary';

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
            $startDate = $this->option('start_date');
            $endDate = $this->option('end_date');
            $branchId = $this->option('branch_id');
            if ($branchId) {
                $branchId = explode(',', $branchId);
            } else {
                $branchId = Branch::select('id')->pluck('id')->toArray();
            }

            $dateRange = $this->dateRange($startDate, $endDate);

            foreach ($dateRange as $row) {
                foreach ($branchId as $value) {
                    dispatch(new FillData([
                        'date' => $row,
                        'branch_id' => $value,
                    ]))->onQueue('monitoring_closing');
                }
            }

            $this->info('Successfully');
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
