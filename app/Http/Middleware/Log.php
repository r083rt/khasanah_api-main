<?php

namespace App\Http\Middleware;

use Closure;

class Log
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
        try {
            $token = explode('/', $request->getRequestUri());
            if (!in_array($token[2], $this->token())) {
                return redirect('/');
            }

            return $next($request);
        } catch (\Throwable $th) {
            return redirect('/');
        }
    }

    /**
     * get token
     *
     * @return array
     */
    private function token()
    {
        return [
            'KmZwAy87Aa'
        ];
    }
}
