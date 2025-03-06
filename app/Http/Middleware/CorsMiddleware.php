<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
{
    /**
     * 處理傳入的請求。
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // 在這裡添加您的邏輯
        return $next($request);
        // 設定 CORS 標頭
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Authorization, Content-Type');

        // 如果是 `OPTIONS` 預檢請求，直接回應 200
        if ($request->isMethod('OPTIONS')) {
            return response()->json([], 200);
        }

        return $response;
    }
}
