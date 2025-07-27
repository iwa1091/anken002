<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; // Carbonをインポート
// use Illuminate\Support\Facades\Log; // Logファサードは不要になったため削除またはコメントアウト

class BreakTime extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * 一括代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'attendance_id',
        'break_start_time',
        'break_end_time',
    ];

    /**
     * The attributes that should be cast.
     * キャスト対象の属性
     *
     * @var array<string, string>
     */
    protected $casts = [
        'break_start_time' => 'datetime', // 休憩開始時刻をCarbonインスタンスにキャスト
        'break_end_time' => 'datetime',   // 休憩終了時刻をCarbonインスタンスにキャスト
        'created_at' => 'datetime',       // created_at をCarbonインスタンスにキャスト
        'updated_at' => 'datetime',       // updated_at をCarbonインスタンスにキャスト
    ];

    /**
     * Get the attendance record that owns the break time.
     * この休憩時間が属する勤怠データとのリレーション
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * Get the formatted break start time.
     * 休憩開始時刻を「HH:MM」形式で取得するアクセサ。
     * nullの場合は「00:00」を返します。
     *
     * @return string
     */
    public function getFormattedStartTimeAttribute(): string
    {
        $time = $this->break_start_time;
        return $time ? $time->format('H:i') : '00:00';
    }

    /**
     * Get the formatted break end time.
     * 休憩終了時刻を「HH:MM」形式で取得するアクセサ。
     * nullの場合は「00:00」を返します。
     *
     * @return string
     */
    public function getFormattedEndTimeAttribute(): string
    {
        $time = $this->break_end_time;
        return $time ? $time->format('H:i') : '00:00';
    }
}
