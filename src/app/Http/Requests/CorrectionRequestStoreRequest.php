<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Attendance;
use Illuminate\Validation\Rule;

class CorrectionRequestStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 修正申請を行う権限があるか確認
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     * リクエストに適用するバリデーションルールを取得
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        // ルートパラメータからattendance_idを取得
        $attendanceId = $this->route('attendance_id');
        $attendance = Attendance::find($attendanceId);

        // 元の勤怠レコードの退勤時刻を取得
        $originalCheckOutTime = $attendance ? $attendance->check_out_time : null;

        // 希望退勤時刻を優先し、存在しない場合は元の退勤時刻を使用
        $checkOutTimeForValidation = $this->input('requested_check_out_time') ?? ($originalCheckOutTime ? Carbon::parse($originalCheckOutTime)->format('H:i') : null);

        return [
            'requested_check_in_time' => [
                'nullable',
                'string',
                'date_format:H:i',
                'required_without_all:requested_check_out_time,reason'
            ],
            'requested_check_out_time' => [
                'nullable',
                'string',
                'date_format:H:i',
                'after_or_equal:requested_check_in_time',
                'required_without_all:requested_check_in_time,reason'
            ],
            'requested_breaks' => [
                'nullable',
                'array',
            ],
            'requested_breaks.*.start' => [
                'nullable',
                'string',
                'date_format:H:i',
                // 休憩開始時刻が入力されている場合、かつ退勤時刻が有効な値の場合のみ検証
                Rule::when($this->filled('requested_breaks.*.start') && $checkOutTimeForValidation !== null, [
                    'before_or_equal:' . $checkOutTimeForValidation,
                ]),
            ],
            'requested_breaks.*.end' => [
                'nullable',
                'string',
                'date_format:H:i',
                Rule::when($this->filled('requested_breaks.*.start') && $this->filled('requested_breaks.*.end') && $checkOutTimeForValidation !== null, [
                    'after:requested_breaks.*.start',
                    'before_or_equal:' . $checkOutTimeForValidation,
                ]),
            ],
            'reason' => [
                'required', 
                'string', 
                'max:1000'
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     * バリデーションエラー用のカスタム属性名を取得
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'requested_check_in_time' => '希望出勤時刻',
            'requested_check_out_time' => '希望退勤時刻',
            'requested_breaks' => '希望休憩時間',
            'requested_breaks.*.start' => '休憩開始時刻',
            'requested_breaks.*.end' => '休憩終了時刻',
            'reason' => '修正理由',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     * 定義されたバリデーションルールに対するエラーメッセージを取得
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'requested_check_in_time.required_without_all' => '出勤時間、退勤時間、または備考のいずれかを入力してください。',
            'requested_check_in_time.string' => ':attributeは文字列で入力してください。',
            'requested_check_in_time.date_format' => ':attributeはHH:MM形式で入力してください。',
            
            'requested_check_out_time.string' => ':attributeは文字列で入力してください。',
            'requested_check_out_time.date_format' => ':attributeはHH:MM形式で入力してください。',
            'requested_check_out_time.after_or_equal' => '出勤時間もしくは退勤時間が不適切な値です',

            'requested_breaks.array' => ':attributeは配列で入力してください。',

            'requested_breaks.*.start.before_or_equal' => '出勤時間もしくは退勤時間が不適切な値です',
            'requested_breaks.*.start.string' => '休憩開始時刻は文字列で入力してください。',
            'requested_breaks.*.start.date_format' => '休憩開始時刻はHH:MM形式で入力してください。',

            'requested_breaks.*.end.before_or_equal' => '出勤時間もしくは退勤時間が不適切な値です',
            'requested_breaks.*.end.string' => '休憩終了時刻は文字列で入力してください。',
            'requested_breaks.*.end.date_format' => '休憩終了時刻はHH:MM形式で入力してください。',
            'requested_breaks.*.end.after' => '休憩終了時刻は休憩開始時刻より後の時刻を入力してください。',

            'reason.required_without_all' => '出勤時間、退勤時間、または備考のいずれかを入力してください。',
            'reason.string' => ':attributeは文字列で入力してください。',
            'reason.max' => ':attributeは:max文字以内で入力してください。',

            'reason.required' => '備考を記入してください', // このメッセージは'required_without_all'で置き換えられる可能性あり
            'reason.string' => ':attributeは文字列で入力してください。',
            'reason.max' => ':attributeは:max文字以内で入力してください。',
        ];
    }
}
