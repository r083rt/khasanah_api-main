<?php

namespace App\Console\Commands\Fix;

use App\Models\Distribution\PoOrderProduct;
use App\Models\Distribution\PoOrderProductPackaging;
use App\Models\Production\BrowniesTargetPlanWarehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BarcodePoWarehouseBrowniesBarcodePo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'production:brownies-fill-barcode-po';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update po warehouse brownies fill barcode po';

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
        try {
            $datas = BrowniesTargetPlanWarehouse::select('id', 'nomor_po')->whereNull('po_order_product_id')->whereNotNull('nomor_po')->get();
            $this->info('Insert Data..');
            $bar = $this->output->createProgressBar(count($datas));

            foreach ($datas as $value) {
                $poOrder = PoOrderProduct::where('nomor_po', $value->nomor_po)->first();
                if ($poOrder) {
                    $value->update([
                        'po_order_product_id' => $poOrder->id
                    ]);

                    $packaging = PoOrderProductPackaging::where('po_order_product_id', $poOrder->id)->first();
                    if ($packaging) {
                        $value->update([
                            'barcode_po' => $packaging->barcode
                        ]);
                    }
                }

                $bar->advance();
            }

            $bar->finish();
            $this->output->newLine();

            $this->info('Successfully');
        } catch (\Throwable $th) {
            Log::error($this->description . ': ' . $th->getMessage());
            $this->error('Error: ' . $th->getMessage());
        }
    }
}
