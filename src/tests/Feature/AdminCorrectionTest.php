<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;

class AdminCorrectionTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /**
     * @var User
     */
    protected $adminUser;

    /**
     * @var User
     */
    protected $generalUser;

    /**
     * @var Attendance
     */
    protected $attendance;

    /**
     * Setup the test environment.
     * テスト環境をセットアップする。
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // ロールを作成
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $generalRole = Role::factory()->create(['name' => 'staff']);

        // ロールを割り当ててユーザーを作成
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        $this->generalUser = User::factory()->create(['role_id' => $generalRole->id]);

        // 修正申請に関連する勤怠データを事前に作成する
        $this->attendance = Attendance::factory()->create(['user_id' => $this->generalUser->id]);
    }

    /**
     * Test that an admin can access the correction request list page.
     * 管理者ユーザーが修正申請一覧ページにアクセスできることをテストする
     *
     * @return void
     */
    public function test_admin_can_access_correction_list_page(): void
    {
        // actingAs() と withoutMiddleware() を組み合わせて認証ミドルウェアをバイパス
        $response = $this->withoutMiddleware(\App\Http\Middleware\Authenticate::class)
            ->actingAs($this->adminUser)
            ->get(route('admin.stamp_correction_request.list'));

        // レスポンスステータスが200であることを確認
        $response->assertOk();
    }

    /**
     * Test that a general user cannot access the correction request list page.
     * 一般ユーザーが修正申請一覧ページにアクセスできないことをテストする
     *
     * @return void
     */
    public function test_general_user_cannot_access_correction_list_page(): void
    {
        // actingAs() と withoutMiddleware() を組み合わせて認証ミドルウェアをバイパス
        $response = $this->withoutMiddleware(\App\Http\Middleware\Authenticate::class)
            ->actingAs($this->generalUser)
            ->get(route('admin.stamp_correction_request.list'));

        // レスポンスステータスが403であることを確認
        $response->assertForbidden();
    }

    /**
     * Test that an admin can approve a correction request.
     * 管理者が修正申請を承認できることをテストする
     *
     * @return void
     */
    public function test_admin_can_approve_correction_request(): void
    {
        // 勤怠修正内容を特定の値で定義
        $requestedCheckInTime = Carbon::parse('2023-01-01 09:00:00');
        $requestedCheckOutTime = Carbon::parse('2023-01-01 18:00:00');
        
        // 休憩時間の申請データを定義
        $requestedBreaks = [
            ['start' => '12:00:00', 'end' => '13:00:00'],
            ['start' => '15:00:00', 'end' => '15:30:00'],
        ];
        
        // 承認待ちの修正申請データを作成する
        $correction = CorrectionRequest::factory()->create([
            'user_id' => $this->generalUser->id,
            'attendance_id' => $this->attendance->id,
            'status' => 'pending',
            'requested_check_in_time' => $requestedCheckInTime,
            'requested_check_out_time' => $requestedCheckOutTime,
            // JSON文字列として保存
            'requested_breaks' => json_encode($requestedBreaks),
        ]);

        // actingAs() と withoutMiddleware() を組み合わせて認証ミドルウェアをバイパス
        // 'status' を明示的に 'approved' としてリクエストボディに追加
        $response = $this->withoutMiddleware(\App\Http\Middleware\Authenticate::class)
            ->actingAs($this->adminUser)
            ->post(route('admin.stamp_correction_request.approve', $correction), [
                'status' => 'approved'
            ]);

        // リダイレクトのステータスコードを検証する
        $response->assertStatus(302);

        // データベースに承認済みとして保存されたことを確認する
        $this->assertDatabaseHas('correction_requests', [
            'id' => $correction->id,
            'status' => 'approved',
        ]);

        // 勤怠データが修正申請の内容で更新されたことを確認する
        $this->attendance->refresh();

        // 勤務時間と休憩時間を秒単位で計算
        $expectedWorkingTimeInSeconds = $requestedCheckOutTime->diffInSeconds($requestedCheckInTime);
        $expectedBreakTimeInSeconds = 0;
        foreach ($requestedBreaks as $break) {
            $breakStart = Carbon::parse($break['start']);
            $breakEnd = Carbon::parse($break['end']);
            $expectedBreakTimeInSeconds += $breakEnd->diffInSeconds($breakStart);
        }
        $expectedWorkingTimeInSeconds -= $expectedBreakTimeInSeconds;

        $this->assertEquals($requestedCheckInTime->format('Y-m-d H:i:s'), $this->attendance->check_in_time->format('Y-m-d H:i:s'));
        $this->assertEquals($requestedCheckOutTime->format('Y-m-d H:i:s'), $this->attendance->check_out_time->format('Y-m-d H:i:s'));
        $this->assertEquals($expectedBreakTimeInSeconds, $this->attendance->break_time);
        $this->assertEquals($expectedWorkingTimeInSeconds, $this->attendance->working_time);
    }
    
    /**
     * ID 11: 勤怠修正機能
     * 修正申請が実行され、管理者の申請一覧画面に表示されることをテスト
     *
     * @return void
     */
    public function test_submitted_correction_request_is_displayed_on_admin_list_page(): void
    {
        // 修正申請データを作成
        $correction = CorrectionRequest::factory()->create([
            'user_id' => $this->generalUser->id,
            'attendance_id' => $this->attendance->id,
            'status' => 'pending',
        ]);

        // 管理者ユーザーとして一覧画面にアクセス
        $response = $this->withoutMiddleware(\App\Http\Middleware\Authenticate::class)
            ->actingAs($this->adminUser)
            ->get(route('admin.stamp_correction_request.list'));

        // レスポンスステータスが200であることを確認
        $response->assertOk();

        // 作成した申請情報が画面に表示されていることを確認
        // 例: ユーザー名、日付、ステータスなど
        $response->assertSeeText($this->generalUser->name);
        // ここを修正：ビューの表示形式に合わせて日付フォーマットをY/m/dに変更
        $response->assertSeeText($this->attendance->date->format('Y/m/d'));
        $response->assertSeeText('承認待ち');
    }
}
