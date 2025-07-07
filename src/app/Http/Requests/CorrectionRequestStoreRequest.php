<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth; // 認証ユーザーの確認のために使用

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
     * リクエストに適用されるバリデーションルールを取得します。
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:50', 'in:打刻ミス,休憩時間修正,出勤日修正,退勤日修正,その他'], // 修正の種類
            'requested_check_in_time' => ['nullable', 'string', 'date_format:H:i'], // 修正希望の出勤時刻 (H:i形式)
            // ↓↓↓ ここを修正しました ↓↓↓
            'requested_check_out_time' => ['nullable', 'string', 'date_format:H:i', 'after:requested_check_in_time'], // 修正希望の退勤時刻 (H:i形式)
            // ↑↑↑ ここを修正しました ↑↑↑
            'requested_breaks' => ['nullable', 'array'], // 修正希望の休憩情報（配列形式で受け取る）
            'requested_breaks.*.start' => ['required_with:requested_breaks', 'string', 'date_format:H:i'], // 各休憩開始時刻
            'requested_breaks.*.end' => ['required_with:requested_breaks', 'string', 'date_format:H:i', 'after:requested_breaks.*.start'], // 各休憩終了時刻
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
            'type' => '修正の種類',
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
     * 定義されたバリデーションルールに対応するエラーメッセージを取得します。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => ':attributeは必須です。',
            'type.string' => ':attributeは文字列で入力してください。',
            'type.max' => ':attributeは:max文字以内で入力してください。',
            'type.in' => '選択された:attributeが無効です。',

            'requested_check_in_time.string' => ':attributeは文字列で入力してください。',
            'requested_check_in_time.date_format' => ':attributeはHH:MM形式で入力してください。',

            'requested_check_out_time.string' => ':attributeは文字列で入力してください。',
            'requested_check_out_time.date_format' => ':attributeはHH:MM形式で入力してください。',
            'requested_check_out_time.after' => ':attributeは希望出勤時刻より後の時刻を入力してください。',

            'requested_breaks.array' => ':attributeは配列で入力してください。',

            'requested_breaks.*.start.required_with' => '休憩開始時刻は必須です。',
            'requested_breaks.*.start.string' => '休憩開始時刻は文字列で入力してください。',
            'requested_breaks.*.start.date_format' => '休憩開始時刻はHH:MM形式で入力してください。',

            'requested_breaks.*.end.required_with' => '休憩終了時刻は必須です。',
            'requested_breaks.*.end.string' => '休憩終了時刻は文字列で入力してください。',
            'requested_breaks.*.end.date_format' => '休憩終了時刻はHH:MM形式で入力してください。',
            'requested_breaks.*.end.after' => '休憩終了時刻は休憩開始時刻より後の時刻を入力してください。',

            'reason.required' => ':attributeは必須です。',
            'reason.string' => ':attributeは文字列で入力してください。',
            'reason.max' => ':attributeは:max文字以内で入力してください。',
        ];
    }
}
