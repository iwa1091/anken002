<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; // 日付・時刻操作のためにCarbonを使用

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
        'date' => 'date',             // 日付としてキャスト
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
}
