<?php

namespace App\Jobs\Reporting;

use App\Jobs\Job;
use Illuminate\Support\Facades\Artisan;

class ReportRecipe extends Job
{
    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
        $this->onQueue('report_recipe');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (isset($this->data['product_id'])) {
            Artisan::call('reporting:generate-recipe', [
                '--product_id' => $this->data['product_id']
            ]);
        }

        if (isset($this->data['master_packaging_id'])) {
            Artisan::call('reporting:generate-recipe', [
                '--master_packaging_id' => $this->data['master_packaging_id']
            ]);
        }
    }
}
