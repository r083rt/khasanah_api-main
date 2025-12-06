<?php

namespace App\Console\Commands\Fix;

use App\Models\Purchasing\PoSupplier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PoReciveNumber extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:po-supplier-receipt-number';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix Receipt Number Po Supplier';

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
            $datas = PoSupplier::select('id')->whereNull('receipt_number')->where('status_delivery', 'received')->get();

            $this->info('Processing..');
            $bar = $this->output->createProgressBar(count($datas));

            foreach ($datas as $value) {
                $value->update([
                    'receipt_number' => receipt_number_btb()
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
