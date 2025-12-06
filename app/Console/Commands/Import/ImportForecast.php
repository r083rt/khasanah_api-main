<?php

namespace App\Console\Commands\Import;

use App\Models\Branch;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportForecast extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:forecast';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import forecast';

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
            $datas = DB::table('import_forecast')->get();

            $this->info('Insert Data..');
            $bar = $this->output->createProgressBar(count($datas));
            $bar->start();

            foreach ($datas as $value) {
                $branch = Branch::whereRaw('UPPER(name) = (?)', $value->name)->first();
                $product = Product::where('code', $value->product_code)->first();
                $model =  DB::table('import_forecast')->where('id', $value->id)->update([
                    'branch_id' => $branch?->id,
                    'product_id' => $product?->id,
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
