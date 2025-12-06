<?php

namespace App\Jobs\Purchasing;

use App\Jobs\Job;
use Illuminate\Support\Facades\Artisan;

class PoSupplier extends Job
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
        $this->onQueue('po_supplier');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Artisan::call('po:supplier', [
            '--forecast_conversion_approval_id' => $this->data['forecast_conversion_approval_id'],
        ]);
    }
}
