<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->date('date');                           // 日付カラム
            $table->dateTime('start_time');                 // 出勤時間は必須のためnullable()は付けない
            $table->dateTime('end_time')->nullable();       // 退勤時間は後で入力することもあるためnullable()を付ける
            $table->text('comment')->nullable();            // 管理者が勤怠修正する際の備考
            $table->unique(['user_id', 'date']);            // 同じユーザーが同じ出勤時間で複数の記録を持てないようにする
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
