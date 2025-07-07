<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClockOutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // ここを true にすることで、認証済みユーザーからのリクエストを許可します。
        // より複雑な権限チェックが必要な場合は、ここにロジックを記述します。
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        // 退勤処理で特別なバリデーションが必要ない場合、空の配列を返します。
        // 例: 'user_id' => 'required|exists:users,id' など
        return [
            //
        ];
    }
}
