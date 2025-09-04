<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use Carbon\Carbon;
use Tests\TestCase;

class UserAttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $user;
    protected Attendance $attendance;
    protected Attendance $secondAttendance;

    public function setUp(): void
    {
        parent::setUp();

        // ユーザーを作成し、認証済み状態にする
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // 修正対象の勤怠レコードを作成
        $this->attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->subDays(1), // 1日前の勤怠レコード
            'check_in_time' => '09:00:00',
            'check_out_time' => '17:00:00',
        ]);

        // もう1つの修正対象の勤怠レコードを作成
        $this->secondAttendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today()->subDays(2), // 2日前の勤怠レコード
            'check_in_time' => '09:00:00',
            'check_out_time' => '17:00:00',
        ]);
    }

    /**
     * ID 11: 勤怠修正機能
     * 修正申請画面にアクセスできることをテスト
     * @test
     */
    public function test_user_can_access_correction_form(): void
    {
        $response = $this->get(route('attendance.show', ['id' => $this->attendance->id]));

        $response->assertStatus(200);
        $response->assertViewIs('attendance.detail');
    }

    /**
     * ID 11: 勤怠修正機能
     * 修正申請が成功し、データベースにデータが保存されることをテスト
     * @test
     */
    public function test_user_can_submit_correction_request(): void
    {
        // 修正データ
        // バリデーションルールに合わせ、HH:MM形式に修正
        $requestedCheckInTime = '08:30';
        $requestedCheckOutTime = '17:30';
        $reason = 'テスト';

        $initialCount = CorrectionRequest::count();

        $response = $this->post(route('application.storeCorrectionRequest', ['attendance_id' => $this->attendance->id]), [
            'requested_check_in_time' => $requestedCheckInTime,
            'requested_check_out_time' => $requestedCheckOutTime,
            'reason' => $reason,
        ]);

        // コントローラーの実際の動作に合わせてリダイレクト先を修正
        $response->assertRedirect(route('stamp_correction_request.list'));
        $response->assertSessionDoesntHaveErrors();

        $this->assertEquals($initialCount + 1, CorrectionRequest::count());

        $latestRequest = CorrectionRequest::latest()->first();

        $this->assertEquals($this->user->id, $latestRequest->user_id);
        $this->assertEquals($this->attendance->id, $latestRequest->attendance_id);
        // 時刻データはデータベースでどのように保存されるかに依存するため、厳密な比較を避ける
        $this->assertEquals(Carbon::parse($requestedCheckInTime)->format('H:i'), $latestRequest->requested_check_in_time->format('H:i'));
        $this->assertEquals(Carbon::parse($requestedCheckOutTime)->format('H:i'), $latestRequest->requested_check_out_time->format('H:i'));
        $this->assertEquals($reason, $latestRequest->reason);
        $this->assertEquals('pending', $latestRequest->status);
    }
    
    /**
     * @test
     * 休憩開始時間が退勤時間よりも後である場合にバリデーションエラーが発生することをテストします。
     */
    public function it_shows_validation_error_when_break_start_time_is_after_check_out_time(): void
    {
        // 勤怠レコードを準備
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'check_in_time' => '09:00',
            'check_out_time' => '18:00',
        ]);

        // バリデーションに失敗する無効なデータを作成
        // 休憩開始時間 '19:00' を退勤時間 '18:00' より後に設定
        $invalidData = [
            'date' => $attendance->date,
            'requested_check_in_time' => '09:00',
            'requested_check_out_time' => '18:00',
            'reason' => '休憩開始時間が退勤時間より後',
            'requested_breaks' => [
                [
                    'start' => '19:00',
                    'end' => '20:00'
                ]
            ]
        ];

        // postJson() を使用して、Ajaxリクエストとして送信
        $response = $this->postJson(route('application.storeCorrectionRequest', ['attendance_id' => $attendance->id]), $invalidData);

        // レスポンスがバリデーションエラーを示す 422 であることを確認
        $response->assertStatus(422);

        // JSONレスポンスに、休憩開始時間のエラーが含まれていることを確認
        // 'requested_breaks.0.start' のように配列のキーを指定
        $response->assertJsonValidationErrors(['requested_breaks.0.start']);

        // JSONレスポンスに特定のエラーメッセージが含まれていることを確認
        $response->assertJsonFragment([
            '出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }

    /**
     * @test
     * 休憩終了時間が退勤時間よりも後である場合にバリデーションエラーが発生することをテストします。
     */
    public function it_shows_validation_error_when_break_end_time_is_after_check_out_time(): void
    {
        // 勤怠レコードを準備
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'check_in_time' => '09:00',
            'check_out_time' => '18:00',
        ]);

        // バリデーションに失敗する無効なデータを作成
        // 休憩終了時間 '19:00' を退勤時間 '18:00' より後に設定
        $invalidData = [
            'date' => $attendance->date,
            'requested_check_in_time' => '09:00',
            'requested_check_out_time' => '18:00',
            'reason' => '休憩終了時間が退勤時間より後',
            'requested_breaks' => [
                [
                    'start' => '17:00',
                    'end' => '19:00'
                ]
            ]
        ];

        // postJson() を使用して、Ajaxリクエストとして送信
        $response = $this->postJson(route('application.storeCorrectionRequest', ['attendance_id' => $attendance->id]), $invalidData);

        // レスポンスがバリデーションエラーを示す 422 であることを確認
        $response->assertStatus(422);

        // JSONレスポンスに、休憩終了時間のエラーが含まれていることを確認
        // 'requested_breaks.0.end' のように配列のキーを指定
        $response->assertJsonValidationErrors(['requested_breaks.0.end']);

        // JSONレスポンスに特定のエラーメッセージが含まれていることを確認
        $response->assertJsonFragment([
            '出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }
    
    /**
     * ID 11: 勤怠修正機能
     * ログインユーザーが行った勤怠修正申請が申請一覧にすべて表示されることをテスト
     * @test
     */
    public function test_pending_applications_are_displayed_for_logged_in_user(): void
    {
        // ログインユーザーの勤怠修正申請を2つ作成
        CorrectionRequest::factory()->create([
            'user_id' => $this->user->id,
            'attendance_id' => $this->attendance->id,
            'requested_check_in_time' => '08:00:00',
            'requested_check_out_time' => '18:00:00',
            'status' => 'pending',
        ]);

        CorrectionRequest::factory()->create([
            'user_id' => $this->user->id,
            'attendance_id' => $this->secondAttendance->id,
            'requested_check_in_time' => '09:30:00',
            'requested_check_out_time' => '17:30:00',
            'status' => 'pending',
        ]);

        // 申請一覧画面にアクセスする
        $response = $this->get(route('stamp_correction_request.list'));

        // ステータスコードが200であることを確認
        $response->assertStatus(200);

        // ビューが正しいことを確認
        $response->assertViewIs('application.list');

        // 修正申請データがビューに表示されていることを確認
        // ビューには時刻が表示されていないため、日付のみを検証する
        $response->assertSeeInOrder([
            Carbon::today()->subDays(1)->format('Y/m/d'),
            Carbon::today()->subDays(2)->format('Y/m/d'),
        ]);
    }

    /**
     * @test
     * 管理者が承認した修正申請が、申請一覧の「承認済み」にすべて表示されることをテストします。
     */
    public function test_user_can_view_all_their_approved_requests_on_the_list_page(): void
    {
        // ログインユーザーの承認済み勤怠修正申請を2つ作成
        CorrectionRequest::factory()->create([
            'user_id' => $this->user->id,
            'attendance_id' => $this->attendance->id,
            'status' => 'approved',
        ]);

        CorrectionRequest::factory()->create([
            'user_id' => $this->user->id,
            'attendance_id' => $this->secondAttendance->id,
            'status' => 'approved',
        ]);

        // 他のユーザーの承認済み申請を作成（表示されないことを確認するため）
        $otherUser = User::factory()->create();
        CorrectionRequest::factory()->create([
            'user_id' => $otherUser->id,
            'attendance_id' => Attendance::factory()->create(['user_id' => $otherUser->id])->id,
            'status' => 'approved',
        ]);

        // 申請一覧画面にアクセス
        $response = $this->get(route('stamp_correction_request.list'));

        $response->assertStatus(200);
        $response->assertViewIs('application.list');
        
        // ログインユーザーの申請が表示されていることを確認
        $response->assertSeeText($this->attendance->date->format('Y/m/d'));
        $response->assertSeeText($this->secondAttendance->date->format('Y/m/d'));

        // 他のユーザーの申請は表示されないことを確認
        $response->assertDontSeeText($otherUser->name);
    }
    
    /**
     * @test
     * 各申請の「詳細」ボタンを押すと勤怠詳細画面に遷移することをテストします。
     */
    public function test_clicking_detail_button_redirects_to_attendance_detail_page(): void
    {
        // ログインユーザーの勤怠修正申請を作成
        $correctionRequest = CorrectionRequest::factory()->create([
            'user_id' => $this->user->id,
            'attendance_id' => $this->attendance->id,
        ]);

        // 詳細画面へのアクセスをシミュレート
        $response = $this->get(route('attendance.show', ['id' => $correctionRequest->attendance_id]));

        // ステータスコードが200であることを確認
        $response->assertStatus(200);
        // ビューが正しいことを確認
        $response->assertViewIs('attendance.detail');
    }
}
