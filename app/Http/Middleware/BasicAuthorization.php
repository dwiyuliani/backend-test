<?php

namespace App\Http\Middleware;

use Closure;

class BasicAuthorization
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = $request->getUser();
        $password = $request->getPassword();
        // validate empty user and password
        if ((isset($user) && empty($user)) || (isset($password) && empty($password))) {
            return response()->json(response_error('Not authorized.'), 400);
        }

        // check user and password provided
        $basic_auth = base64_encode(env('BASIC_AUTH_USER') . ":" . env('BASIC_AUTH_PASSWORD'));
        $request_auth = base64_encode($user . ":" . $password);
        // validate
        if ($basic_auth != $request_auth) {
            return response()->json(response_error('Invalid authorization.'), 400);
        }
        
        return $next($request);
    }
}
