<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth; // 認証ユーザーの確認のために使用
use Carbon\Carbon; // 日付・時刻操作のためにCarbonを使用

class AdminAttendanceUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * リクエストを行う権限があるかどうかを判定します。
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // ログイン中の管理ユーザーのみがこのリクエストを行えるように許可します。
        // AuthServiceProviderで 'admin-access' Gateが定義されていることを前提とします。
        return Auth::check() && Auth::user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     * リクエストに適用されるバリデーションルールを取得します。
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'check_in_time' => ['nullable', 'string', 'date_format:H:i'], // 修正希望の出勤時刻 (H:i形式)
            'check_out_time' => ['nullable', 'string', 'date_format:H:i', 'after:check_in_time_if_present'], // 修正希望の退勤時刻 (H:i形式)
            'breaks' => ['nullable', 'array'], // 休憩情報（配列形式で受け取る）
            'breaks.*.start' => ['required_with:breaks', 'string', 'date_format:H:i'], // 各休憩開始時刻
            'breaks.*.end' => ['required_with:breaks', 'string', 'date_format:H:i', 'after:breaks.*.start'], // 各休憩終了時刻
            'remarks' => ['nullable', 'string', 'max:1000'], // 備考欄
        ];
    }

    /**
     * Configure the validator instance.
     * バリデータインスタンスを設定します。
     * クロスフィールドバリデーションルールなどを定義します。
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $checkInTime = $this->input('check_in_time');
            $checkOutTime = $this->input('check_out_time');

            // 出勤時間が退勤時間より後になっている場合のバリデーション
            if ($checkInTime && $checkOutTime) {
                // 同じ日付で比較するためにダミーの日付を使用
                $dummyDate = Carbon::today()->toDateString();
                $fullCheckInTime = Carbon::parse($dummyDate . ' ' . $checkInTime);
                $fullCheckOutTime = Carbon::parse($dummyDate . ' ' . $checkOutTime);

                if ($fullCheckInTime->greaterThan($fullCheckOutTime)) {
                    $validator->errors()->add('check_out_time', '退勤時刻は出勤時刻より後の時刻を入力してください。');
                }
            }

            // 休憩時間と出退勤時間の関係のバリデーション
            $breaks = $this->input('breaks');
            if (!empty($breaks) && $checkInTime && $checkOutTime) {
                $dummyDate = Carbon::today()->toDateString();
                $fullCheckInTime = Carbon::parse($dummyDate . ' ' . $checkInTime);
                $fullCheckOutTime = Carbon::parse($dummyDate . ' ' . $checkOutTime);

                foreach ($breaks as $key => $break) {
                    $breakStartTime = $break['start'] ?? null;
                    $breakEndTime = $break['end'] ?? null;

                    if ($breakStartTime && $breakEndTime) {
                        $fullBreakStartTime = Carbon::parse($dummyDate . ' ' . $breakStartTime);
                        $fullBreakEndTime = Carbon::parse($dummyDate . ' ' . $breakEndTime);

                        // 休憩開始時間が勤務開始時刻より前、または終了時刻より後になっていないか
                        if ($fullBreakStartTime->lessThan($fullCheckInTime) || $fullBreakStartTime->greaterThan($fullCheckOutTime)) {
                            $validator->errors()->add("breaks.$key.start", '休憩開始時刻は出勤時刻と退勤時刻の間に設定してください。');
                        }
                        // 休憩終了時間が勤務開始時刻より前、または終了時刻より後になっていないか
                        if ($fullBreakEndTime->lessThan($fullCheckInTime) || $fullBreakEndTime->greaterThan($fullCheckOutTime)) {
                            $validator->errors()->add("breaks.$key.end", '休憩終了時刻は出勤時刻と退勤時刻の間に設定してください。');
                        }
                        // 休憩開始時間が休憩終了時間より後になっていないか（基本的なチェックはrules()にもあるが、念のため）
                        if ($fullBreakStartTime->greaterThanOrEqualTo($fullBreakEndTime)) {
                            $validator->errors()->add("breaks.$key.end", '休憩終了時刻は休憩開始時刻より後の時刻を入力してください。');
                        }
                    }
                }
            }
        });
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
            'check_in_time' => '出勤時刻',
            'check_out_time' => '退勤時刻',
            'breaks' => '休憩時間',
            'breaks.*.start' => '休憩開始時刻',
            'breaks.*.end' => '休憩終了時刻',
            'remarks' => '備考',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     * 定義されたバリデーションルールに対応するエラーメッセージを取得します。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'check_in_time.string' => ':attributeは文字列で入力してください。',
            'check_in_time.date_format' => ':attributeはHH:MM形式で入力してください。',

            'check_out_time.string' => ':attributeは文字列で入力してください。',
            'check_out_time.date_format' => ':attributeはHH:MM形式で入力してください。',
            'check_out_time.after' => '退勤時刻は出勤時刻より後の時刻を入力してください。',

            'breaks.array' => ':attributeは配列で入力してください。',

            'breaks.*.start.required_with' => '休憩開始時刻は必須です。',
            'breaks.*.start.string' => '休憩開始時刻は文字列で入力してください。',
            'breaks.*.start.date_format' => '休憩開始時刻はHH:MM形式で入力してください。',

            'breaks.*.end.required_with' => '休憩終了時刻は必須です。',
            'breaks.*.end.string' => '休憩終了時刻は文字列で入力してください。',
            'breaks.*.end.date_format' => '休憩終了時刻はHH:MM形式で入力してください。',
            'breaks.*.end.after' => '休憩終了時刻は休憩開始時刻より後の時刻を入力してください。',

            'remarks.string' => ':attributeは文字列で入力してください。',
            'remarks.max' => ':attributeは:max文字以内で入力してください。',
            // 'remarks.required' => '備考を記入してください', // テストケースと異なりnullableにしたためコメントアウト。必要に応じて有効化
        ];
    }
}
