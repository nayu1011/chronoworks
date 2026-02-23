<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    @yield('css')
    <title>coachtech勤怠管理アプリ</title>
</head>

<body>
    <div class="app">
        <header class="header">

            <div class="header__logo-wrapper">
                <a href="{{ route('admin.attendance.list') }}">
                    <img class="header__logo" src="{{ asset('images/COACHTECH_header_logo.png') }}" alt="COACHTECH ロゴ">
                </a>
            </div>
            <div class="header__link-inner">
                <a class="header__link" href="{{ route('admin.attendance.list') }}">勤怠一覧</a>
                <a class="header__link" href="{{ route('admin.staff.list') }}">スタッフ一覧</a>
                {{-- 申請一覧は一般ユーザーと共通 --}}
                <a class="header__link" href="{{ route('application.list') }}">申請一覧</a>
                <form class="header__link-logout header__link" action="{{ route('logout') }}" method="POST">
                    @csrf
                        <input class="header__link-logout" type="submit" value="ログアウト">
                </form>
            </div>
        </header>

        <main class="content">
            @yield('content')
        </main>
    </div>
    @yield('js')
</body>

</html>
