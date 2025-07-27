<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
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
        // 管理者ガードで認証しているユーザーのみがリクエストを許可されます。
        // AuthServiceProviderで定義された'admin-access'ゲートも考慮されます。
        return Auth::guard('admin')->check();
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
            // 出勤・退勤時間
            'check_in_time' => ['nullable', 'string', 'date_format:H:i'],
            'check_out_time' => [
                'nullable',
                'string',
                'date_format:H:i',
                // FN13: 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
                'after_or_equal:check_in_time',
            ],

            // 休憩時間
            'breaks' => [
                'nullable',
                'array',
                // カスタムバリデーション：休憩時間が勤務時間内にあるか、および開始・終了のペアチェック
                function ($attribute, $value, $fail) {
                    $checkInTime = $this->input('check_in_time');
                    $checkOutTime = $this->input('check_out_time');

                    // 出勤・退勤時刻が両方入力されている場合にのみ、休憩時間の勤務時間内チェックを行う
                    if (!empty($checkInTime) && !empty($checkOutTime)) {
                        try {
                            $checkInCarbon = Carbon::createFromFormat('H:i', $checkInTime);
                            $checkOutCarbon = Carbon::createFromFormat('H:i', $checkOutTime);
                        } catch (\Exception $e) {
                            // check_in_time/check_out_timeのフォーマットが不正な場合は、
                            // date_formatルールで既にエラーになるため、ここでは何もしない
                            return;
                        }

                        foreach ($value as $index => $break) {
                            $breakStartTime = $break['start'] ?? null;
                            $breakEndTime = $break['end'] ?? null;

                            // 休憩開始時刻と終了時刻が両方空の場合はスキップ
                            if (empty($breakStartTime) && empty($breakEndTime)) {
                                continue;
                            }

                            // 休憩開始時刻または終了時刻の片方だけが入力されている場合はエラー
                            if (empty($breakStartTime) || empty($breakEndTime)) {
                                $fail("休憩{ $index + 1 }の開始時刻と終了時刻は両方入力するか、両方空にしてください。");
                                continue;
                            }

                            try {
                                $breakStartCarbon = Carbon::createFromFormat('H:i', $breakStartTime);
                                $breakEndCarbon = Carbon::createFromFormat('H:i', $breakEndTime);
                            } catch (\Exception $e) {
                                // フォーマットエラーはdate_formatで捕捉されるが、念のため
                                $fail("休憩{ $index + 1 }の時刻フォーマットが不正です。");
                                continue;
                            }

                            // FN13: 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
                            // FN13: 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
                            // 休憩時間が勤務時間外の場合
                            if ($breakStartCarbon->lt($checkInCarbon) || $breakEndCarbon->gt($checkOutCarbon)) {
                                $fail('出勤時間もしくは退勤時間が不適切な値です');
                            }
                            // 休憩開始時間が休憩終了時間より後になっている場合 (afterルールと重複するが、カスタムでまとめてメッセージを出すため)
                            if ($breakStartCarbon->greaterThanOrEqualTo($breakEndCarbon)) {
                                $fail("休憩{ $index + 1 }の終了時刻は開始時刻より後の時刻を入力してください。");
                            }
                        }
                    } else {
                        // 出勤・退勤時刻のいずれか、または両方が空の場合、
                        // 休憩時間も全て空であることを強制する（またはエラーとしない）
                        // ここでは、休憩時間が入力されている場合はエラーとする
                        foreach ($value as $index => $break) {
                            if (!empty($break['start']) || !empty($break['end'])) {
                                $fail('出勤・退勤時刻が入力されていない場合、休憩時間は入力できません。');
                                break; // 最初のエラーでループを抜ける
                            }
                        }
                    }
                },
            ],
            'breaks.*.start' => ['nullable', 'string', 'date_format:H:i'],
            'breaks.*.end' => ['nullable', 'string', 'date_format:H:i', 'after:breaks.*.start'], // 個別の休憩開始・終了時刻の順序チェック

            // 備考欄
            'remarks' => ['required', 'string', 'max:1000'], // FN13: 備考欄が未入力の場合のエラーメッセージが表示される
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
            'check_in_time.date_format' => ':attributeはHH:MM形式で入力してください。',
            'check_out_time.date_format' => ':attributeはHH:MM形式で入力してください。',
            'check_out_time.after_or_equal' => '出勤時間もしくは退勤時間が不適切な値です', // FN13

            'breaks.*.start.date_format' => '休憩開始時刻はHH:MM形式で入力してください。',
            'breaks.*.end.date_format' => '休憩終了時刻はHH:MM形式で入力してください。',
            'breaks.*.end.after' => '休憩終了時刻は休憩開始時刻より後の時刻を入力してください。', // 個別の休憩時間ペアのチェック

            'remarks.required' => '備考を記入してください', // FN13
            'remarks.string' => ':attributeは文字列で入力してください。',
            'remarks.max' => ':attributeは:max文字以内で入力してください。',
        ];
    }
}
