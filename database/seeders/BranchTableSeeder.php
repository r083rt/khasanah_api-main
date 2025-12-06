<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            "Kantor Pusat",
            "Agus Salim",
            "Alinda",
            "Babelan",
            "Bintara",
            "Cibarusa",
            "Cibitung",
            "Cibodas",
            "Cifest",
            "Cikampek",
            "Cikarang",
            "Cilamaya",
            "Cileduk",
            "Cilengsi",
            "Cilincing",
            "Citayem",
            "Depok",
            "Gatim",
            "Jababeka",
            "Jati Mulya",
            "Jatiwangi",
            "Jatiwaringin",
            "Johar Lamaran",
            "Klender",
            "Kompas",
            "Kompas Baru",
            "Kondang Jaya",
            "Kosambi",
            "Lagoa Koja",
            "Latihan",
            "Malabar",
            "Mangun Jaya",
            "Marakas",
            "Metland",
            "Mugi",
            "Nusantara Beji",
            "Pademangan",
            "Penggilingan",
            "Perum",
            "Perumnas 1",
            "Perumnas 2 Cibodas",
            "Pondok Gede",
            "Pondok Ungu",
            "Poris",
            "Priuk",
            "Rawa Lumbu",
            "Regency",
            "Rengas Dengklok",
            "Sawangan",
            "Setu",
            "Sumur Batu Jakarta",
            "Telagasari",
            "Teluk Jambe",
            "Teluk Naga",
            "Tipar",
            "Vila Gading",
            "Villa Tangerang",
            "Wadas",
            "Warakas",
            "Warung Bongkok",
            "Wisma Asri",
            "Zamrud",
        ];

        foreach ($data as $key => $value) {
            $branch = Branch::create([
                'name' => $value,
                'code' => 'CODE' . $key,
                'zip_code' => '5325' + $key,
                'material_delivery_type' => 'daily',
                'schedule' => 'monday',
                'initial_capital' => 10000000,
                'territory_id' => 1,
                'area_id' => 1,
                'discount_active' => null
            ]);
        }

        // Branch::create([
        //     'name' => 'Cabang Jakarta',
        //     'code' => 'JKT123',
        //     'phone' => '021903492334',
        //     'zip_code' => '53256',
        //     'fund' => 0,
        //     'material_delivery_type' => 'daily',
        //     'schedule' => 'monday',
        //     'address' => 'Jakarta',
        //     'note' => 'Deket monas',
        // ]);
    }
}
