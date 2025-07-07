<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; // 日付・時刻操作のためにCarbonを使用
use Carbon\CarbonInterval; // CarbonIntervalも使用するため追加

class Attendance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * 一括代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'date',
        'check_in_time',
        'check_out_time',
        'status',
        'remarks',
    ];

    /**
     * The attributes that should be cast.
     * キャスト対象の属性
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',           // 日付としてキャスト
        'check_in_time' => 'datetime', // 出勤時刻をCarbonインスタンスにキャスト
        'check_out_time' => 'datetime',// 退勤時刻をCarbonインスタンスにキャスト
        'created_at' => 'datetime',    // created_at をCarbonインスタンスにキャスト
        'updated_at' => 'datetime',    // updated_at をCarbonインスタンスにキャスト
    ];

    /**
     * Get the user that owns the attendance record.
     * この勤怠データが属するユーザーとのリレーション
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the break times for the attendance record.
     * この勤怠データに紐づく休憩時間とのリレーション
     */
    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class);
    }

    /**
     * Get the correction requests for the attendance record.
     * この勤怠データに紐づく修正申請とのリレーション
     */
    public function correctionRequests()
    {
        return $this->hasMany(CorrectionRequest::class);
    }

    /**
     * Get the formatted date in "MM/DD (DayOfWeek)" format.
     * 日付を「MM/DD（曜日）」形式で取得するアクセサ
     * Bladeで $attendance->formatted_date のようにアクセスできます。
     *
     * @return string
     */
    public function getFormattedDateAttribute()
    {
        // date属性がCarbonインスタンスであることを前提とする (protected $casts で設定済み)
        if (empty($this->date)) {
            return ''; // 日付が存在しない場合のハンドリング
        }

        // 曜日を日本語で取得するための配列
        $dayOfWeekNames = ['日', '月', '火', '水', '木', '金', '土'];
        $dayOfWeek = $dayOfWeekNames[$this->date->dayOfWeek];

        // 月/日（曜日）の形式で返す
        return $this->date->format('m/d') . '（' . $dayOfWeek . '）';
    }

    /**
     * Get the formatted date in "YYYY年MM月DD日" format.
     * 日付を「YYYY年MM月DD日」形式で取得するアクセサ
     * Bladeで $attendance->full_formatted_date のようにアクセスできます。
     *
     * @return string
     */
    public function getFullFormattedDateAttribute()
    {
        if (empty($this->date)) {
            return ''; // 日付が存在しない場合のハンドリング
        }
        return $this->date->format('Y年m月d日');
    }

    /**
     * Get the formatted check-in time in "HH:MM" format.
     * 出勤時刻を「HH:MM」形式で取得するアクセサ
     * Bladeで $attendance->formatted_check_in_time のようにアクセスできます。
     *
     * @return string
     */
    public function getFormattedCheckInTimeAttribute()
    {
        // check_in_time属性がCarbonインスタンスであることを前提とする
        // 存在しない場合は空文字列を返す
        return $this->check_in_time ? $this->check_in_time->format('H:i') : '';
    }

    /**
     * Get the formatted check-out time in "HH:MM" format.
     * 退勤時刻を「HH:MM」形式で取得するアクセサ
     * Bladeで $attendance->formatted_check_out_time のようにアクセスできます。
     *
     * @return string
     */
    public function getFormattedCheckOutTimeAttribute()
    {
        // check_out_time属性がCarbonインスタンスであることを前提とする
        // 存在しない場合は空文字列を返す
        return $this->check_out_time ? $this->check_out_time->format('H:i') : '';
    }

    /**
     * Get the formatted total break time.
     * 合計休憩時間を「時:分」形式で取得するアクセサ
     * Bladeで $attendance->formatted_break_time のようにアクセスできます。
     */
    public function getFormattedBreakTimeAttribute()
    {
        // total_break_time が存在しない、またはnullの場合は空文字列を返す
        if (is_null($this->total_break_time)) {
            return '';
        }

        $totalSeconds = $this->total_break_time;
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);

        // 時は先頭ゼロなし、分は2桁表示でフォーマット
        return sprintf('%d:%02d', $hours, $minutes);
    }

    /**
     * Get the formatted total working time.
     * 合計勤務時間を「時:分」形式で取得するアクセサ
     * Bladeで $attendance->formatted_working_time のようにアクセスできます。
     */
    public function getFormattedWorkingTimeAttribute()
    {
        // total_working_time が存在しない、またはnullの場合は空文字列を返す
        if (is_null($this->total_working_time)) {
            return '';
        }

        $totalSeconds = $this->total_working_time;
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);

        // 時は先頭ゼロなし、分は2桁表示でフォーマット
        return sprintf('%d:%02d', $hours, $minutes);
    }
}
