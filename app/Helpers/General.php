<?php

use App\Models\Inventory\ProductIngredientBrand;
use App\Models\Inventory\ProductRecipeUnit;
use App\Models\Purchasing\PoSupplier;
use App\Models\Purchasing\ReceivePoSupplier;
use App\Models\Purchasing\ReturnPoSupplier;

use Spatie\Browsershot\Browsershot;

if (!function_exists('tanggal_indo')) {
    function tanggal_indo($date, $day = false, $time = true)
    {
        $hari = array(
            1 => 'Senin',
            'Selasa',
            'Rabu',
            'Kamis',
            'Jumat',
            'Sabtu',
            'Minggu'
        );

        $month = array(
            1 => 'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember'
        );

        $split = explode(' ', $date);
        $date1 = explode('-', $split[0]);
        if ($time) {
            $time = isset($split[1]) ? $split[1] : null;
        } else {
            $time = null;
        }

        if ($day) {
            $num = date('N', strtotime($date));
            return  $hari[$num] . ', ' . $date1[2] . ' ' . $month[(int)$date1[1]] . ' ' . $date1[0] . ' ' . $time;
        } else {
            return $date1[2] . ' ' . $month[(int)$date1[1]] . ' ' . $date1[0] . ' ' . $time;
        }
    }
}

if (!function_exists('month_indo')) {
    function month_indo($month)
    {
        $monthIndo = array(
            1 => 'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember'
        );

        return $monthIndo[(int)$month];
    }
}

if (!function_exists('date_to_day')) {
    function date_to_day($date)
    {
        $dt = strtotime($date);
        $day = date("l", $dt);

        return strtolower($day);
    }
}

if (!function_exists('first_day')) {
    function first_day($month, $year)
    {
        $result = strtotime("{$year}-{$month}-01");
        return  date('Y-m-d', $result);
    }
}

if (!function_exists('last_day')) {
    function last_day($month, $year)
    {
        $result = strtotime("{$year}-{$month}-01");
        $result = strtotime('-1 second', strtotime('+1 month', $result));
        return date('Y-m-d', $result);
    }
}

if (!function_exists('date_from_day')) {
    function date_from_day($month, $year, $day)
    {
        $begin = new DateTime(first_day($month, $year));
        $end  = new DateTime(last_day($month, $year));
        $datas = [];
        while ($begin <= $end) {
            if ($begin->format('l') == $day) {
                $datas[] = $begin->format("Y-m-d");
            }

            $begin->modify('+1 day');
        }

        return $datas;
    }
}

if (!function_exists('day')) {
    function day()
    {
        return [
            'Sunday',
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
        ];
    }
}

if (!function_exists('date_range')) {
    function date_range($from, $to)
    {
        return array_map(function ($arg) {
            return date('Y-m-d', $arg);
        }, range(strtotime($from), strtotime($to), 86400));
    }
}

if (!function_exists('hours')) {
    function hours()
    {
        return [
            [
                'start_hour' => '06:00:00',
                'end_hour' => '06:59:59',
            ],
            [
                'start_hour' => '07:00:00',
                'end_hour' => '07:59:59',
            ],
            [
                'start_hour' => '08:00:00',
                'end_hour' => '08:59:59',
            ],
            [
                'start_hour' => '09:00:00',
                'end_hour' => '09:59:59',
            ],
            [
                'start_hour' => '10:00:00',
                'end_hour' => '10:59:59',
            ],
            [
                'start_hour' => '11:00:00',
                'end_hour' => '11:59:59',
            ],
            [
                'start_hour' => '12:00:00',
                'end_hour' => '12:59:59',
            ],
            [
                'start_hour' => '13:00:00',
                'end_hour' => '13:59:59',
            ],
            [
                'start_hour' => '14:00:00',
                'end_hour' => '14:59:59',
            ],
            [
                'start_hour' => '15:00:00',
                'end_hour' => '15:59:59',
            ],
            [
                'start_hour' => '16:00:00',
                'end_hour' => '16:59:59',
            ],
            [
                'start_hour' => '17:00:00',
                'end_hour' => '17:59:59',
            ],
            [
                'start_hour' => '18:00:00',
                'end_hour' => '18:59:59',
            ],
            [
                'start_hour' => '19:00:00',
                'end_hour' => '19:59:59',
            ],
            [
                'start_hour' => '20:00:00',
                'end_hour' => '20:59:59',
            ],
            [
                'start_hour' => '21:00:00',
                'end_hour' => '21:59:59',
            ],
            [
                'start_hour' => '22:00:00',
                'end_hour' => '22:59:59',
            ],
            [
                'start_hour' => '23:00:00',
                'end_hour' => '23:59:59',
            ],
        ];
    }
}

if (!function_exists('time')) {
    function time()
    {
        return [
            [
                'start_hour' => '06:00:00',
                'end_hour' => '06:59:59',
            ],
            [
                'start_hour' => '07:00:00',
                'end_hour' => '07:59:59',
            ],
            [
                'start_hour' => '08:00:00',
                'end_hour' => '08:59:59',
            ],
            [
                'start_hour' => '09:00:00',
                'end_hour' => '09:59:59',
            ],
            [
                'start_hour' => '10:00:00',
                'end_hour' => '10:59:59',
            ],
            [
                'start_hour' => '11:00:00',
                'end_hour' => '11:59:59',
            ],
            [
                'start_hour' => '12:00:00',
                'end_hour' => '12:59:59',
            ],
            [
                'start_hour' => '13:00:00',
                'end_hour' => '13:59:59',
            ],
            [
                'start_hour' => '14:00:00',
                'end_hour' => '14:59:59',
            ],
            [
                'start_hour' => '15:00:00',
                'end_hour' => '15:59:59',
            ],
            [
                'start_hour' => '16:00:00',
                'end_hour' => '16:59:59',
            ],
            [
                'start_hour' => '17:00:00',
                'end_hour' => '17:59:59',
            ],
            [
                'start_hour' => '18:00:00',
                'end_hour' => '18:59:59',
            ],
            [
                'start_hour' => '19:00:00',
                'end_hour' => '19:59:59',
            ],
            [
                'start_hour' => '20:00:00',
                'end_hour' => '20:59:59',
            ],
            [
                'start_hour' => '21:00:00',
                'end_hour' => '21:59:59',
            ],
            [
                'start_hour' => '22:00:00',
                'end_hour' => '22:59:59',
            ],
            [
                'start_hour' => '23:00:00',
                'end_hour' => '23:59:59',
            ],
        ];
    }
}

if (!function_exists('rounding_real_grind')) {
    function rounding_real_grind($value)
    {
        $floor = floor($value);
        $fraction = (float)substr($value - $floor, 0, 4);
        if ($fraction <= 0.50) {
            return $floor;
        } else {
            return $floor + 1;
        }
    }
}

if (!function_exists('forecast_rounding')) {
    function forecast_rounding($value)
    {
        $explode = explode('.', $value);
        if (isset($explode[1])) {
            $substr = (int) substr($explode[1], 0, 1);
            if ($substr < 5) {
                return $explode[0];
            } else {
                return $explode[0] + 1;
            }
        } else {
            return $value;
        }
    }
}

if (!function_exists('month')) {
    function month()
    {
        return [
            [
                'value' => 1,
                'name' => 'Januari',
            ],
            [
                'value' => 2,
                'name' => 'Februari',
            ],
            [
                'value' => 3,
                'name' => 'Maret',
            ],
            [
                'value' => 4,
                'name' => 'April',
            ],
            [
                'value' => 5,
                'name' => 'Mei',
            ],
            [
                'value' => 6,
                'name' => 'Juni',
            ],
            [
                'value' => 7,
                'name' => 'Juli',
            ],
            [
                'value' => 8,
                'name' => 'Agustus',
            ],
            [
                'value' => 9,
                'name' => 'September',
            ],
            [
                'value' => 10,
                'name' => 'Oktober',
            ],
            [
                'value' => 11,
                'name' => 'November',
            ],
            [
                'value' => 12,
                'name' => 'Desember',
            ],
        ];
    }
}

if (!function_exists('generate_pdf')) {
    function generate_pdf($view, $data, $fileName, $browser_output = false)
    {
        $browsershot = Browsershot::html(view($view, $data)->render())->noSandbox()->format('A4')->margins(0.5, 0, 0.5, 0, 'cm');

        if ($browser_output) {
            return $browsershot->pdf();
        }

        return $browsershot->save(storage_path($fileName));
    }
}

if (!function_exists('unit_2')) {
    function unit_2($unit)
    {
        $unit = ProductRecipeUnit::find($unit);
        return ProductRecipeUnit::find($unit->parent_id_2);
    }
}

if (!function_exists('barcode_unit_2')) {
    function barcode_unit_2($product_ingredient_id, $product_recipe_unit_id)
    {
        $barcode = ProductIngredientBrand::where('product_ingredient_id', $product_ingredient_id)
            ->where('product_recipe_unit_id', $product_recipe_unit_id)
            ->first();
        return $barcode->barcode;
    }
}

if (!function_exists('receipt_number_btb')) {
    function receipt_number_btb()
    {
        $rand = rand(100000,999999);
        $cek = ReceivePoSupplier::select('id')->where('rg_number', 'BTB-' . $rand)->first();
        if ($cek) {
            return receipt_number_btb();
        }

        return 'BTB-' . $rand;
    }
}
if (!function_exists('return_number_btb')) {
    function return_number_btb()
    {
        $rand = rand(100000,999999);
        $cek = ReturnPoSupplier::select('id')->where('rt_number', 'BTB-R-' . $rand)->first();
        if ($cek) {
            return return_number_btb();
        }

        return 'BTB-R-' . $rand;
    }
}