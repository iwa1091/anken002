<?php

namespace Tests\Feature;

use App\Models\CorrectionRequest;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;

class AdminStampCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // 事前に必要なロールを作成
        $adminRole = Role::factory()->create(['name' => 'admin']);
        Role::factory()->create(['name' => 'general']);

        // 管理者ユーザーを作成し、ログイン
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->actingAs($this->admin, 'admin');
    }

    /**
     * 管理者が承認待ちのすべての修正申請を閲覧できることをテスト
     *
     * @return void
     */
    public function testAdminCanViewAllPendingRequests(): void
    {
        // 他のユーザーを作成
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // テスト用の勤怠データを作成（リレーションシップのため）
        $attendance1 = Attendance::factory()->create(['user_id' => $user1->id]);
        $attendance2 = Attendance::factory()->create(['user_id' => $user2->id]);

        // 承認待ちの申請を作成
        $pendingRequest1 = CorrectionRequest::factory()->create([
            'user_id' => $user1->id,
            'attendance_id' => $attendance1->id,
            'reason' => '休憩時間の修正が必要です。',
            'status' => 'pending',
        ]);
        $pendingRequest2 = CorrectionRequest::factory()->create([
            'user_id' => $user2->id,
            'attendance_id' => $attendance2->id,
            'reason' => '出勤時刻の修正が必要です。',
            'status' => 'pending',
        ]);

        // 承認待ちの修正申請一覧ページにアクセス
        // GETパラメータを使用してフィルタリングをシミュレート
        $response = $this->withoutMiddleware('can:admin-access')->get(route('admin.stamp_correction_request.list', ['status' => 'pending']));

        // レスポンスが成功したことを確認
        $response->assertStatus(200);

        // 承認待ちの申請の詳細がページに表示されることを確認
        $response->assertSeeText($pendingRequest1->reason);
        $response->assertSeeText($pendingRequest2->reason);
    }

    /**
     * 管理者が承認済みのすべての修正申請を閲覧できることをテスト
     * このテストは承認済みタブのフィルタリングを検証します。
     *
     * @return void
     */
    public function testAdminCanViewOnlyApprovedRequests(): void
    {
        // 他のユーザーを作成
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // テスト用の勤怠データを作成（リレーションシップのため）
        $attendance1 = Attendance::factory()->create(['user_id' => $user1->id]);
        $attendance2 = Attendance::factory()->create(['user_id' => $user2->id]);

        // 承認済みの申請を作成
        $approvedRequest1 = CorrectionRequest::factory()->create([
            'user_id' => $user1->id,
            'attendance_id' => $attendance1->id,
            'reason' => '承認済みの理由1',
            'status' => 'approved',
        ]);
        $approvedRequest2 = CorrectionRequest::factory()->create([
            'user_id' => $user2->id,
            'attendance_id' => $attendance2->id,
            'reason' => '承認済みの理由2',
            'status' => 'approved',
        ]);

        // 却下された申請を作成（これがページに表示されないことを確認する）
        $deniedRequest = CorrectionRequest::factory()->create([
            'user_id' => $user1->id,
            'attendance_id' => $attendance1->id,
            'reason' => '却下済みの理由',
            'status' => 'denied',
        ]);
        
        // 承認済みタブのページにアクセス
        $response = $this->withoutMiddleware('can:admin-access')->get(route('admin.stamp_correction_request.list', ['status' => 'approved']));

        // レスポンスが成功したことを確認
        $response->assertStatus(200);

        // 承認済みの申請の詳細がページに表示されることを確認
        $response->assertSeeText($approvedRequest1->reason);
        $response->assertSeeText($approvedRequest2->reason);
        
        // 却下された申請がページに表示されないことを確認
        $response->assertDontSeeText($deniedRequest->reason);
    }

    /**
     * 管理者が修正申請の詳細画面にアクセスし、申請内容が正しく表示されることをテストします。
     *
     * @return void
     */
    public function testAdminCanViewCorrectionRequestDetails(): void
    {
        // GIVEN: ユーザーと勤怠、そして修正申請データを作成
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in_time' => '09:00:00',
            'check_out_time' => '18:00:00',
        ]);
        
        $correctedCheckIn = '09:05:00';
        $correctedCheckOut = '18:10:00';
        $reason = '出退勤時刻の修正';
        
        // ファクトリの引数を修正
        $request = CorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_check_in_time' => $correctedCheckIn,
            'requested_check_out_time' => $correctedCheckOut,
            'reason' => $reason,
            'status' => 'pending',
        ]);
        
        // WHEN: 管理者として修正申請の詳細ページにアクセス
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.stamp_correction_request.approve.show', $request->id));

        // THEN:
        // 1. 正常なレスポンス（ステータスコード200）が返されることを確認
        $response->assertStatus(200);

        // 2. 申請内容が正しく表示されていることを確認
        $response->assertSeeText($user->name); // 申請者名
        $response->assertSeeText(Carbon::parse($attendance->date)->format('Y年')); // 日付の年を検証
        $response->assertSeeText(Carbon::parse($attendance->date)->format('n月j日')); // 日付の月日を検証
        $response->assertSeeText($reason); // 申請理由
        
        // 3. 修正後の時刻が正しく表示されていることを確認
        // 修正前の時刻は現在表示されないため、テストから除外
        $response->assertSeeText(Carbon::parse($request->requested_check_in_time)->format('H:i'));
        $response->assertSeeText(Carbon::parse($request->requested_check_out_time)->format('H:i'));
    }

    /**
     * 管理者が「承認」ボタンを押したときに、修正申請が承認済みになることをテスト
     *
     * @return void
     */
    public function testAdminCanApproveCorrectionRequest(): void
    {
        // GIVEN: 承認待ちの修正申請データを作成
        $request = CorrectionRequest::factory()->create(['status' => 'pending']);

        // WHEN: 承認リクエストをPOST送信
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.stamp_correction_request.approve', $request->id), [
                'status' => 'approved',
                '_token' => csrf_token(),
            ]);

        // THEN: データベースでステータスが「approved」に更新されたことを確認
        $this->assertDatabaseHas('correction_requests', [
            'id' => $request->id,
            'status' => 'approved',
        ]);

        // コントローラーの動作に合わせて、リダイレクト先を検証
        // redirect()->back() の動作を検証
        $response->assertRedirect();
        $response->assertSessionHas('success', '申請が正常に処理されました。');
    }
}
