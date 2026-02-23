<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisteredResponse implements RegisterResponseContract
{
    // 会員登録後のレスポンス
    public function toResponse($request)
    {
        // 会員登録直後はプロフィール登録画面へ遷移
        return redirect()->route('verification.notice');
    }
}
