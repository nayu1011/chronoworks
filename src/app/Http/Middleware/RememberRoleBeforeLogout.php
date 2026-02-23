<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RememberRoleBeforeLogout
{
    public function handle(Request $request, Closure $next)
    {
        // /logout の直前（まだ user が取れるタイミング）
        if ($request->routeIs('logout') && $request->user()) {
            //「Request属性」にログアウト前の役割を保存
            $request->attributes->set('logout_role', $request->user()->role);
        }

        return $next($request);
    }
}
