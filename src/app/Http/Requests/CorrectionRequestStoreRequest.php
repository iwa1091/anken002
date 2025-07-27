<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth; // 認証ユーザーの確認のために使用
use Carbon\Carbon; // 日付・時刻操作のためにCarbonを使用

class CorrectionRequestStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * リクエストを行う権限があるかどうかを判定します。
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // ログイン済みのユーザーのみが修正申請を提出することを許可します。
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     * リクエストに適用されるバリデーションルールを取得します。（FN002 バリデーション）
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'requested_check_in_time' => ['nullable', 'string', 'date_format:H:i'], // 修正希望の出勤時刻 (H:i形式)
            'requested_check_out_time' => ['nullable', 'string', 'date_format:H:i', 'after_or_equal:requested_check_in_time'], // 修正希望の退勤時刻 (H:i形式)
            'requested_breaks' => [
                'nullable',
                'array',
                // 休憩時間が勤務時間内にあるかをチェックするカスタムバリデーション
                function ($attribute, $value, $fail) {
                    $requestedCheckInTime = $this->input('requested_check_in_time');
                    $requestedCheckOutTime = $this->input('requested_check_out_time');

                    // 出勤・退勤時刻が入力されていない場合、休憩時間の勤務時間内チェックはスキップ
                    // (出勤・退勤時刻自体のバリデーションは別途行われる)
                    if (empty($requestedCheckInTime) || empty($requestedCheckOutTime)) {
                        return;
                    }

                    try {
                        $checkInCarbon = Carbon::createFromFormat('H:i', $requestedCheckInTime);
                        $checkOutCarbon = Carbon::createFromFormat('H:i', $requestedCheckOutTime);
                    } catch (\Exception $e) {
                        // 出勤・退勤時刻のフォーマットが不正な場合は、date_formatルールで既にエラーになるため、ここでは何もしない
                        return;
                    }

                    foreach ($value as $index => $break) {
                        $breakStartTime = $break['start'] ?? null;
                        $breakEndTime = $break['end'] ?? null;

                        // 休憩開始・終了時刻が入力されていない場合は、nullableで許可されるためスキップ
                        if (empty($breakStartTime) && empty($breakEndTime)) {
                            continue; // 両方空の場合はスキップ
                        }
                        // 片方だけ入力されている場合はエラー
                        if (empty($breakStartTime) || empty($breakEndTime)) {
                            $fail('休憩の開始時刻と終了時刻は両方入力するか、両方空にしてください。');
                            continue;
                        }


                        try {
                            $breakStartCarbon = Carbon::createFromFormat('H:i', $breakStartTime);
                            $breakEndCarbon = Carbon::createFromFormat('H:i', $breakEndTime);
                        } catch (\Exception $e) {
                            // 休憩時刻のフォーマットが不正な場合は、date_formatルールで既にエラーになる
                            // ここでは、カスタムメッセージを優先して出すため、フォーマットエラーもまとめてこのメッセージにする場合
                             $fail('休憩時間が勤務時間外です');
                            continue;
                        }

                        // 休憩開始時刻が出勤時刻より前、または休憩終了時刻が退勤時刻より後の場合
                        if ($breakStartCarbon->lt($checkInCarbon) || $breakEndCarbon->gt($checkOutCarbon)) {
                            $fail('休憩時間が勤務時間外です');
                        }
                    }
                },
            ],
            // ★修正: required_with を nullable に変更 ★
            'requested_breaks.*.start' => ['nullable', 'string', 'date_format:H:i'], // 各休憩開始時刻
            // ★修正: required_with を nullable に変更 ★
            'requested_breaks.*.end' => ['nullable', 'string', 'date_format:H:i', 'after:requested_breaks.*.start'], // 各休憩終了時刻
            'reason' => ['required', 'string', 'max:1000'], // 修正理由
        ];
    }

    /**
     * Get custom attributes for validator errors.
     * バリデータのエラーメッセージに使用されるカスタム属性名を取得します。
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
     * 定義されたバリデーションルールに対応するエラーメッセージを取得します。（FN003 エラーメッセージ表示）
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'requested_check_in_time.string' => ':attributeは文字列で入力してください。',
            'requested_check_in_time.date_format' => ':attributeはHH:MM形式で入力してください。',

            // FN029-1: 出勤時間もしくは退勤時間が不適切な値です
            'requested_check_out_time.string' => ':attributeは文字列で入力してください。',
            'requested_check_out_time.date_format' => ':attributeはHH:MM形式で入力してください。',
            'requested_check_out_time.after_or_equal' => '出勤時間もしくは退勤時間が不適切な値です',

            'requested_breaks.array' => ':attributeは配列で入力してください。', // 通常は表示されない

            // ★修正: nullable に変更したため、required_with のメッセージは不要 ★
            'requested_breaks.*.start.string' => '休憩開始時刻は文字列で入力してください。',
            'requested_breaks.*.start.date_format' => '休憩開始時刻はHH:MM形式で入力してください。',

            // ★修正: nullable に変更したため、required_with のメッセージは不要 ★
            'requested_breaks.*.end.string' => '休憩終了時刻は文字列で入力してください。',
            'requested_breaks.*.end.date_format' => '休憩終了時刻はHH:MM形式で入力してください。',
            'requested_breaks.*.end.after' => '休憩終了時刻は休憩開始時刻より後の時刻を入力してください。',

            // FN029-3: 備考を記入してください
            'reason.required' => '備考を記入してください',
            'reason.string' => ':attributeは文字列で入力してください。',
            'reason.max' => ':attributeは:max文字以内で入力してください。',
        ];
    }
}
