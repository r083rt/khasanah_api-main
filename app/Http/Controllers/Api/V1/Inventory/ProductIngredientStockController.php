<?php

namespace App\Http\Controllers\Api\V1\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Inventory\ProductIngredientStock;
use App\Models\ProductIngredient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductIngredientStockController extends Controller
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
    public function __construct(ProductIngredient $model)
    {
        $this->middleware('permission:stok-bahan.lihat', [
            'only' => ['index']
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
        $branchId = Auth::user()->branch_id;

        $data = $this->model->select('id', 'code', 'name')->search($request)->orderBy('name');
        $data = $data->paginate($this->perPage($data));

        foreach ($data->items() as $value) {
            $stock = ProductIngredientStock::select('id', 'branch_id')->where('product_ingredient_id', $value->id)->with(['productIngredientUnit:id,name']);
            if ($branchId != 1) {
                $stock = $stock->where('branch_id', $branchId);
            }
            $branch = $stock->groupBy('branch_id')->get();

            $branches = [];
            foreach ($branch as $row) {
                $stock = ProductIngredientStock::where('product_ingredient_id', $value->id)->where('branch_id', $row->branch_id)->with(['productIngredientUnit:id,name'])->get();
                $stocks = [];
                foreach ($stock as $values) {
                    $stocks[] = [
                        'unit' => $values->productIngredientUnit->name,
                        'stock' => $values->stock,
                    ];
                }

                $branches[] = [
                    'name' => Branch::find($row->branch_id)->name,
                    'stocks' => $stocks
                ];
            }

            $stock = ProductIngredientStock::where('product_ingredient_id', $value->id)->where('branch_id', $branchId)->with(['productIngredientUnit:id,name','productIngredient','productIngredient.unit'])->get();
            $stocks = [];
            foreach ($stock as $values) {
                
                $unit_data = $values->productIngredient->unit;

                $unit = $values->productIngredientUnit->id;

                if ($unit_data) {
                    if ($unit_data->parent_id_4 && $unit_data->parent_id_4 == $unit) {
        
                        $unit_id = 4;
                        
                        $value->unit_4 = [
                            'name'=> $values->productIngredientUnit->name,
                            'qty'=> $values->stock
                        ];

                    } elseif ($unit_data->parent_id_3 && $unit_data->parent_id_3 == $unit) {
        
                        $unit_id = 3;

                        $value->unit_3 = [
                            'name'=> $values->productIngredientUnit->name,
                            'qty'=> $values->stock
                        ];

                    } elseif ($unit_data->parent_id_2 && $unit_data->parent_id_2 == $unit ) {

                        $unit_id = 2;

                        $value->unit_2 = [
                            'name'=> $values->productIngredientUnit->name,
                            'qty'=> $values->stock
                        ];

                    } else {
                        
                        $unit_id = 1;

                        $value->unit_1 = [
                            'name'=> $values->productIngredientUnit->name,
                            'qty'=> $values->stock
                        ];
                    }
                } else {
                    $unit_id = 1;
                    
                    $value->unit_1 = [
                        'name'=> $values->productIngredientUnit->name,
                        'qty'=> $values->stock
                    ];
                }

            }

            $value->details = $branches;
        }

        return $this->response($data);
    }
}
