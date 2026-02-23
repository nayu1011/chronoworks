<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time',
        'comment',
    ];

    protected $casts = [
        'date'       => 'date',
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
    ];

    /* =====================
     * リレーション
     * ===================== */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendanceBreaks(): HasMany
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    /* =====================
     * 計算系（秒）
     * ===================== */

    public function getBreakSecondsAttribute(): int
    {
        return $this->attendanceBreaks->sum(function ($break) {
            if (! $break->start_time || ! $break->end_time) {
                return 0;
            }
            return $break->end_time->diffInSeconds($break->start_time);
        });
    }

    public function getWorkingSecondsAttribute(): int
    {
        if (! $this->start_time || ! $this->end_time) {
            return 0;
        }

        return max(
            $this->end_time->diffInSeconds($this->start_time) - $this->break_seconds,
            0
        );
    }

    /* =====================
     * 表示用（H:i）
     * ===================== */

    public function getBreakTimeFormattedAttribute(): string
    {
        return $this->formatSeconds($this->break_seconds);
    }

    public function getWorkingTimeFormattedAttribute(): string
    {
        return $this->formatSeconds($this->working_seconds);
    }

    protected function formatSeconds(int $seconds): string
    {
        return sprintf(
            '%d:%02d',
            floor($seconds / 3600),
            floor(($seconds % 3600) / 60)
        );
    }
}
