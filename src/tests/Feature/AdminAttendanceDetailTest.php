<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Gate;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /** @var \App\Models\User */
    protected $adminUser;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // テスト用の管理者ロールを作成
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // 管理者ユーザーを作成
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id, 'name' => 'テスト管理者']);

        // Gateの定義をモックし、管理者アクセスを常に許可するように設定
        Gate::define('admin-access', function ($user) {
            return true;
        });
    }

    /**
     * Test that the selected attendance data is displayed correctly on the detail screen.
     *
     * @return void
     */
    public function test_selected_attendance_data_is_displayed_on_detail_screen()
    {
        // 1. Log in as an admin user with the 'admin' guard
        $this->actingAs($this->adminUser, 'admin');

        // 勤怠情報を持つユーザーに特定の名前を設定
        $testUser = User::factory()->create(['name' => 'テストユーザー']);

        // Attendanceモデルのfillableプロパティに合わせてデータを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $testUser->id, // 勤怠情報とテストユーザーを紐付け
            'date' => '2024-08-29',
            'check_in_time' => '2024-08-29 09:00:00',
            'check_out_time' => '2024-08-29 18:00:00',
            'break_time' => 3600, // 60分を秒で表現
            'working_time' => 28800, // 8時間を秒で表現
        ]);

        // 2. Open the attendance detail page
        $response = $this->get(route('admin.attendance.show', ['id' => $attendance->id]));

        // Check if the page is loaded successfully
        $response->assertStatus(200);

        // Check if the attendance data is displayed on the page
        // HTMLの表示形式に合わせてアサート
        $response->assertSee($testUser->name); // ユーザー名をアサート
        $response->assertSee('2024年');
        $response->assertSee('8月29日');


        // 実際のHTMLに表示されているであろう時間をアサート
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * Test that an error message is displayed when check-in time is after check-out time.
     *
     * @return void
     */
    public function test_error_message_is_displayed_when_check_in_time_is_after_check_out_time()
    {
        // 管理者ユーザーとしてログイン
        $this->actingAs($this->adminUser, 'admin');

        // テスト用のユーザーと勤怠データを作成
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-08-29',
            'check_in_time' => '2024-08-29 09:00:00',
            'check_out_time' => '2024-08-29 18:00:00',
            'break_time' => 3600,
            'working_time' => 28800,
        ]);

        // 不適切な値で勤怠情報を更新するリクエストを送信
        $response = $this->put(route('admin.attendance.update', ['id' => $attendance->id]), [
            'date' => '2024-08-29',
            'check_in_time' => '20:00', // 退勤時間より後の出勤時間
            'check_out_time' => '18:00',
            'break_time' => 3600,
            'working_time' => 28800,
            'remarks' => '備考欄のテスト',
        ]);

        // リダイレクトを確認 (バリデーションエラーが発生した場合)
        $response->assertStatus(302);

        // 'check_out_time'キーのエラーがセッションに存在することを確認
        $response->assertSessionHasErrors(['check_out_time']);

        // セッション内の特定のエラーメッセージが正しいことをアサート
        $this->assertStringContainsString('出勤時間もしくは退勤時間が不適切な値です', session('errors')->first('check_out_time'));
    }

    /**
     * Test that an error message is displayed when break start time is after check-out time.
     *
     * @return void
     */
    public function test_error_message_is_displayed_when_break_start_time_is_after_check_out_time()
    {
        // 管理者ユーザーとしてログイン
        $this->actingAs($this->adminUser, 'admin');

        // テスト用のユーザーと勤怠データを作成
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-08-29',
            'check_in_time' => '2024-08-29 09:00:00',
            'check_out_time' => '2024-08-29 18:00:00',
            'break_time' => 0,
            'working_time' => 0,
        ]);

        // 不適切な値（休憩開始時間が退勤時間より後）で勤怠情報を更新するリクエストを送信
        $response = $this->put(route('admin.attendance.update', ['id' => $attendance->id]), [
            'date' => '2024-08-29',
            'check_in_time' => '09:00',
            'check_out_time' => '18:00',
            'breaks' => [
                [
                    'start' => '19:00', // 退勤時間より後の休憩開始時間
                    'end' => '20:00'
                ]
            ],
            'remarks' => '備考欄のテスト',
        ]);

        // リダイレクトを確認 (バリデーションエラーが発生した場合)
        $response->assertStatus(302);

        // セッションにエラーメッセージが含まれていることを確認
        $response->assertSessionHasErrors();

        // 任意のエラーメッセージの中に、期待する文字列が含まれていることをアサート
        $foundError = false;
        foreach (session('errors')->getMessages() as $errors) {
            foreach ($errors as $error) {
                if (str_contains($error, '出勤時間もしくは退勤時間が不適切な値です')) {
                    $foundError = true;
                    break 2;
                }
            }
        }
        $this->assertTrue($foundError, 'Expected error message not found in session errors.');
    }

    /**
     * Test that an error message is displayed when break end time is after check-out time.
     *
     * @return void
     */
    public function test_error_message_is_displayed_when_break_end_time_is_after_check_out_time()
    {
        // 管理者ユーザーとしてログイン
        $this->actingAs($this->adminUser, 'admin');

        // テスト用のユーザーと勤怠データを作成
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-08-29',
            'check_in_time' => '2024-08-29 09:00:00',
            'check_out_time' => '2024-08-29 18:00:00',
            'break_time' => 0,
            'working_time' => 0,
        ]);

        // 不適切な値（休憩終了時間が退勤時間より後）で勤怠情報を更新するリクエストを送信
        $response = $this->put(route('admin.attendance.update', ['id' => $attendance->id]), [
            'date' => '2024-08-29',
            'check_in_time' => '09:00',
            'check_out_time' => '18:00',
            'breaks' => [
                [
                    'start' => '13:00',
                    'end' => '19:00' // 退勤時間より後の休憩終了時間
                ]
            ],
            'remarks' => '備考欄のテスト',
        ]);

        // リダイレクトを確認 (バリデーションエラーが発生した場合)
        $response->assertStatus(302);

        // セッションにエラーメッセージが含まれていることを確認
        $response->assertSessionHasErrors();

        // 任意のエラーメッセージの中に、期待する文字列が含まれていることをアサート
        $foundError = false;
        foreach (session('errors')->getMessages() as $errors) {
            foreach ($errors as $error) {
                if (str_contains($error, '出勤時間もしくは退勤時間が不適切な値です')) {
                    $foundError = true;
                    break 2;
                }
            }
        }
        $this->assertTrue($foundError, 'Expected error message not found in session errors.');
    }

    /**
     * Test that an error message is displayed when remarks is empty.
     *
     * @return void
     */
    public function test_error_message_is_displayed_when_remarks_is_empty()
    {
        // 管理者ユーザーとしてログイン
        $this->actingAs($this->adminUser, 'admin');

        // テスト用のユーザーと勤怠データを作成
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-08-29',
            'check_in_time' => '2024-08-29 09:00:00',
            'check_out_time' => '2024-08-29 18:00:00',
            'break_time' => 3600,
            'working_time' => 28800,
            'remarks' => '備考欄のテスト',
        ]);

        // 備考欄を空にして勤怠情報を更新するリクエストを送信
        $response = $this->put(route('admin.attendance.update', ['id' => $attendance->id]), [
            'date' => '2024-08-29',
            'check_in_time' => '09:00',
            'check_out_time' => '18:00',
            'break_time' => 3600,
            'working_time' => 28800,
            'remarks' => '', // 備考欄を空にする
        ]);

        // リダイレクトを確認
        $response->assertStatus(302);

        // 'remarks'キーのエラーがセッションに存在することを確認
        $response->assertSessionHasErrors(['remarks']);

        // セッション内のエラーメッセージが正しいことをアサート
        $this->assertStringContainsString('備考を記入してください', session('errors')->first('remarks'));
    }
}
