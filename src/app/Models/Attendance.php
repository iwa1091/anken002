<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; // 日付・時刻操作のためにCarbonを使用
use Carbon\CarbonInterval; // CarbonIntervalも使用するため追加
use Illuminate\Support\Facades\Log; // Logファサードをインポート

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
        'break_time',         // ★保持: 秒単位の合計休憩時間 (BreakTimeモデルから計算)
        'working_time',       // ★保持: 秒単位の合計勤務時間
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
        'created_at' => 'datetime',   // created_at をCarbonインスタンスにキャスト
        'updated_at' => 'datetime',   // updated_at をCarbonインスタンスにキャスト
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
     * Check if there is a pending correction request for this attendance record.
     * この勤怠記録に承認待ちの修正申請があるかどうかをチェックするアクセサ
     * Bladeで $attendance->hasPendingCorrectionRequest のようにアクセスできます。
     *
     * @return bool
     */
    public function getHasPendingCorrectionRequestAttribute()
    {
        return $this->correctionRequests()->where('status', 'pending')->exists();
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
        if (empty($this->date)) {
            return '';
        }

        $dayOfWeekNames = ['日', '月', '火', '水', '木', '金', '土'];
        $dayOfWeek = $dayOfWeekNames[$this->date->dayOfWeek];

        return $this->date->format('m/d') . '（' . $dayOfWeek . '）';
    }

    /**
     * Get the formatted date in "YYYY/MM/DD" format.
     * 日付を「YYYY/MM/DD」形式で取得するアクセサ
     * Bladeで $attendance->full_formatted_date のようにアクセスできます。
     *
     * @return string
     */
    public function getFullFormattedDateAttribute()
    {
        if (empty($this->date)) {
            return '';
        }
        return $this->date->format('Y/m/d');
    }

    /**
     * Get the formatted year in "YYYY年" format.
     * 年を「YYYY年」形式で取得するアクセサ
     * Bladeで $attendance->formatted_year のようにアクセスできます。
     *
     * @return string
     */
    public function getFormattedYearAttribute(): string
    {
        return $this->date ? $this->date->format('Y年') : '';
    }

    /**
     * Get the formatted month and day in "MM月DD日" format.
     * 月日を「MM月DD日」形式で取得するアクセサ
     * Bladeで $attendance->formatted_month_day のようにアクセスできます。
     *
     * @return string
     */
    public function getFormattedMonthDayAttribute(): string
    {
        return $this->date ? $this->date->format('m月d日') : '';
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
        return $this->check_out_time ? $this->check_out_time->format('H:i') : '';
    }

    /**
     * Get the formatted total break time.
     * 合計休憩時間を「H:MM」形式で取得するアクセサ
     * Bladeで $attendance->formatted_break_time のようにアクセスできます。
     *
     * @return string
     */
    public function getFormattedBreakTimeAttribute(): string
    {
        // break_time が NULL の場合は空白を返す
        if ($this->break_time === null) {
            return '';
        }
        // break_time が 0 の場合は '0:00' を返す
        if ($this->break_time === 0) {
            return '0:00';
        }

        $hours = floor($this->break_time / 3600);
        $minutes = floor(($this->break_time % 3600) / 60);
        // 秒は表示しないため削除
        // $seconds = $this->break_time % 60;

        // 時は先頭ゼロなし、分は2桁表示でフォーマット
        return sprintf('%d:%02d', $hours, $minutes);
    }

    /**
     * Get the formatted total working time.
     * 合計勤務時間を「H:MM」形式で取得するアクセサ
     * Bladeで $attendance->formatted_working_time のようにアクセスできます。
     *
     * @return string
     */
    public function getFormattedWorkingTimeAttribute(): string
    {
        // working_time が NULL の場合は空白を返す
        if ($this->working_time === null) {
            return '';
        }
        // working_time が 0 の場合は '0:00' を返す
        if ($this->working_time === 0) {
            return '0:00';
        }

        $hours = floor($this->working_time / 3600);
        $minutes = floor(($this->working_time % 3600) / 60);
        // 秒は表示しないため削除
        // $seconds = $this->working_time % 60;

        // 時は先頭ゼロなし、分は2桁表示でフォーマット
        return sprintf('%d:%02d', $hours, $minutes);
    }

    /**
     * 勤怠レコードがその日に完了しているか（出勤・退勤が揃っているか）を判断
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->check_in_time !== null && $this->check_out_time !== null;
    }

    /**
     * Calculate and update the total break time from associated BreakTime records.
     * 関連するBreakTimeレコードから合計休憩時間を計算し、Attendanceモデルのbreak_timeを更新します。
     *
     * @return void
     */
    public function calculateAndSaveTotalBreakTime(): void
    {
        $totalBreakSeconds = 0;
        // 関連するbreakTimesを確実にロード
        $this->loadMissing('breakTimes'); 

        // Log::info("DEBUG: Calculating total break time for Attendance ID: {$this->id}"); // この行を削除

        foreach ($this->breakTimes as $breakTime) {
            if ($breakTime->break_start_time && $breakTime->break_end_time) {
                $duration = $breakTime->break_start_time->diffInSeconds($breakTime->break_end_time);
                $totalBreakSeconds += $duration;
                // Log::info("DEBUG:   BreakTime ID: {$breakTime->id}, Start: {$breakTime->break_start_time->format('H:i:s')}, End: {$breakTime->break_end_time->format('H:i:s')}, Duration: {$duration} seconds"); // この行を削除
            } else {
                // Log::warning("DEBUG:   BreakTime ID: {$breakTime->id} has null start or end time. Skipping calculation."); // この行を削除
            }
        }
        // Log::info("DEBUG:   Calculated total break seconds for Attendance ID {$this->id}: {$totalBreakSeconds}"); // この行を削除

        // break_timeが変更された場合にのみ保存
        if ($this->break_time !== $totalBreakSeconds) {
            $this->break_time = $totalBreakSeconds;
            $this->save();
            // Log::info("DEBUG:   Attendance ID: {$this->id} break_time updated and saved to DB: {$this->break_time}"); // この行を削除
        } else {
            // Log::info("DEBUG:   Attendance ID: {$this->id} break_time is unchanged ({$this->break_time}), no save needed."); // この行を削除
        }
    }
}
