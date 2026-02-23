<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    // ログイン後のレスポンス
    public function toResponse($request)
    {
        $user = $request->user();

        // メール未認証なら verify 画面へ
        if ($user && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        // 管理者
        if ($user &&$user->role === 'admin') {
            return redirect()->route('admin.attendance.list');
        }

        // 一般ユーザー
        return redirect()->route('attendance.index');
    }
}
