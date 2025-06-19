<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClockInRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * リクエストを行う権限があるかどうかを判定します。
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // ログイン済みのユーザーのみが出勤打刻を可能にする
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     * リクエストに適用されるバリデーションルールを取得します。
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        // 出勤打刻はユーザーからの特定の入力データを受け取ることは稀です。
        // 時刻はサーバー側で取得するため、ここでは入力データのバリデーションは不要です。
        // ただし、既にその日出勤しているかなどのビジネスロジックは
        // この後のコントローラーやサービス層で検証されます。
        return [
            // 必要に応じて、例えば隠しフィールド（hidden field）などがあればここにルールを追加します。
            // 例: 'some_token' => ['required', 'string'],
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
        // 現状、rules()メソッドにバリデーションルールがないため、カスタムメッセージも不要です。
        // もしルールを追加した場合、ここに対応するメッセージを記述します。
        return [
            // 'some_token.required' => 'トークンが不足しています。',
        ];
    }
}
