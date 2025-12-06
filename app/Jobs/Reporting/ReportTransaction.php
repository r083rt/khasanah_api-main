<?php

namespace App\Jobs\Reporting;

use App\Jobs\Job;
use App\Models\ProductCategory;
use App\Models\Reporting\ReportTransactionCurrent as ReportingReportTransaction;

class ReportTransaction extends Job
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
        $date = $this->data['date'];
        $branchId = $this->data['branch_id'];
        $branchName = $this->data['branch_name'];

        $categories = ProductCategory::select('id', 'name')->get();
        $hours = hours();
        $dateTime = date('Y-m-d H:i:s');

        $allDatas = [];
        foreach ($hours as $hour) {
            foreach ($categories as $key => $category) {
                if ($key == 0) {
                    $allDatas[] = [
                        'date' => $date,
                        'start_time' => $hour['start_hour'],
                        'end_time' => $hour['end_hour'],
                        'product_category_id' => null,
                        'product_category_name' => 'Cust.',
                        'branch_id' => $branchId,
                        'branch_name' => $branchName,
                        'qty' => 0,
                        'total_price' => null,
                        'created_at' => $dateTime,
                        'updated_at' => $dateTime,
                    ];
                }
                $allDatas[] = [
                    'date' => $date,
                    'start_time' => $hour['start_hour'],
                    'end_time' => $hour['end_hour'],
                    'product_category_id' => $category->id,
                    'product_category_name' => $category->name,
                    'branch_id' => $branchId,
                    'branch_name' => $branchName,
                    'qty' => 0,
                    'total_price' => 0,
                    'created_at' => $dateTime,
                    'updated_at' => $dateTime,
                ];
            }
        }

        $allDatas = collect($allDatas)->chunk(300);
        foreach ($allDatas as $payloads) {
            ReportingReportTransaction::insert($payloads->toArray());
        }
    }
}
