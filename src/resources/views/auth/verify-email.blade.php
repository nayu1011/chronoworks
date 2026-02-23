@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/verify-email.css') }}">
@endsection

@section('content')
<div class="verify-email-page">
    <div class="verify-email-container">
        <p class="verify-message">登録していただいたメールアドレスに認証メールを送付しました。</p>
        <p class="verify-message">メール認証を完了してください。</p>
        <a class="verify-link" href="http://localhost:8025" target="_blank">認証はこちらから</a>
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="page-link">
                認証メールを再送する
            </button>
        </form>
    </div>
</div>
@endsection
