<?php

namespace App\Http\Middleware;

use Closure;

class XSSProtection
{
    protected $excepts = [
        '&amp;' => '&'
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!in_array(strtolower($request->method()), ['put', 'post'])) {
            return $next($request);
        }

        $input = $request->all();

        array_walk_recursive($input, function (&$input) {
            $input = htmLawed($input, ['safe' => 1]);
            $input = $this->decodeString($input);
        });

        $request->merge($input);

        return $next($request);
    }

    private function decodeString($input)
    {
        foreach ($this->excepts as $encode => $decode) {
            if (str_contains($input, $encode)) {
                $input = str_replace($encode, $decode, $input);
            }
        }
        return $input;
    }
}
