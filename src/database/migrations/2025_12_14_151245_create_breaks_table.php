<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->nullable()->cascadeOnDelete();   // 【Todo】cascadeOnDeleteについて学び直す。cascadeOnDelete()で関連するattendanceが削除されたらbreakも削除される
            $table->dateTime('start_time');             // 休憩開始時間は必須のためnullable()は付けない
            $table->dateTime('end_time')->nullable();   // 休憩終了時間は後で入力することもあるためnullable()を付ける
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breaks');
    }
};
