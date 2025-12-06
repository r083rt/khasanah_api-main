<?php

namespace App\Http\Controllers\Api\V1\Management;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Management\UserNotificationToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserNotificationTokenController extends Controller
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
    public function __construct(UserNotificationToken $model)
    {
        $this->model = $model;
    }

    /**
     * Store the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $this->validate($request, [
            'token' => 'required|string',
        ]);

        $data = DB::connection('mysql')->transaction(function () use ($data) {
            $this->model->where('user_id', Auth::id())->delete();
            return $this->model->create($data);
        });

        return $this->response($data ? true : false);
    }
}
