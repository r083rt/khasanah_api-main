<?php

namespace App\Console\Commands\Reporting;

use App\Jobs\Reporting\ReportTransaction as ReportingReportTransaction;
use App\Models\Branch;
use App\Models\ProductCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReportTransactionCalculateInitialFill extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reporting:report-transaction-fill {--start_date=} {--end_date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Initial fill report transaction';

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

            $datas = Branch::select('id', 'name')->get();

            if (empty($startDate) && empty($endDate)) {
                $startDate = date('Y-m-d');
                $endDate = date('Y-m-d');
            }
            $dates = date_range($startDate, $endDate);

            $this->info('Insert Data..');
            $bar = $this->output->createProgressBar(count($datas));
            $bar->start();

            foreach ($datas as $value) {
                foreach ($dates as $row) {
                    dispatch(new ReportingReportTransaction([
                        'date' => $row,
                        'branch_id' => $value->id,
                        'branch_name' => $value->name,
                    ]))->onQueue('report_transaction');
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
