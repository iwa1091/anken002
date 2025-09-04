<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; // Carbonを使用するためにこの行を追加
use Illuminate\Support\Facades\Log; // Logファサードをインポート

class CorrectionRequest extends Model
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
        'user_id',
        'requested_check_in_time',
        'requested_check_out_time',
        'requested_breaks', // JSON形式で保存
        'reason',
        'status',
        'approved_by', // 承認者ID
        'approved_at', // 承認日時
    ];

    /**
     * The attributes that should be cast.
     * キャスト対象の属性
     *
     * @var array<string, string>
     */
    protected $casts = [
        'requested_breaks' => 'array', // JSONカラムを自動的に配列にキャスト
        'requested_check_in_time' => 'datetime',
        'requested_check_out_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'approved_at' => 'datetime', // approved_atもdatetimeにキャスト
    ];

    /**
     * Get the user that owns the correction request.
     * 申請者（ユーザー）とのリレーション
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attendance record associated with the correction request.
     * 修正対象の勤怠データとのリレーション
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * Get the formatted created_at date in "YYYY/MM/DD" format.
     * 申請作成日時を「YYYY/MM/DD」形式で取得するアクセサ
     * Bladeで $correctionRequest->formatted_created_at のようにアクセスできます。
     *
     * @return string
     */
    public function getFormattedCreatedAtAttribute()
    {
        // created_at属性がCarbonインスタンスであることを前提とする
        // 存在しない場合は空文字列を返す
        return $this->created_at ? $this->created_at->format('Y/m/d') : '';
    }

    /**
     * Get the formatted requested check-in time in "HH:MM" format.
     * 修正希望出勤時刻を「HH:MM」形式で取得するアクセサ。
     * nullの場合は「00:00」を返します。
     *
     * @return string
     */
    public function getFormattedRequestedCheckInTimeAttribute(): string
    {
        $formattedTime = $this->requested_check_in_time ? $this->requested_check_in_time->format('H:i') : '00:00';
        return $formattedTime;
    }

    /**
     * Get the formatted requested check-out time in "HH:MM" format.
     * 修正希望退勤時刻を「HH:MM」形式で取得するアクセサ。
     * nullの場合は「00:00」を返します。
     *
     * @return string
     */
    public function getFormattedRequestedCheckOutTimeAttribute(): string
    {
        $formattedTime = $this->requested_check_out_time ? $this->requested_check_out_time->format('H:i') : '00:00';
        return $formattedTime;
    }

    /**
     * Get the formatted attendance year in "YYYY年" format.
     * 関連する勤怠の年を「YYYY年」形式で取得するアクセサ。
     *
     * @return string
     */
    public function getFormattedAttendanceYearAttribute(): string
    {
        // attendanceリレーションが存在し、かつそのdateプロパティが存在する場合にフォーマット
        return $this->attendance && $this->attendance->date ? $this->attendance->date->format('Y年') : 'N/A';
    }

    /**
     * Get the formatted attendance month and day in "MM月DD日" format.
     * 関連する勤怠の月日を「MM月DD日」形式で取得するアクセサ。
     *
     * @return string
     */
    public function getFormattedAttendanceMonthDayAttribute(): string
    {
        // attendanceリレーションが存在し、かつそのdateプロパティが存在する場合にフォーマット
        return $this->attendance && $this->attendance->date ? $this->attendance->date->format('n月j日') : 'N/A';
    }
}
