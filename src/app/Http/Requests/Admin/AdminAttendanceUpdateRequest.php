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
                function ($attribute, $value, $fail) {
                    $checkInTime = $this->input('check_in_time');
                    $checkOutTime = $this->input('check_out_time');
                    if (!empty($checkInTime) && !empty($checkOutTime)) {
                        try {
                            $checkInCarbon = Carbon::createFromFormat('H:i', $checkInTime);
                            $checkOutCarbon = Carbon::createFromFormat('H:i', $checkOutTime);
                        } catch (\Exception $e) {
                            return;
                        }

                        foreach ($value as $index => $break) {
                            $breakStartTime = $break['start'] ?? null;
                            $breakEndTime = $break['end'] ?? null;

                            if (empty($breakStartTime) && empty($breakEndTime)) {
                                continue;
                            }

                            if (empty($breakStartTime) || empty($breakEndTime)) {
                                $fail("休憩" . ($index + 1) . "の開始時刻と終了時刻は両方入力するか、両方空にしてください。");
                                continue;
                            }

                            try {
                                $breakStartCarbon = Carbon::createFromFormat('H:i', $breakStartTime);
                                $breakEndCarbon = Carbon::createFromFormat('H:i', $breakEndTime);
                            } catch (\Exception $e) {
                                $fail("休憩" . ($index + 1) . "の時刻フォーマットが不正です。");
                                continue;
                            }

                            if ($breakStartCarbon->lt($checkInCarbon) || $breakEndCarbon->gt($checkOutCarbon)) {
                                $fail('出勤時間もしくは退勤時間が不適切な値です');
                            }
                            if ($breakStartCarbon->greaterThanOrEqualTo($breakEndCarbon)) {
                                $fail("休憩" . ($index + 1) . "の終了時刻は開始時刻より後の時刻を入力してください。");
                            }
                        }
                    } else {
                        foreach ($value as $index => $break) {
                            if (!empty($break['start']) || !empty($break['end'])) {
                                $fail('出勤・退勤時刻が入力されていない場合、休憩時間は入力できません。');
                                break;
                            }
                        }
                    }
                },
            ],
            'breaks.*.start' => ['nullable', 'string', 'date_format:H:i'],
            'breaks.*.end' => ['nullable', 'string', 'date_format:H:i', 'after:breaks.*.start'],
            'remarks' => ['required', 'string', 'max:1000'],
        ];
    }

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

    public function messages(): array
    {
        return [
            'check_in_time.date_format' => ':attributeはHH:MM形式で入力してください。',
            'check_out_time.date_format' => ':attributeはHH:MM形式で入力してください。',
            'check_out_time.after_or_equal' => '出勤時間もしくは退勤時間が不適切な値です',
            'breaks.*.start.date_format' => '休憩開始時刻はHH:MM形式で入力してください。',
            'breaks.*.end.date_format' => '休憩終了時刻はHH:MM形式で入力してください。',
            'breaks.*.end.after' => '休憩終了時刻は休憩開始時刻より後の時刻を入力してください。',
            'remarks.required' => '備考を記入してください',
            'remarks.string' => ':attributeは文字列で入力してください。',
            'remarks.max' => ':attributeは:max文字以内で入力してください。',
        ];
    }
}
