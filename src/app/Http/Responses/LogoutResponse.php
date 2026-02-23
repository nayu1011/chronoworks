<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    // ログアウト後のレスポンス
    public function toResponse($request)
    {
        $role = $request->attributes->get('logout_role');

        // 管理者
        if ($role === 'admin') {
            return redirect()->route('admin.login');
        }

        // 一般ユーザー or 未ログイン
        return redirect()->route('login');
    }
}
