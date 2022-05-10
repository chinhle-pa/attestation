<?php

namespace ChinhlePa\Attestation\Http\Middleware;

use Closure;
use ChinhlePa\Attestation\Models\Attestation;

use Illuminate\Support\Str;

class EnsureDevice
{
    public function handle($request, Closure $next)
    {
        if ($request->has('title')) {
            $request->merge([
                'title' => ucfirst($request->title)
            ]);
        }

        $response = $next($request);

        if($response->status() == 200){
            $challenge = base64_encode(urlencode(Str::random(config('attestation.CHALLENGE_LENGTH', 30))));

            $attestation = [
                'challenge' => $challenge,
                'method' => $request->getMethod(),
                'endpoint' => $request->getUri(),
                'header' => json_encode($request->header(),true),
                'request' => json_encode($request->all(),true),
                'response' => json_encode($response->getContent(),true),
            ];
            Attestation::create($attestation);

            return response()->json([
                'challenge' => $challenge
            ]);
        }
        
        return $response;
    }
}