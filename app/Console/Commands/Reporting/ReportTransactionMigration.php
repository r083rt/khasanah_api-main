<?php

namespace App\Console\Commands\Reporting;

use App\Models\Reporting\ReportTransaction;
use App\Models\Reporting\ReportTransactionCurrent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ReportTransactionMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reporting:report-transaction-migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Report transaction migration';

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
            DB::connection('report')
                ->table('report_transaction_currents')
                ->select('date', 'start_time', 'end_time', 'product_category_id', 'product_category_name', 'branch_id', 'branch_name', 'qty', 'total_price', 'created_at', 'updated_at')
                ->orderBy('id')
            ->chunk(500, function ($datas) {
                $data = json_decode( json_encode($datas), true);
                ReportTransaction::insert($data);
            });

            ReportTransactionCurrent::whereNotNull('id')->delete();

            $this->info('Successfully');
        } catch (\Throwable $th) {
            Log::error($this->description . ': ' . $th->getMessage());
            $this->error('Error: ' . $th->getMessage());
        }
    }
}
