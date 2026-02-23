@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/register.css') }}">
@endsection

@section('content')
<div class="container register-page">
    <h1 class="page-heading--auth">会員登録</h1>
    <form class="register-page__form" action="{{ route('register') }}" method="POST" novalidate>
        @csrf
        <div class="register-page__form-inner">
            <div class="form__group">
                <label class="form__label" for="name">ユーザー名</label>
                <input class="form__input" type="text" name="name" id="name" value="{{ old('name') }}">
                @error('name')
                    <p class="form__error">{{ $message }}</p>
                @enderror
            </div>
            <div class="form__group">
                <label class="form__label" for="email">メールアドレス</label>
                <input class="form__input" type="email" name="email" id="email" value="{{ old('email') }}">
                @error('email')
                    <p class="form__error">{{ $message }}</p>
                @enderror
            </div>
            <div class="form__group">
                <label class="form__label" for="password">パスワード</label>
                <input class="form__input" type="password" name="password" id="password">
                @error('password')
                    <p class="form__error">{{ $message }}</p>
                @enderror
            </div>
            <div class="form__group">
                <label class="form__label" for="password_confirmation">確認用パスワード</label>
                <input class="form__input" type="password" name="password_confirmation" id="password_confirmation">
                @error('password_confirmation')
                    <p class="form__error">{{ $message }}</p>
                @enderror
            </div>
            <input class="form__btn" type="submit" value="登録する">
        </div>
    </form>
    <a class="page-link" href="{{ route('login') }}">ログインはこちら</a>
</div>
@endsection
