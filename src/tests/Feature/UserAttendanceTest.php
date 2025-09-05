<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserAttendanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ユーザーを作成するヘルパー関数。
     */
    private function createUser(): User
    {
        return User::factory()->create();
    }

    /**
     * 勤怠レコードを作成するヘルパー関数。
     */
    private function createAttendance(User $user, string $date, array $attributes = []): void
    {
        Attendance::factory()->create(array_merge([
            'user_id' => $user->id,
            'date' => $date,
        ], $attributes));
    }

    // --- ID 4: 日時取得機能 ---

    /**
     * 現在の日時情報がUIと同じ形式で出力されていることをテストします。
     * 時刻はテスト実行時によって変わるため、Carbonをモックしてテストを安定させます。
     * @test
     */
    public function test_current_date_and_time_are_displayed_correctly(): void
    {
        // 時刻を固定してテストを安定させる
        $now = Carbon::create(2025, 8, 23, 7, 48, 0);
        Carbon::setTestNow($now);

        $user = $this->createUser();
        $this->actingAs($user);
        $response = $this->get('/attendance');
        $response->assertStatus(200);

        $expectedDateWithDayOfWeek = $now->isoFormat('Y年M月D日(ddd)');
        $expectedTime = $now->format('H:i');

        $response->assertSee($expectedDateWithDayOfWeek);
        $response->assertSee($expectedTime);
    }

    // --- ID 5、6: ステータス確認機能 ---

    /**
     * ステータスが「勤務外」のとき、画面に正しく表示されることをテストします。
     * @test
     */
    public function test_attendance_page_when_status_is_not_working(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $response = $this->get('/attendance');
        
        // 勤怠ステータス
        $response->assertSee('勤務外');

        // ボタンの表示
        $response->assertSee('出勤');
        $response->assertDontSee('退勤');
        $response->assertDontSee('休憩入');
    }

    /**
     * ステータスが「出勤中」のとき、画面に正しく表示されることをテストします。
     * @test
     */
    public function test_attendance_page_when_status_is_working(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $this->createAttendance($user, Carbon::today()->format('Y-m-d'), [
            'check_in_time' => Carbon::now()->subHours(1)->format('Y-m-d H:i:s'),
            'check_out_time' => null,
            'break_time' => 0,
        ]);
        $response = $this->get('/attendance');
        
        // 勤怠ステータス
        $response->assertSee('出勤中');


        // ボタンの表示を、フォームアクションで確認
        $response->assertSee('<form action="' . route('attendance.checkout') . '"', false);
        $response->assertSee('<form action="' . route('attendance.breakin') . '"', false);
        
        // ボタンの非表示を、フォームアクションで確認
        $response->assertDontSee('<form action="' . route('attendance.checkin') . '"', false);
        $response->assertDontSee('<form action="' . route('attendance.breakout') . '"', false);
    }

    /**
     * ステータスが「休憩中」のとき、画面に正しく表示されることをテストします。
     * @test
     */
    public function test_attendance_page_when_status_is_on_break(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'check_in_time' => Carbon::now()->subHours(2)->format('Y-m-d H:i:s'),
        ]);
        
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start_time' => Carbon::now()->subMinutes(15)->format('Y-m-d H:i:s'),
            'break_end_time' => null,
        ]);
        $response = $this->get('/attendance');
        
        $response->assertSee('休憩中');
        $response->assertSee('休憩戻');
        $response->assertDontSee('出勤');
        $response->assertDontSee('退勤');
        $response->assertDontSee('休憩入');
    }

    /**
     * ステータスが「退勤済」のとき、画面に正しく表示されることをテストします。
     * @test
     */
    public function test_attendance_page_when_status_is_finished(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $this->createAttendance($user, Carbon::today()->format('Y-m-d'), [
            'check_in_time' => Carbon::now()->subHours(8)->format('Y-m-d H:i:s'),
            'check_out_time' => Carbon::now()->format('Y-m-d H:i:s'),
            'break_time' => 3600,
        ]);
        $response = $this->get('/attendance');
    
        // 画面に表示される勤怠ステータスと完了メッセージを確認
        $response->assertSee('退勤済');
        $response->assertSee('お疲れさまでした。');
        
        // 勤怠打刻ボタンが表示されていないことを、フォームアクションで確認
        $response->assertDontSee('<form action="' . route('attendance.checkin') . '"', false);
        $response->assertDontSee('<form action="' . route('attendance.checkout') . '"', false);
        $response->assertDontSee('<form action="' . route('attendance.breakin') . '"', false);
        $response->assertDontSee('<form action="' . route('attendance.breakout') . '"', false);
    }

    // --- ID 6: 出勤機能 ---

    /**
     * 出勤ボタンが正しく機能するかテストします。
     * @test
     */
    public function test_start_working_button_works_correctly(): void
    {
        // 時刻を固定
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $user = $this->createUser();
        $this->actingAs($user);
        $response = $this->post(route('attendance.checkin'));
        $response->assertRedirect('/');

        // データベースに勤怠レコードが作成され、出勤時刻が正しく記録されたことを確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => $now->format('Y-m-d'),
            'check_in_time' => $now->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 出勤打刻は1日1回しかできないことをテストします。
     * @test
     */
    public function test_start_working_can_be_done_only_once_a_day(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $this->createAttendance($user, Carbon::today()->format('Y-m-d'), ['check_in_time' => Carbon::now()]);
        $response = $this->post(route('attendance.checkin'));
        $response->assertRedirect('/');
        $response->assertSessionHas('error', 'すでに出勤打刻がされています。');
    }

    // --- ID 7: 休憩機能 ---

    /**
     * ステータスが出勤中のユーザーが「休憩入」ボタンを押下後、画面に「休憩中」と表示されることをテストします。
     * @test
     */
    public function test_break_in_button_shows_on_break_status(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        // ステータスを「出勤中」にする
        $this->createAttendance($user, Carbon::today()->format('Y-m-d'), [
            'check_in_time' => Carbon::now()->subHour(),
        ]);

        // 画面に「休憩入」ボタンが表示されているか確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩入');

        // 休憩開始処理を実行
        $response = $this->post(route('attendance.breakin'));
        $response->assertRedirect('/attendance');

        // 処理後に画面に「休憩中」と表示されることを確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩中');
        $response->assertDontSee('出勤中');
    }

    /**
     * 休憩開始ボタンが正しく機能するかテストします。
     * @test
     */
    public function test_start_break_button_works_correctly(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'check_in_time' => Carbon::now(),
        ]);
        $response = $this->post(route('attendance.breakin'));
        $response->assertRedirect('/');
        
        // break_timesテーブルに休憩開始時刻が記録されたことを確認
        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'break_end_time' => null,
        ]);
    }

    /**
     * 終了ボタンが正しく機能するかテストします。
     * @test
     */
    public function test_end_break_button_works_correctly(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'check_in_time' => Carbon::today()->format('Y-m-d').' 09:00:00',
        ]);
        
        // 休憩中のレコードを作成し、そのIDを保持する
        $break = BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start_time' => Carbon::now()->subMinutes(5),
            'break_end_time' => null,
        ]);

        $response = $this->post(route('attendance.breakout'));
        $response->assertRedirect('/');
        
        // データベースからレコードを再取得して確認
        $updatedBreak = BreakTime::find($break->id);
        $this->assertNotNull($updatedBreak->break_end_time);
    }

    /**
     * 休憩は1日複数回開始できることをテストします。
     * @test
     */
    public function test_break_can_be_started_multiple_times_a_day(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'check_in_time' => Carbon::today()->format('Y-m-d').' 09:00:00',
        ]);
        
        $break1 = BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start_time' => Carbon::today()->format('Y-m-d').' 12:00:00',
            'break_end_time' => Carbon::today()->format('Y-m-d').' 13:00:00',
        ]);

        $response = $this->post(route('attendance.breakin'));
        $response->assertRedirect('/');
        $this->assertCount(2, $attendance->breakTimes()->get());
    }

    /**
     * 休憩は複数回開始できるが、終了は最後に開始したもののみ可能であることをテストします。
     * @test
     */
    public function test_break_can_be_ended_only_the_last_started_one(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'check_in_time' => Carbon::today()->format('Y-m-d').' 09:00:00',
        ]);
        
        $break1 = BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start_time' => Carbon::today()->format('Y-m-d').' 12:00:00',
            'break_end_time' => Carbon::today()->format('Y-m-d').' 13:00:00',
        ]);

        $break2 = BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start_time' => Carbon::today()->format('Y-m-d').' 15:00:00',
            'break_end_time' => null,
        ]);

        // 最後の休憩を終了
        $response1 = $this->post(route('attendance.breakout'));
        $response1->assertRedirect('/');
        $this->assertNotNull(BreakTime::find($break2->id)->break_end_time);
        
        // 休憩終了後の状態なので、再度休憩終了を試みるとエラーセッションが返される
        $response2 = $this->post(route('attendance.breakout'));
        $response2->assertRedirect('/');
        $response2->assertSessionHas('error');
    }

    /**
     * 退勤ボタンが正しく機能するかテストします。
     * @test
     */
    public function test_end_working_button_works_correctly(): void
    {
        // 時刻を固定
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $user = $this->createUser();
        $this->actingAs($user);
        $this->createAttendance($user, $now->format('Y-m-d'), ['check_in_time' => $now]);
        $response = $this->post(route('attendance.checkout'));
        $response->assertRedirect('/');
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => $now->format('Y-m-d'),
            'check_out_time' => $now->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * ユーザーが出勤打刻を行い、勤怠一覧画面でその出勤時刻を確認できることをテストします。
     * @test
     */
    public function test_user_can_checkin_and_view_their_attendance_list(): void
    {
        // 時刻を固定してテストを安定させる
        $now = Carbon::create(2025, 8, 23, 9, 0, 0);
        Carbon::setTestNow($now);
        
        // ログインユーザーを作成
        $user = $this->createUser();
        $this->actingAs($user);

        // 出勤処理を実行
        $this->post(route('attendance.checkin'));
        
        // ユーザー勤怠一覧画面にアクセス
        $response = $this->get('/attendance/list');

        // ステータスコードが200であることを確認
        $response->assertStatus(200);

        // 画面に「退勤」ボタンが表示されていることを確認
        $response->assertSee('退勤');

        // 画面に出勤時刻が表示されているか確認
        // 日付と時刻を、アプリケーションの出力と一致するよう修正
        $response->assertSee($now->isoFormat('MM/DD（ddd）')); 
        $response->assertSee('09:00'); 
    }
}
