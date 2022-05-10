<?php

namespace ChinhlePa\Attestation\Http\Middleware;

use Closure;

class CapitalizeTitle
{
    public function handle($request, Closure $next)
    {
        if ($request->has('title')) {
            $request->merge([
                'title' => ucfirst($request->title)
            ]);
        }

        $response = $next($request);

        // Perform action
        dd($response);

        return $response;
    }
}