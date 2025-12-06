<?php

namespace App\Console\Commands\Fix;

use App\Models\Distribution\PoManual;
use App\Models\Distribution\PoOrderIngredient;
use App\Models\Distribution\PoOrderProduct;
use App\Models\Distribution\PoSjItem as DistributionPoSjItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PoSjItem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:po-sj-item';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix Po SJ Item';

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
            $datas = DistributionPoSjItem::whereNull('po_date')->get();
            $this->info('Fixing Data Po Date...');
            $bar = $this->output->createProgressBar(count($datas));

            foreach ($datas as $value) {
                if ($value->type == 'po_order_product') {
                    $po = PoOrderProduct::find($value->po_id);
                    $date = $po ? date('Y-m-d', strtotime($po->created_at)) : null;
                } elseif ($value->type == 'po_order_ingredient') {
                    $po = PoOrderIngredient::find($value->po_id);
                    $date = $po ? date('Y-m-d', strtotime($po->created_at)) : null;
                } elseif ($value->type == 'po_manual_product' || $value->type == 'po_manual_ingredient') {
                    $po = PoManual::find($value->po_id);
                    $date = $po ? date('Y-m-d', strtotime($po->created_at)) : null;
                } elseif ($value->type == 'po_brownies_product') {
                    $po = PoOrderProduct::find($value->po_id);
                    $date = $po ? date('Y-m-d', strtotime($po->created_at)) : null;
                }

                if (is_null($date)) {
                    $date = date('Y-m-d', strtotime($value->created_at));
                }

                $value->update([
                    'po_date' => $date,
                ]);

                $bar->advance();
            }

            $bar->finish();
            $this->output->newLine();

            $this->info('Successfully');
            $this->output->newLine();

            $datas = DistributionPoSjItem::with('posj')->whereNull('received_date')->get();
            $this->info('Fixing Data Received Date...');
            $bar = $this->output->createProgressBar(count($datas));

            foreach ($datas as $value) {
                $date = $value->posj ? $value->posj->delivery_date : null;
                if (is_null($date)) {
                    $date = date('Y-m-d', strtotime($value->created_at));
                }
                $value->update([
                    'received_date' => $date,
                ]);

                $bar->advance();
            }

            $bar->finish();
            $this->output->newLine();

            $this->info('Successfully');
            $this->output->newLine();

            $datas = DistributionPoSjItem::with('posj')->whereNotNull('received_date')->get();
            $this->info('Fixing Data Received Date FIX...');
            $bar = $this->output->createProgressBar(count($datas));

            foreach ($datas as $value) {
                if (is_null($value->qty_real)) {
                    $value->update([
                        'received_date' => null,
                    ]);
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
