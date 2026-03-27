<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Libraries\JwtToken;
use App\Models\Management\BranchSetting;
use App\Models\Pos\Closing;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Arr;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $data = $this->validate($request, [
            'email' => 'required',
            'password' => 'required',
            'os' => 'nullable',
            'mac' => 'nullable',
        ]);
        $data['status'] = 'active';

        if (! $token = auth()->attempt(Arr::except($data, ['os', 'mac']))) {
            return $this->response(Lang::get('auth.failed'), 'error', 422);
        }

        /**
         * Cek Mac Address
         */
        if (Auth::user()->role_id != 1) {
            $branchSetting = BranchSetting::where('branch_id', Auth::user()->branch_id)->get();
            if ($branchSetting->count() > 0) {
                if (!in_array($data['mac'], $branchSetting->pluck('mac')->toArray())) {
                    return $this->response('Anda hanya bisa masuk pada komputer yang sudah didaftarkan', 'error', 422);
                }
            } else {
                return $this->response('Anda hanya bisa masuk pada komputer yang sudah didaftarkan', 'error', 422);
            }
        }

        /**
         * Checking Closing
         */
        // $cek = Closing::select('created_at')->whereDate('created_at', date('Y-m-d'))->where('created_by', Auth::id())->first();
        // if ($cek) {
        //     $closing = tanggal_indo($cek->created_at);
        //     return $this->response('Anda telah melakukan Closing pada: ' . $closing, 'error', 422);
        // }

        $token = JwtToken::respondWithToken($token);

        UserSession::updateOrCreate(
            [
                'user_id' => auth()->id()
            ],
            [
                'user_id' => auth()->id(),
                'os' => isset($data['os']) ? $data['os'] : null,
                'last_login_at' => date('Y-m-d H:i:s'),
                'last_active_at' => date('Y-m-d H:i:s'),
            ]
        );

        return $this->response($token->original);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {

        return $this->response(User::with(['branch:id,name,code,address', 'role:id,name'])->find(auth()->id()));
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $id = auth()->id();
        UserSession::where('user_id', $id)->delete();
        auth()->logout();

        return $this->response(true);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->response(JwtToken::respondWithToken(auth()->refresh())->original);
    }
}
