<?php

namespace App\Jobs;

use App\Services\Firebase\FirebaseService;

class FirebaseNotification extends Job
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
        $this->queue = 'notification';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        FirebaseService::send($this->data['title'], $this->data['content'], $this->data['token'], $this->data['datas']);
    }
}
