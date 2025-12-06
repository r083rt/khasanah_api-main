<?php

namespace App\Jobs\Purchasing;

use App\Jobs\Job;
use App\Mail\PoSupplier;
use App\Models\Purchasing\PoSupplier as PurchasingPoSupplier;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PoSupplierEmail extends Job
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
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $fileName = $this->data['file'];
        generate_pdf('pdf.po-supplier-detail', $this->data, $fileName, false);

        Mail::to($this->data['to'])->send(new PoSupplier($this->data));
        PurchasingPoSupplier::where('id', $this->data['id'])->update([
            'status' => 'success'
        ]);
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Po Supplier Email: ' . $exception->getMessage());
        PurchasingPoSupplier::where('id', $this->data['id'])->update([
            'status' => 'failed'
        ]);
    }
}
