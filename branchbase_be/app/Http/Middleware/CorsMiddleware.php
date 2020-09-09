<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
{
    //prod fe servers
    private $prod_cors_list = [
        'http://bb.helputalk.com'
    ];

    //test fe servers
    private $test_cors_list = [
        'http://dev-bb.helputalk.com',
        'http://localhost:8080',
        'http://localhost:8081',
        'http://localhost:8000',
        'http://localhost:8001'
    ];

    //local fe servers
    private $dev_cors_list = [
        'http://localhost:8080',
        'http://localhost:8081',
        'http://localhost:8000',
        'http://localhost:8001'
    ];

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        switch (env('APP_ENV')) {
            case 'prod' :
                $cors_list = $this->prod_cors_list;
                break;
            case 'test' :
                $cors_list = $this->test_cors_list;
                break;
            default:
                $cors_list = $this->dev_cors_list;
        }

        if (env('APP_ALLOW_BLANK_ORIGIN'))
            $cors_list[] = '';

        $origin = empty($_SERVER['HTTP_ORIGIN']) ? "" : $_SERVER['HTTP_ORIGIN'];

        if (in_array($origin, $cors_list)) {
            $headers = [
                'Access-Control-Allow-Origin' => $origin,
                'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS, PUT, DELETE, PATCH',
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Max-Age' => '86400',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With'];

            if ($request->isMethod('OPTIONS')) {
                return response()->json('{"method":"OPTIONS"}', 200, $headers);
            }

            $response = $next($request);
            foreach ($headers as $key => $value) {
                $response->header($key, $value);
            }
            return $response;
        } else {
            return response()->json('Request invalid!', 403);
        }
    }
}
