<?php

namespace App\Console\Commands\Fix;

use App\Models\Distribution\PoSjItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BranchReceiverIdPoSjItem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:branch-receiver-po-sj-item';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix branch receiver po sj item';

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
            $datas = PoSjItem::select('id', 'branch_id')->whereNull('branch_receiver_id')->get();
            $this->info('Fill data branch receiver id...');
            $bar = $this->output->createProgressBar(count($datas));

            foreach ($datas as $value) {
                $value->update([
                    'branch_receiver_id' => $value->branch_id,
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
