<?php

namespace App\Imports;

use App\Models\Distribution\PoManualImport as DistributionPoManualImport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class PoManualImport implements ToCollection
{
    protected $type;
    protected $createdBy;

    /**
     * @return void
     */
    public function __construct($type, $createdBy)
    {
        $this->type = $type;
        $this->createdBy = $createdBy;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $key => $row) {
            if ($key > 0) {
                DistributionPoManualImport::create([
                    'product_code' => $row[0],
                    'qty' =>  $row[3],
                    'type' => $this->type,
                    'created_by' => $this->createdBy
                ]);
            }
        }
    }
}
