<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; // ★追加: Carbonを使用するためにこの行を追加

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
        // 'type', // この行を削除
        'requested_check_in_time',
        'requested_check_out_time',
        'requested_breaks', // JSON形式で保存
        'reason',
        'status',
        'approved_by', // ★追加: 承認者ID
        'approved_at', // ★追加: 承認日時
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
        'approved_at' => 'datetime', // ★追加: approved_atもdatetimeにキャスト
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
     * 申請の種類を日本語で取得するアクセサ
     * このメソッドは不要になるため、完全に削除
     *
     * public function getFormattedTypeAttribute()
     * {
     * switch ($this->type) {
     * case 'punch_error':
     * return '打刻ミス';
     * case 'break_time_correction':
     * return '休憩時間修正';
     * case 'check_in_date_correction':
     * return '出勤日修正';
     * case 'check_out_date_correction':
     * return '退勤日修正';
     * case 'other':
     * return 'その他';
     * default:
     * return $this->type;
     * }
     * }
     */
}
