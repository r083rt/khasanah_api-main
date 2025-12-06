<?php

namespace App\Http\Middleware;

use App\Models\UserSession;
use Closure;
use Illuminate\Support\Facades\Auth;

class Activity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($session = UserSession::where('user_id', Auth::id())->first()) {
            $session->update([
                'last_active_at' => date('Y-m-d H:i:s')
            ]);
        }

        return $next($request);
    }
}
