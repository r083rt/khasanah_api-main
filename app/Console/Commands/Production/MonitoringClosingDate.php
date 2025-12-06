<?php

namespace App\Console\Commands\Production;

use App\Jobs\MonitoringClosingSummary\FillData;
use App\Models\Branch;
use App\Models\ProductCategory;
use App\Models\Reporting\MonitoringClosingSummary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitoringClosingDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'production:monitoring-closing-summary-date {--date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update monitoring closing summary date';

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
            $date = $this->option('date');
            $productCategories = ProductCategory::select('id', 'name')->get();
            $branches = Branch::get();

            $result = [];
            foreach ($branches as $value) {
                foreach ($productCategories as $category) {
                    $result[] = [
                        'product_category_id' => $category->id,
                        'type' => $category->name,
                        'first_stock' => 0,
                        'in' => 0,
                        'sale' => 0,
                        'order' => 0,
                        'return' => 0,
                        'transfer_stock' =>0,
                        'remains_closing' => 0,
                        'difference' => 0,
                        'branch_id' => $value->id,
                        'date' => $date,
                        'hpp_total' => 0,
                    ];
                }
            }

            MonitoringClosingSummary::insert($result);

            $this->info('Successfully');
        } catch (\Throwable $th) {
            Log::error($this->description . ': ' . $th->getMessage());
            $this->error('Error: ' . $th->getMessage());
        }
    }
}
