<?php

namespace App\Exports\Reporting;

use App\Invoice;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use App\Services\Reporting\ReportTransactionService;

class ReportTransaction implements FromView
{
    private $request;
    public function __construct($request)
    {
        $this->request = $request;
    }

    public function view(): View
    {
        $data = ReportTransactionService::getAll($this->request);
        return view('exports.reporting.report-transaction', $data);
    }
}
