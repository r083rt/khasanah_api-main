<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Artisan;

class IngredientUsage extends Job
{
    protected $date;
    protected $branchId;
    protected $type;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($date, $branchId, $type)
    {
        $this->date = $date;
        $this->branchId = $branchId;
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Artisan::call('reporting:ingredient-usage --date=' . $this->date . ' --branch_id=' . $this->branchId . ' --type=' . $this->type);
    }
}
