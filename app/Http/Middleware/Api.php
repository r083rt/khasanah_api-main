<?php

namespace App\Http\Middleware;

use Closure;

class Api
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
        $header = $request->header('Accept') ?: $request->header('accept');
        $accept = collect(
            explode(',', $header)
        )->transform(function ($item) {
            return trim($item);
        })->filter(function ($item) {
            return $item == 'application/json';
        });

        if ($accept->isEmpty()) {
            return redirect('/');
        }

        return $next($request);
    }
}
