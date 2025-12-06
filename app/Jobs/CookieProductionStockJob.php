<?php

namespace App\Jobs;

use App\Models\Production\CookieProduction;
use App\Services\Inventory\StockService;

class CookieProductionStockJob extends Job
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
        $created_by = $this->data['created_by'];

        $datas = CookieProduction::select('id', 'product_id')->with(['grinds'])->where([
            'date' => $date,
            'branch_id' => $branchId,
        ])->orderBy('total_target_after_remains', 'DESC')->get();
        $dateNext = date('Y-m-d', strtotime('+1 days', strtotime($date)));
        foreach ($datas as $value) {
            $total = $value->grinds ? $value->grinds->sum('total') : 0;
            $stockService = app(StockService::class);
            $stockService->create($value->product_id, $branchId, $total, 'Po Produksi Roti Manis', 'cookie_productions', $value->id, $dateNext, $created_by);
        }
    }
}
