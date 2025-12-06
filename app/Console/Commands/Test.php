<?php

namespace App\Console\Commands;

use App\Jobs\Purchasing\PoSupplierEmail;
use App\Jobs\Test as JobsTest;
use App\Mail\PoSupplier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:queue-redis';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Queue Redis';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // dispatch(new PoSupplierEmail(['file' => 'app/po_supplier/123456.pdf']));
        dispatch(new JobsTest('Fajar Sujito'));
    }
}
