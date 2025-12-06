<?php

namespace App\Jobs;

class Test extends Job
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
        $this->onConnection('redis_queue_forecast_conversion');
        $this->onQueue('test');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        echo 'success: ' . $this->data;
    }
}
