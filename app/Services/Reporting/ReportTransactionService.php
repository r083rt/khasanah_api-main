<?php

namespace App\Services\Reporting;

use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\ProductCategory;
use App\Models\Reporting\ReportTransaction;
use App\Models\Reporting\ReportTransactionCurrent;

class ReportTransactionService
{
    /**
     * Get sale service all
     */
    public static function getAll($request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $branchId = empty($request->branch_id) ? null : $request->branch_id;

        if ($startDate == date('Y-m-d')) {
            $data = ReportTransactionCurrent::select('id', 'date', 'start_time', 'end_time', 'product_category_id', 'product_category_name', 'branch_name', 'branch_id', 'qty', 'total_price')
                ->where('date', '>=', $startDate)
                ->where('date', '<=', $endDate)
                ->orderBy('date');

            if ($branchId) {
                $data = $data->where('branch_id', $branchId);
            }

            $data = $data->get();

            $dates = date_range($startDate, $endDate);
            $times = hours();
            $categories = ProductCategory::select('name', 'id')->get()->keyBy('id');
            $raw = [];
            $branches = [];
            $available_products = [];
            foreach ($data as $item) {
                $raw[$item->date][$item->branch_id][$item->start_time . '_' . $item->end_time][$item->product_category_id ?? 'CUST'] = [
                    'qty' => $item->qty,
                    'total_price' => $item->total_price
                ];
                if ($item->product_category_id && !in_array($item->product_category_id, $available_products[$item->date] ?? [])) {
                    $available_products[$item->date][] = $item->product_category_id;
                    sort($available_products[$item->date]);
                }

                if ($item->branch_id && !isset($branches[$item->branch_id])) {
                    $branches[$item->branch_id] = $item->branch_name;
                }
            }

            $branches = collect($branches)
                ->sort()
                ->toArray();
            $branches_ids = array_keys($branches);

            $result = [];
            foreach ($dates as $date) {
                foreach ($times as $time) {
                    if (isset($available_products[$date])) {
                        foreach ($available_products[$date] as $key => $product_id) {
                            if ($key === 0) {
                                $transactions = [];
                                foreach ($branches_ids as $branch_id) {
                                    $transactions[] = [
                                        'qty' => $raw[$date][$branch_id][$time['start_hour'] . '_' . $time['end_hour']]['CUST']['qty'] ?? 0,
                                        'total_price' => null
                                    ];
                                }
                                $result[] = [
                                    'show_date' => true,
                                    'show_time' => true,
                                    'date' => $date,
                                    'start_time' => $time['start_hour'],
                                    'end_time' => $time['end_hour'],
                                    'product_category_name' => 'Cust.',
                                    'transactions' => $transactions
                                ];
                            }

                            $transactions = [];
                            foreach ($branches_ids as $branch_id) {
                                $transactions[] = [
                                    'qty' => $raw[$date][$branch_id][$time['start_hour'] . '_' . $time['end_hour']][$product_id]['qty'] ?? 0,
                                    'total_price' => $raw[$date][$branch_id][$time['start_hour'] . '_' . $time['end_hour']][$product_id]['total_price'] ?? 0,
                                ];
                            }
                            $result[] = [
                                'show_date' => true,
                                'show_time' => true,
                                'date' => $date,
                                'start_time' => $time['start_hour'],
                                'end_time' => $time['end_hour'],
                                'product_category_name' => $categories[$product_id]->name ?? null,
                                'transactions' => $transactions
                            ];
                        }
                    }
                }
            }
        } else if ($endDate == date('Y-m-d')) {
            $data = ReportTransaction::select('id', 'date', 'start_time', 'end_time', 'product_category_id', 'product_category_name', 'branch_name', 'branch_id', 'qty', 'total_price')
                ->where('date', '>=', $startDate)
                ->where('date', '<=', $endDate)
                ->orderBy('date');

            if ($branchId) {
                $data = $data->where('branch_id', $branchId);
            }

            $data = $data->get();

            $dates = date_range($startDate, $endDate);
            $times = hours();
            $categories = ProductCategory::select('name', 'id')->get()->keyBy('id');
            $raw = [];
            $branches = [];
            $available_products = [];
            foreach ($data as $item) {
                $raw[$item->date][$item->branch_id][$item->start_time . '_' . $item->end_time][$item->product_category_id ?? 'CUST'] = [
                    'qty' => $item->qty,
                    'total_price' => $item->total_price
                ];
                if ($item->product_category_id && !in_array($item->product_category_id, $available_products[$item->date] ?? [])) {
                    $available_products[$item->date][] = $item->product_category_id;
                    sort($available_products[$item->date]);
                }

                if ($item->branch_id && !isset($branches[$item->branch_id])) {
                    $branches[$item->branch_id] = $item->branch_name;
                }
            }

            $branches = collect($branches)
                ->sort()
                ->toArray();
            $branches_ids = array_keys($branches);

            $result = [];
            foreach ($dates as $date) {
                foreach ($times as $time) {
                    if (isset($available_products[$date])) {
                        foreach ($available_products[$date] as $key => $product_id) {
                            if ($key === 0) {
                                $transactions = [];
                                foreach ($branches_ids as $branch_id) {
                                    $transactions[] = [
                                        'qty' => $raw[$date][$branch_id][$time['start_hour'] . '_' . $time['end_hour']]['CUST']['qty'] ?? 0,
                                        'total_price' => null
                                    ];
                                }
                                $result[] = [
                                    'show_date' => true,
                                    'show_time' => true,
                                    'date' => $date,
                                    'start_time' => $time['start_hour'],
                                    'end_time' => $time['end_hour'],
                                    'product_category_name' => 'Cust.',
                                    'transactions' => $transactions
                                ];
                            }

                            $transactions = [];
                            foreach ($branches_ids as $branch_id) {
                                $transactions[] = [
                                    'qty' => $raw[$date][$branch_id][$time['start_hour'] . '_' . $time['end_hour']][$product_id]['qty'] ?? 0,
                                    'total_price' => $raw[$date][$branch_id][$time['start_hour'] . '_' . $time['end_hour']][$product_id]['total_price'] ?? 0,
                                ];
                            }
                            $result[] = [
                                'show_date' => true,
                                'show_time' => true,
                                'date' => $date,
                                'start_time' => $time['start_hour'],
                                'end_time' => $time['end_hour'],
                                'product_category_name' => $categories[$product_id]->name ?? null,
                                'transactions' => $transactions
                            ];
                        }
                    }
                }
            }

            $data = ReportTransactionCurrent::select('id', 'date', 'start_time', 'end_time', 'product_category_id', 'product_category_name', 'branch_name', 'branch_id', 'qty', 'total_price')
                ->where('date', '>=', $endDate)
                ->where('date', '<=', $endDate)
                ->orderBy('date');

            if ($branchId) {
                $data = $data->where('branch_id', $branchId);
            }

            $data = $data->get();

            $dates = date_range($endDate, $endDate);
            $times = hours();
            $categories = ProductCategory::select('name', 'id')->get()->keyBy('id');
            $raw = [];
            $branches = [];
            $available_products = [];
            foreach ($data as $item) {
                $raw[$item->date][$item->branch_id][$item->start_time . '_' . $item->end_time][$item->product_category_id ?? 'CUST'] = [
                    'qty' => $item->qty,
                    'total_price' => $item->total_price
                ];
                if ($item->product_category_id && !in_array($item->product_category_id, $available_products[$item->date] ?? [])) {
                    $available_products[$item->date][] = $item->product_category_id;
                    sort($available_products[$item->date]);
                }

                if ($item->branch_id && !isset($branches[$item->branch_id])) {
                    $branches[$item->branch_id] = $item->branch_name;
                }
            }

            $branches = collect($branches)
                ->sort()
                ->toArray();
            $branches_ids = array_keys($branches);

            $result2 = [];
            foreach ($dates as $date) {
                foreach ($times as $time) {
                    if (isset($available_products[$date])) {
                        foreach ($available_products[$date] as $key => $product_id) {
                            if ($key === 0) {
                                $transactions = [];
                                foreach ($branches_ids as $branch_id) {
                                    $transactions[] = [
                                        'qty' => $raw[$date][$branch_id][$time['start_hour'] . '_' . $time['end_hour']]['CUST']['qty'] ?? 0,
                                        'total_price' => null
                                    ];
                                }
                                $result2[] = [
                                    'show_date' => true,
                                    'show_time' => true,
                                    'date' => $date,
                                    'start_time' => $time['start_hour'],
                                    'end_time' => $time['end_hour'],
                                    'product_category_name' => 'Cust.',
                                    'transactions' => $transactions
                                ];
                            }

                            $transactions = [];
                            foreach ($branches_ids as $branch_id) {
                                $transactions[] = [
                                    'qty' => $raw[$date][$branch_id][$time['start_hour'] . '_' . $time['end_hour']][$product_id]['qty'] ?? 0,
                                    'total_price' => $raw[$date][$branch_id][$time['start_hour'] . '_' . $time['end_hour']][$product_id]['total_price'] ?? 0,
                                ];
                            }
                            $result2[] = [
                                'show_date' => true,
                                'show_time' => true,
                                'date' => $date,
                                'start_time' => $time['start_hour'],
                                'end_time' => $time['end_hour'],
                                'product_category_name' => $categories[$product_id]->name ?? null,
                                'transactions' => $transactions
                            ];
                        }
                    }
                }
            }

            $result = array_merge($result, $result2);
        } else {
            $data = ReportTransaction::select('id', 'date', 'start_time', 'end_time', 'product_category_id', 'product_category_name', 'branch_name', 'branch_id', 'qty', 'total_price')
                ->where('date', '>=', $startDate)
                ->where('date', '<=', $endDate)
                ->orderBy('date');

            if ($branchId) {
                $data = $data->where('branch_id', $branchId);
            }

            $data = $data->get();

            $dates = date_range($startDate, $endDate);
            $times = hours();
            $categories = ProductCategory::select('name', 'id')->get()->keyBy('id');
            $raw = [];
            $branches = [];
            $available_products = [];
            foreach ($data as $item) {
                $raw[$item->date][$item->branch_id][$item->start_time . '_' . $item->end_time][$item->product_category_id ?? 'CUST'] = [
                    'qty' => $item->qty,
                    'total_price' => $item->total_price
                ];
                if ($item->product_category_id && !in_array($item->product_category_id, $available_products[$item->date] ?? [])) {
                    $available_products[$item->date][] = $item->product_category_id;
                    sort($available_products[$item->date]);
                }

                if ($item->branch_id && !isset($branches[$item->branch_id])) {
                    $branches[$item->branch_id] = $item->branch_name;
                }
            }

            $branches = collect($branches)
                ->sort()
                ->toArray();
            $branches_ids = array_keys($branches);

            $result = [];
            foreach ($dates as $date) {
                foreach ($times as $time) {
                    if (isset($available_products[$date])) {
                        foreach ($available_products[$date] as $key => $product_id) {
                            if ($key === 0) {
                                $transactions = [];
                                foreach ($branches_ids as $branch_id) {
                                    $transactions[] = [
                                        'qty' => $raw[$date][$branch_id][$time['start_hour'] . '_' . $time['end_hour']]['CUST']['qty'] ?? 0,
                                        'total_price' => null
                                    ];
                                }
                                $result[] = [
                                    'show_date' => true,
                                    'show_time' => true,
                                    'date' => $date,
                                    'start_time' => $time['start_hour'],
                                    'end_time' => $time['end_hour'],
                                    'product_category_name' => 'Cust.',
                                    'transactions' => $transactions
                                ];
                            }

                            $transactions = [];
                            foreach ($branches_ids as $branch_id) {
                                $transactions[] = [
                                    'qty' => $raw[$date][$branch_id][$time['start_hour'] . '_' . $time['end_hour']][$product_id]['qty'] ?? 0,
                                    'total_price' => $raw[$date][$branch_id][$time['start_hour'] . '_' . $time['end_hour']][$product_id]['total_price'] ?? 0,
                                ];
                            }
                            $result[] = [
                                'show_date' => true,
                                'show_time' => true,
                                'date' => $date,
                                'start_time' => $time['start_hour'],
                                'end_time' => $time['end_hour'],
                                'product_category_name' => $categories[$product_id]->name ?? null,
                                'transactions' => $transactions
                            ];
                        }
                    }
                }
            }
        }

        return [
            'data' => $result,
            'branch_names' => array_values($branches)
        ];
    }
}
