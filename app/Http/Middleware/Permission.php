<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Auth;
use App\Traits\ResponseTransform;

class Permission
{
    use ResponseTransform;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $action)
    {
        // $user = Auth::user();

        // $actions = explode('|', $action);
        // if (count(array_intersect($actions, $user->permissions))) {
            return $next($request);
        // }

        // return $this->response('Tidak ada otorisasi', 'error', 401);
    }
}
