<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Libraries\JwtToken;
use App\Models\UserSession;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * The user repository instance.
     */
    protected $id;
    protected $model;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(User $model)
    {
        $this->id = Auth::id();
        $this->model = $model;
    }

    /**
     * Update profile user.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $data = $this->validate($request, [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email,' . $this->id,
            'phone' => 'required|unique:users,phone,' . $this->id,
            'address' => 'nullable|string',
            'note' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);
        $model = $this->model->findOrFail($this->id);

        $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
            return $model->update($data);
        });

        return $this->response($model);
    }

    /**
     * Reset user.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset(Request $request)
    {
        $data = $this->validate($request, [
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $model = $this->model->findOrFail($this->id);
        if (Hash::check($data['current_password'], $model->password)) {
            $data['password'] = Hash::make($data['password']);

            $data = DB::connection('mysql')->transaction(function () use ($data, $model) {
                return $model->update($data);
            });

            return $this->response(JwtToken::respondWithToken(auth()->refresh())->original);
        } else {
            return $this->response('Password sekarang tidak valid', 'error', 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy()
    {
        $model = $this->model->findOrFail($this->id)->delete();
        UserSession::where('user_id', $this->id)->delete();
        return $this->response($model);
    }
}
