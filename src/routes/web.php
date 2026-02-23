<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BreakController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\ApplicationController as AdminApplicationController;

/*
|--------------------------------------------------------------------------
| 会員登録・ログイン認証
|--------------------------------------------------------------------------
*/

// PG01 会員登録（一般）Fortify標準機能を使用
// PG02 ログイン（一般）Fortify標準機能を使用

// PG07 ログイン画面（管理者）
Route::get('/admin/login', fn () => view('admin.auth.login'))->name('admin.login');


/*
|--------------------------------------------------------------------------
| 一般ユーザー（認証必須）
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // PG03 勤怠登録画面（今日）
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');

    // 出勤・退勤・休憩（操作）
    Route::post('/attendance/start', [AttendanceController::class, 'start'])->name('attendance.start');

    Route::post('/attendance/end', [AttendanceController::class, 'end'])->name('attendance.end');

    Route::post('/break/start', [BreakController::class, 'start'])->name('break.start');

    Route::post('/break/end', [BreakController::class, 'end'])->name('break.end');

    // PG04 勤怠一覧
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');

    // PG05 勤怠詳細
    Route::get('/attendance/detail/{attendance}', [AttendanceController::class, 'detail'])->name('attendance.detail');

    // PG06・PG12 申請一覧
    Route::get('/stamp_correction_request/list', [ApplicationController::class, 'list'])
        ->name('application.list');

    // 申請詳細
    Route::get('/stamp_correction_request/{application}', [ApplicationController::class, 'detail'])
        ->name('application.detail');

    // 申請送信
    Route::post('/stamp_correction_request', [ApplicationController::class, 'store'])
        ->name('application.store');
});


/*
|--------------------------------------------------------------------------
| 管理者（認証・権限必須）
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'can:admin'])->prefix('admin')->group(function () {

    // PG08 勤怠一覧（管理者）
    Route::get('/attendance/list', [AdminAttendanceController::class, 'list'])
        ->name('admin.attendance.list');

    // PG09 勤怠詳細（管理者）
    Route::get('/attendance/{attendance}', [AdminAttendanceController::class, 'detail'])
        ->name('admin.attendance.detail');

    // 管理者による直接更新
    Route::put('/attendance/{attendance}', [AdminAttendanceController::class, 'update'])
        ->name('admin.attendance.update');

    // PG10 スタッフ一覧
    Route::get('/staff/list', [AdminAttendanceController::class, 'staffList'])
        ->name('admin.staff.list');

    // PG11 スタッフ別勤怠一覧
    Route::get('/attendance/staff/{staff}', [AdminAttendanceController::class, 'staffAttendanceList'])
        ->name('admin.attendance.staff');


    // CSV出力
    Route::get('/attendance/staff/{staff}/csv', [AdminAttendanceController::class, 'exportStaffMonthlyCsv'])
        ->name('admin.attendance.staff.csv');
});

// 申請系のみprefix('admin')外にする
Route::middleware(['auth', 'can:admin'])->group(function () {
    // PG13 修正申請承認画面（詳細）
    Route::get('/stamp_correction_request/approve/{application}', [AdminApplicationController::class, 'detail'])
        ->name('admin.application.detail');

    // PG13 修正申請承認（処理）
    Route::post('/stamp_correction_request/approve/{application}', [AdminApplicationController::class, 'approve'])
        ->name('admin.application.approve');
});
