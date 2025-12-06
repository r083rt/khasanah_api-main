<?php

namespace App\Jobs\Purchasing;

use App\Jobs\Job;
use Illuminate\Support\Facades\Artisan;

class Po2 extends Job
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
        Artisan::call('purchasing:po-2', [
            '--stock_opname_id' => $this->data['id'],
            '--submitted_by' => $this->data['submitted_by'],
        ]);
    }
}
