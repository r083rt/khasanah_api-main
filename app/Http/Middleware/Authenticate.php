<?php

namespace App\Http\Middleware;

use App\Models\UserSession;
use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use App\Traits\ResponseTransform;

class Authenticate
{
    use ResponseTransform;

    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        \Log::info('User Guard: ' . json_encode($this->auth->guard($guard)->user()));
        if ($this->auth->guard($guard)->guest()) {
            return $this->response('Tidak ada otorisasi-guest', 'error', 401);
        }

        $session = UserSession::select('id')->where('user_id', auth()->id())->count();
        if ($session <= 0) {
            return $this->response('Tidak ada otorisasi-seesion' . auth()->id(), 'error', 401);
        }

        return $next($request);
    }
}
