<?php

namespace App\Http\Controllers\Api\V1\Purchasing;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Purchasing\Forecast;
use App\Models\Purchasing\Trend;
use Illuminate\Support\Facades\DB;

class TrendController extends Controller
{
    /**
     * The user repository instance.
     */
    protected $model;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(Trend $model)
    {
        $this->middleware('permission:trend.lihat|trend.show', [
            'only' => ['index', 'show']
        ]);
        $this->middleware('permission:trend.ubah', [
            'only' => ['update']
        ]);
        $this->model = $model;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $month = month();
        $datas = [];
        foreach ($month as $value) {
            $data = $this->model->where('month', $value['value'])->first();
            $trend = $data?->trend;
            if ($trend) {
                $trend_format = $trend . ' %';
            } else {
                $trend_format = null;
            }

            $inflation = $data?->inflation;
            if ($inflation) {
                $inflation_format = $inflation . ' %';
            } else {
                $inflation_format = null;
            }

            $datas[] = [
                'name' => $value['name'],
                'value' => $value['value'],
                'trend' => $trend,
                'trend_format' => $trend_format,
                'inflation' => $inflation,
                'inflation_format' => $inflation_format,
            ];
        }

        return $this->response($datas);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($month)
    {
        $model = $this->model->where('month', $month)->first();
        if (is_null($model)) {
            $model = $this->model->create([
                'month' => $month,
            ]);
            $model = $this->model->find($model->id);
        }
        return $this->response($model);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $month
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $month)
    {
        $data = $this->validate($request, [
            'trend' => 'required|string',
            'inflation' => 'required|string',
            'is_update' => 'nullable|in:0,1',
        ]);

        $model = $this->model->where('month', $month)->first();

        $data = DB::connection('mysql')->transaction(function () use ($data, $model, $month) {
            $model->update($data);
            if (isset($data['is_update']) && $data['is_update'] == 1) {
                $forecast = Forecast::where('month', $month)->where('year', date('Y'))->get();
                foreach ($forecast as $value) {
                    $trend = Trend::where('month', $month)->first();
                    $trendTotal = $trend ? round($trend->trend / 100 * $value->trend) : 0;
                    $inflasiTotal = $trend ? round($trend->inflasi / 100 * $value->inflation) : 0;
                    $value->update([
                        'sale' => ($value->sale - $value->trend - $value->inflation) + $trendTotal + $inflasiTotal,
                        'trend' => $trendTotal,
                        'inflation' => $inflasiTotal,
                    ]);
                }
            }

            return $model;
        });

        return $this->response($model);
    }
}
