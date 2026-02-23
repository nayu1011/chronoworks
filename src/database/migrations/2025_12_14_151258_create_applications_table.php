<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('status', 20)->default('pending');
            $table->foreignId('applicant_user_id')->constrained('users');
            $table->foreignId('attendance_id')->constrained();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->text('comment')->nullable();
            $table->foreignId('approver_user_id')->nullable()->constrained('users');
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
