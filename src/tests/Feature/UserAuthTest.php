<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserAuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * ID 1: ユーザー登録機能
     * 名前が未入力の場合、バリデーションメッセージが表示されることをテスト
     * @test
     */
    public function test_registration_fails_with_missing_name(): void
    {
        $response = $this->post(route('register'), [
            'name' => '', // 未入力
            'email' => $this->faker->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('name', 'お名前を入力してください');
        $this->assertGuest();
    }

    /**
     * ID 1: ユーザー登録機能
     * メールアドレスが未入力の場合、バリデーションメッセージが表示されることをテスト
     * @test
     */
    public function test_registration_fails_with_missing_email(): void
    {
        $response = $this->post(route('register'), [
            'name' => $this->faker->name,
            'email' => '', // 未入力
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email', 'メールアドレスを入力してください');
        $this->assertGuest();
    }

    /**
     * ID 1: ユーザー登録機能
     * パスワードが8文字未満の場合、バリデーションメッセージが表示されることをテスト
     * @test
     */
    public function test_registration_fails_with_short_password(): void
    {
        $response = $this->post(route('register'), [
            'name' => $this->faker->name,
            'email' => $this->faker->safeEmail,
            'password' => 'short', // 8文字未満
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors('password', 'パスワードは8文字以上で入力してください');
        $this->assertGuest();
    }

    /**
     * ID 1: ユーザー登録機能
     * パスワードが一致しない場合、バリデーションメッセージが表示されることをテスト
     * @test
     */
    public function test_registration_fails_if_passwords_do_not_match(): void
    {
        $response = $this->post(route('register'), [
            'name' => $this->faker->name,
            'email' => $this->faker->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'mismatch', // 不一致
        ]);

        $response->assertSessionHasErrors('password', 'パスワードと一致しません');
        $this->assertGuest();
    }

    /**
     * ID 1: ユーザー登録機能
     * パスワードが未入力の場合、バリデーションメッセージが表示されることをテスト
     * @test
     */
    public function test_registration_fails_with_missing_password(): void
    {
        $response = $this->post(route('register'), [
            'name' => $this->faker->name,
            'email' => $this->faker->safeEmail,
            'password' => '', // 未入力
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors('password', 'パスワードを入力してください');
        $this->assertGuest();
    }

    /**
     * ID 1: ユーザー登録機能
     * 有効なデータでユーザー登録が成功することをテスト
     * @test
     */
    public function test_user_can_register_with_valid_data(): void
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post(route('register'), $userData);

        // 成功後、リダイレクトされることを確認
        // Fortifyのデフォルト動作は'/dashboard'ですが、routes/web.phpでは'/mypage'にリダイレクト
        $response->assertRedirect(route('mypage'));

        // データベースにユーザーが作成されたことを確認
        $this->assertDatabaseHas('users', [
            'email' => 'testuser@example.com',
        ]);

        // ユーザーが認証済みであることを確認
        $this->assertAuthenticated();
    }

    /**
     * ID 2: ログイン認証機能（一般ユーザー）
     * 有効なクレデンシャルでログインが成功することをテスト
     * @test
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('mypage'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * ID 2: ログイン認証機能（一般ユーザー）
     * 無効なクレデンシャルでログインが失敗することをテスト
     * @test
     */
    public function test_user_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'invalid-password', // 無効なパスワード
        ]);

        $response->assertSessionHasErrors('email', 'ログイン情報が登録されていません');
        $this->assertGuest();
    }

    /**
     * ID 2 : ログイン認証機能(一般ユーザー)
     * メールアドレスが未入力の場合、バリデーションメッセージが表示されることをテスト
     * @test
     */
    public function test_login_fails_with_missing_email(): void
    {
        $response = $this->post(route('login'), [
            'email' => '', // 未入力
            'password' => 'password123',
        ]);
        $response->assertSessionHasErrors('email', 'メールアドレスを入力してください');
        $this->assertGuest();
    }

    /**
     * ID 2 : ログイン認証機能（一般ユーザー）
     * パスワードが未入力の場合、バリデーションメッセージが表示されることをテスト
     * @test
     */
    public function test_login_fails_with_missing_password(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => '', // 未入力
        ]);

        $response->assertSessionHasErrors('password', 'パスワードを入力してください');
        $this->assertGuest();
    }

    /**
     * ID 3: ログイン認証機能（管理者）
     * 無効なクレデンシャルでログインが失敗することをテスト
     * @test
     */
    public function test_admin_login_fails_with_invalid_credentials(): void
    {
        $this->seed(RolesTableSeeder::class);
        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $admin = User::factory()->create(['role_id' => $adminRole->id, 'password' => bcrypt('password')]);

        $response = $this->post(route('admin.login'), [
            'email' => $admin->email,
            'password' => 'invalid-password', // 無効なパスワード
        ]);

        $response->assertSessionHasErrors('email', 'ログイン情報が登録されていません');
        $this->assertGuest('admin');
    }

    /**
     * ID 3: ログイン認証機能（管理者）
     * メールアドレスが未入力の場合、バリデーションメッセージが表示されることをテスト
     * @test
     */
    public function test_admin_login_fails_with_missing_email(): void
    {
        $response = $this->post(route('admin.login'), [
            'email' => '', // 未入力
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email', 'メールアドレスを入力してください');
        $this->assertGuest('admin');
    }

    /**
     * ID 3: ログイン認証機能（管理者）
     * パスワードが未入力の場合、バリデーションメッセージが表示されることをテスト
     * @test
     */
    public function test_admin_login_fails_with_missing_password(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('admin.login'), [
            'email' => $user->email,
            'password' => '', // 未入力
        ]);

        $response->assertSessionHasErrors('password', 'パスワードを入力してください');
        $this->assertGuest('admin');
    }

    /**
     * ログアウト機能
     * ログイン済みユーザーがログアウトできることをテスト
     * @test
     */
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('logout'));

        $response->assertRedirect('login');
        $this->assertGuest();
    }
}
