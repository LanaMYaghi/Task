<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\JWTException;

class RefreshToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            auth('api')->user();
        } catch (TokenExpiredException $e) {
            try {
                $newToken = auth('api')->refresh();

                auth('api')->setToken($newToken);

                $response = $next($request);

                if ($response->headers->get('content-type') === 'application/json') {
                    $data = $response->getData();
                    $data->access_token = $newToken;
                    $response->setData($data);
                }

                return $response;
            } catch (JWTException $e) {
                return response()->json([
                    'message' => 'Token expired, please login again'
                ], 401);
            }
        }

        return $next($request);
    }
}
