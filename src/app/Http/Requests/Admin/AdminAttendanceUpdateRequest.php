<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AdminAttendanceUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Auth::guard('admin')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'check_in_time' => [
                'nullable',
                'string',
                'date_format:H:i',
            ],
            'check_out_time' => [
                'nullable',
                'string',
                'date_format:H:i',
                'after_or_equal:check_in_time',
            ],
            'breaks' => [
                'nullable',
                'array',
            ],
            // 休憩時間のバリデーションをより詳細なルールに分割
            'breaks.*.start' => [
                'nullable',
                'date_format:H:i',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1];
                    $checkInTime = $this->input('check_in_time');
                    $breakEndTime = $this->input("breaks.{$index}.end");

                    if (empty($value) && empty($breakEndTime)) {
                        return;
                    }

                    if (empty($value)) {
                        $fail("休憩" . ($index + 1) . "の開始時刻は必須です。");
                        return;
                    }

                    if (empty($checkInTime)) {
                        $fail('出勤時刻が入力されていない場合、休憩時間は入力できません。');
                        return;
                    }
                    
                    try {
                        $checkInCarbon = Carbon::createFromFormat('H:i', $checkInTime);
                        $breakStartCarbon = Carbon::createFromFormat('H:i', $value);
                        if ($breakStartCarbon->lt($checkInCarbon)) {
                            $fail('出勤時間もしくは退勤時間が不適切な値です');
                        }
                    } catch (\Exception $e) {
                        $fail("休憩" . ($index + 1) . "の時刻フォーマットが不正です。");
                    }
                },
            ],
            'breaks.*.end' => [
                'nullable',
                'date_format:H:i',
                'after:breaks.*.start',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1];
                    $checkOutTime = $this->input('check_out_time');
                    $breakStartTime = $this->input("breaks.{$index}.start");

                    if (empty($value) && empty($breakStartTime)) {
                        return;
                    }
                    
                    if (empty($value)) {
                        $fail("休憩" . ($index + 1) . "の終了時刻は必須です。");
                        return;
                    }

                    if (empty($checkOutTime)) {
                        $fail('出勤時刻が入力されていない場合、休憩時間は入力できません。');
                        return;
                    }
                    
                    try {
                        $checkOutCarbon = Carbon::createFromFormat('H:i', $checkOutTime);
                        $breakEndCarbon = Carbon::createFromFormat('H:i', $value);
                        if ($breakEndCarbon->gt($checkOutCarbon)) {
                            $fail('出勤時間もしくは退勤時間が不適切な値です');
                        }
                    } catch (\Exception $e) {
                        $fail("休憩" . ($index + 1) . "の時刻フォーマットが不正です。");
                    }
                },
            ],
            'remarks' => [
                'required', 
                'string', 
                'max:1000'
            ],
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'check_in_time' => '出勤時刻',
            'check_out_time' => '退勤時刻',
            'breaks.*.start' => '休憩開始時刻',
            'breaks.*.end' => '休憩終了時刻',
            'remarks' => '備考',
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'check_in_time.date_format' => ':attributeはHH:MM形式で入力してください。',
            'check_out_time.date_format' => ':attributeはHH:MM形式で入力してください。',
            'check_out_time.after_or_equal' => '出勤時間もしくは退勤時間が不適切な値です',
            'breaks.*.end.after' => '休憩終了時刻は開始時刻より後の時刻を入力してください。',
            'remarks.required' => '備考を記入してください',
            'remarks.string' => ':attributeは文字列で入力してください。',
            'remarks.max' => ':attributeは:max文字以内で入力してください。',
        ];
    }
}
