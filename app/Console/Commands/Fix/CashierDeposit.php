<?php

namespace App\Console\Commands\Fix;

use App\Models\Pos\ClosingDetail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CashierDeposit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:cashier-deposit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix cashier deposit';

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
            $datas = ClosingDetail::get();
            $this->info('Insert Data..');
            $bar = $this->output->createProgressBar(count($datas));

            foreach ($datas as $value) {
                $value->update([
                    'cashier_deposit' => $value->payment_cash + $value->sales_cash + $value->dp_cash_order + $value->dp_cash_withdrawal
                ]);

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
