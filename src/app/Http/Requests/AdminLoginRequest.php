<?php

namespace App\Http\Requests\Admin\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AdminLoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * リクエストを行う権限があるかどうかを判定します。
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // ゲスト（未ログイン）ユーザーのみが管理者ログインリクエストを行うことを許可します。
        return !Auth::check();
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
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
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
            'email.required' => 'メールアドレスは必須です。',
            'email.string' => 'メールアドレスは文字列で入力してください。',
            'email.email' => '有効なメールアドレス形式で入力してください。',
            'password.required' => 'パスワードは必須です。',
            'password.string' => 'パスワードは文字列で入力してください。',
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     * リクエストの認証情報を認証しようとします。
     * これはコントローラーから呼び出されることを想定しています。
     *
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            $this->throwValidationException('email', '指定された認証情報が弊社記録と一致しません。');
        }

        // ログインしたユーザーが管理者ロールを持っているか確認
        $user = Auth::user();
        if ($user && ! $user->isAdmin()) {
            Auth::logout(); // 管理者ではない場合、セッションを破棄
            $this->throwValidationException('email', '指定された認証情報に一致する管理ユーザーが見つかりません。');
        }
    }

    /**
     * Throws a validation exception with a custom message.
     * カスタムメッセージでバリデーション例外をスローします。
     *
     * @param string $field
     * @param string $message
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function throwValidationException(string $field, string $message): void
    {
        throw \Illuminate\Validation\ValidationException::withMessages([
            $field => $message,
        ])->onlyInput($field);
    }
}
