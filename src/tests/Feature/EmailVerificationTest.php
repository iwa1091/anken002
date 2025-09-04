<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Notification;
use App\Models\User;
// この行は、Laravelのデフォルト通知クラスを参照するように変更します。
use Illuminate\Auth\Notifications\VerifyEmail as EmailVerificationNotification;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /**
     * ID 15: メール認証
     * 新規ユーザー登録時にメール認証通知が送信されることをテスト
     */
    public function test_new_user_receives_email_verification_notification_on_registration(): void
    {
        // Notificationの送信をモック化
        Notification::fake();

        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        // ユーザー登録を実行
        $response = $this->post(route('register'), $userData);

        // 実際のリダイレクト先 '/mypage' を確認
        $response->assertRedirect('/mypage');

        // 新規作成されたユーザーを取得
        $user = User::where('email', $userData['email'])->first();

        // ユーザーにメール認証通知が送信されたことを確認
        // ここでは、デフォルトのLaravel通知クラスが送信されたことを確認します。
        Notification::assertSentTo(
            $user,
            EmailVerificationNotification::class
        );
    }
    
    /**
     * ID 16: メール認証リンク
     * 有効な認証リンクでユーザーのメールアドレスが認証されることをテスト
     */
    public function test_valid_verification_link_verifies_user_email(): void
    {
        // 未認証のユーザーを作成
        $user = User::factory()->create(['email_verified_at' => null]);
        
        // ユーザーをログイン状態にする
        $this->actingAs($user);

        // 有効な認証URLを生成
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60), // 有効期限
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->get($verificationUrl);

        // クエリパラメータを含めた完全なリダイレクトURLを確認
        $response->assertRedirect('/mypage?verified=1');

        // データベース上のユーザーのemail_verified_atが更新されたことを確認
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
    
    /**
     * ID 16: メール認証リンク
     * 無効なハッシュ値の認証リンクでは認証されないことをテスト
     */
    public function test_invalid_hash_in_verification_link_does_not_verify_email(): void
    {
        // 未認証のユーザーを作成
        $user = User::factory()->create(['email_verified_at' => null]);
        
        // ユーザーをログイン状態にする
        $this->actingAs($user);

        // 無効なハッシュ値を含む認証URLを生成
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => 'invalid-hash']
        );

        $response = $this->get($verificationUrl);
        $response->assertStatus(403);

        // データベース上のユーザーのemail_verified_atが未更新であることを確認
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
    
    /**
     * ID 16: メール認証リンク
     * 期限切れの認証リンクでは認証されないことをテスト
     */
    public function test_expired_verification_link_does_not_verify_email(): void
    {
        // 未認証のユーザーを作成
        $user = User::factory()->create(['email_verified_at' => null]);
        
        // ユーザーをログイン状態にする
        $this->actingAs($user);

        // 期限切れの認証URLを生成
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinutes(5),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->get($verificationUrl);
        $response->assertStatus(403);

        // データベース上のユーザーのemail_verified_atが未更新であることを確認
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    /**
     * ID 17: メール再送信
     * 認証メールの再送信リクエストが正しく処理されることをテスト
     */
    public function test_resending_verification_email_works_correctly(): void
    {
        // Notificationの送信をモック化
        Notification::fake();
        
        // 未認証のユーザーを作成
        $user = User::factory()->create(['email_verified_at' => null]);
        
        // ユーザーをログイン状態にする
        $this->actingAs($user);

        // 再送信リクエストを送信
        $response = $this->post('/email/verification-notification');

        // 再送信成功後のリダイレクト先を修正
        $response->assertRedirect('/');
        
        // 認証メールが再送信されたことを確認
        // ここでも、デフォルトのLaravel通知クラスが送信されたことを確認します。
        Notification::assertSentTo(
            $user,
            EmailVerificationNotification::class
        );
    }
}
