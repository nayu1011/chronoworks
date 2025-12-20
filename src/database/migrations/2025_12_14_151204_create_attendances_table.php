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
            $table->foreignId('user_id')->constrained();// 【Todo】constrained()の使い方学ぶ。引数が必要？？
            $table->dateTime('start_time');             // 出勤時間は必須のためnullable()は付けない
            $table->dateTime('end_time')->nullable();   // 退勤時間は後で入力することもあるためnullable()を付ける
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
