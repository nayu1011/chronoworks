@extends('admin.layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/login.css') }}">
@endsection

@section('content')
<div class="container login-page">
    <h1 class="page-heading--auth">管理者ログイン</h1>
    <form class="login-page__form" action="{{ route('login') }}" method="POST" novalidate>
        <div class="login-page__form-inner">
            @csrf
            <div class="form__group">
                <label class="form__label" for="email">メールアドレス</label>
                <input class="form__input" type="email" name="email" id="email" value="{{ old('email') }}">
                @error('email')
                    <p class="form__error">{{ $message }}</p>
                @enderror
            </div>
            <div class="form__group">
                <label class="form__label" for="password">パスワード</label>
                <input class="form__input" type="password" name="password" id="password" value="{{ old('password') }}">
                @error('password')
                    <p class="form__error">{{ $message }}</p>
                @enderror
            </div>
            <input class="form__btn" type="submit" value="管理者ログインする">
        </div>
    </form>
</div>
@endsection
