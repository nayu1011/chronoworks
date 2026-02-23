<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse;

use App\Http\Responses\LoginResponse;
use App\Http\Responses\RegisteredResponse;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use App\Http\Responses\LogoutResponse;

use Laravel\Fortify\Contracts\VerifyEmailResponse;
use App\Http\Responses\VerifyEmailResponse as CustomVerifyEmailResponse;

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        // 会員登録後のレスポンス差し替え（登録時）
        $this->app->singleton(RegisterResponse::class, RegisteredResponse::class);

        // メール認証後のレスポンス差し替え
        $this->app->singleton(VerifyEmailResponse::class, CustomVerifyEmailResponse::class);

        // ログイン後のレスポンス差し替え
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);

        // ログアウト後のレスポンス差し替え
        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);

        Fortify::createUsersUsing(CreateNewUser::class);

        // 会員登録画面のViewを指定
        Fortify::registerView(function () {
            return view('auth.register');
        });

        // ログイン画面のViewを指定
        Fortify::loginView(function () {
            return view('auth.login');
        });

        // メール認証を必須にする
        Fortify::verifyEmailView(function () {
            return view('auth.verify-email');
        });

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(10)->by($email . $request->ip());
        });
    }
}
