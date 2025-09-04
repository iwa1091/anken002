<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class UserAttendanceListTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $user;

    public function setUp(): void
    {
        parent::setUp();
        // テスト用のユーザーを作成し、ログイン状態にする
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /**
     * ID 9: 勤怠一覧画面表示
     * 勤怠一覧画面にアクセスできることをテスト
     *
     * @test
     */
    public function it_can_access_the_attendance_list_page(): void
    {
        // 勤怠一覧ページのルートにアクセス
        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);
        $response->assertViewIs('attendance.list');
    }

    /**
     * ID 9: 勤怠一覧画面表示
     * 勤怠一覧画面に自分の勤怠データが表示されることをテスト
     *
     * @test
     */
    public function it_shows_the_users_own_attendance_data(): void
    {
        // テストユーザーの勤怠レコードを現在月に合わせて作成
        $today = Carbon::now();
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => $today->toDateString(),
            'check_in_time' => $today->format('Y-m-d 09:00:00'),
            'check_out_time' => $today->format('Y-m-d 17:00:00'),
            'break_time' => 3600,
            'working_time' => 25200,
        ]);

        // 勤怠一覧ページのルートにアクセス
        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);
        
        // 勤怠モデルのformatted_dateアクセサが返す形式を再現
        $dayOfWeekNames = ['日', '月', '火', '水', '木', '金', '土'];
        $dayOfWeek = $dayOfWeekNames[$today->dayOfWeek];
        $formattedDate = $today->format('m/d') . '（' . $dayOfWeek . '）';

        $response->assertSee($formattedDate);
        $response->assertSee('09:00');
        $response->assertSee('17:00');
    }

    /**
     * ID 9: 勤怠一覧画面表示
     * 他のユーザーの勤怠データは表示されないことをテスト
     *
     * @test
     */
    public function it_does_not_show_other_users_attendance_data(): void
    {
        // 他のユーザーを作成
        $otherUser = User::factory()->create();

        // 他のユーザーの勤怠レコードを現在月に合わせて作成
        Attendance::factory()->create([
            'user_id' => $otherUser->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'check_in_time' => Carbon::now()->format('Y-m-d 09:00:00'),
            'check_out_time' => Carbon::now()->format('Y-m-d 17:00:00'),
        ]);

        // 勤怠一覧ページのルートにアクセス
        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);

        // 他のユーザーのデータが表示されていないことを確認
        $response->assertDontSee($otherUser->name); // 他のユーザーの名前が表示されないことを確認
    }

    /**
     * ID 10: 勤務時間計算
     * 休憩なしの勤務時間が正しく計算されて表示されることをテスト
     *
     * @test
     */
    public function it_calculates_working_hours_correctly_without_breaks(): void
    {
        // 勤怠レコードを直接作成
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'check_in_time' => Carbon::now()->subHours(8),
            'check_out_time' => Carbon::now(),
        ]);
    
        // 勤怠一覧ページのルートにアクセス
        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);

        // 勤務時間が8:00と表示されていることを確認
        $response->assertSee('8:00');
    }

    /**
     * ID 10: 勤務時間計算
     * 休憩ありの勤務時間が正しく計算されて表示されることをテスト
     *
     * @test
     */
    public function it_calculates_working_hours_correctly_with_breaks(): void
    {
        // 勤怠レコードと休憩時間を直接作成
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'check_in_time' => Carbon::now()->subHours(9),
            'check_out_time' => Carbon::now(),
        ]);
        
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start_time' => Carbon::now()->subHours(6),
            'break_end_time' => Carbon::now()->subHours(5),
        ]);

        // 勤怠一覧ページのルートにアクセス
        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);

        // 勤務時間が8:00と表示されていることを確認 (9時間拘束 - 1時間休憩 = 8時間勤務)
        $response->assertSee('8:00');
    }

    /**
     * ID 10: 勤務時間計算
     * 複数回休憩した場合の勤務時間が正しく計算されて表示されることをテスト
     *
     * @test
     */
    public function it_calculates_working_hours_correctly_with_multiple_breaks(): void
    {
        // 勤怠レコードと複数回の休憩時間を直接作成
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'check_in_time' => Carbon::now()->subHours(9),
            'check_out_time' => Carbon::now(),
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start_time' => Carbon::now()->subHours(6),
            'break_end_time' => Carbon::now()->subMinutes(330),
        ]);
        
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start_time' => Carbon::now()->subMinutes(150),
            'break_end_time' => Carbon::now()->subMinutes(120),
        ]);

        // 勤怠一覧ページのルートにアクセス
        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);
        
        // 勤務時間が8:00と表示されていることを確認 (9時間拘束 - 1時間休憩 = 8時間勤務)
        $response->assertSee('8:00');
    }

    /**
     * ID 11: ページネーション機能
     * 勤怠データが一定数以上ある場合に、ページネーションが正しく機能することをテスト
     *
     * @test
     */
    public function it_paginates_attendance_data_correctly(): void
    {
        // 35日分の勤怠データをテストユーザーに紐付けて作成
        for ($i = 0; $i < 35; $i++) {
            Attendance::factory()->create([
                'user_id' => $this->user->id,
                'date' => Carbon::now()->subDays($i)->format('Y-m-d'),
                'check_in_time' => Carbon::now()->subDays($i)->format('Y-m-d 09:00:00'),
                'check_out_time' => Carbon::now()->subDays($i)->format('Y-m-d 17:00:00'),
            ]);
        }

        // 1ページ目にアクセス
        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);
        $response->assertSee('翌月 →'); // 次のページへのリンクが存在することを確認

        // 2ページ目にアクセス
        $response = $this->get(route('attendance.list', ['page' => 2]));
        $response->assertStatus(200);
        $response->assertSee('← 前月'); // 前のページへのリンクが存在することを確認
    }

    /**
     * ID 11: ページネーション機能
     * ページネーションが日付順に表示されることをテスト
     *
     * @test
     */
    public function it_orders_pagination_by_date_descending(): void
    {
        // 日付が古い順にデータを複数作成
        $today = Carbon::now();
        Attendance::factory()->create(['user_id' => $this->user->id, 'date' => $today->copy()->subDays(2), 'check_in_time' => $today->copy()->subDays(2)->format('Y-m-d 09:00:00'), 'check_out_time' => $today->copy()->subDays(2)->format('Y-m-d 17:00:00')]);
        Attendance::factory()->create(['user_id' => $this->user->id, 'date' => $today->copy()->format('Y-m-d'), 'check_in_time' => $today->copy()->format('Y-m-d 09:00:00'), 'check_out_time' => $today->copy()->format('Y-m-d 17:00:00')]);
        Attendance::factory()->create(['user_id' => $this->user->id, 'date' => $today->copy()->subDays(1), 'check_in_time' => $today->copy()->subDays(1)->format('Y-m-d 09:00:00'), 'check_out_time' => $today->copy()->subDays(1)->format('Y-m-d 17:00:00')]);

        // 勤怠一覧ページのルートにアクセス
        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);

        // 最新の日付が最初に表示されていることを確認
        $dayOfWeekNames = ['日', '月', '火', '水', '木', '金', '土'];
        $response->assertSeeInOrder([
            $today->format('m/d') . '（' . $dayOfWeekNames[$today->dayOfWeek] . '）',
            $today->copy()->subDays(1)->format('m/d') . '（' . $dayOfWeekNames[$today->copy()->subDays(1)->dayOfWeek] . '）',
            $today->copy()->subDays(2)->format('m/d') . '（' . $dayOfWeekNames[$today->copy()->subDays(2)->dayOfWeek] . '）',
        ]);
    }
    
    /**
     * 勤怠一覧画面にアクセスした際に現在の月が表示されることをテスト
     *
     * @test
     */
    public function it_displays_the_current_month_on_the_list_page(): void
    {
        $response = $this->get(route('attendance.list'));

        $response->assertStatus(200);
        $response->assertSee(Carbon::now()->format('Y/m'));
    }

    /**
     * 「前月」ボタンを押下した時に表示月の前月の情報が表示されることをテスト
     *
     * @test
     */
    public function it_shows_the_previous_month_when_navigating(): void
    {
        // 前月の勤怠データを作成
        $previousMonth = Carbon::now()->subMonth();
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => $previousMonth->format('Y-m-d'),
            'check_in_time' => $previousMonth->format('Y-m-d 09:00:00'),
            'check_out_time' => $previousMonth->format('Y-m-d 17:00:00'),
        ]);

        $response = $this->get(route('attendance.list', ['month' => $previousMonth->format('Y-m')]));

        $response->assertStatus(200);
        $response->assertSee($previousMonth->format('Y/m'));
        
        // 勤怠モデルのformatted_dateアクセサが返す形式を再現
        $dayOfWeekNames = ['日', '月', '火', '水', '木', '金', '土'];
        $dayOfWeek = $dayOfWeekNames[$previousMonth->dayOfWeek];
        $formattedDate = $previousMonth->format('m/d') . '（' . $dayOfWeek . '）';
        $response->assertSee($formattedDate);
    }

    /**
     * 「翌月」ボタンを押下した時に表示月の翌月の情報が表示されることをテスト
     *
     * @test
     */
    public function it_shows_the_next_month_when_navigating(): void
    {
        // 翌月の勤怠データを作成
        $nextMonth = Carbon::now()->addMonth();
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => $nextMonth->format('Y-m-d'),
            'check_in_time' => $nextMonth->format('Y-m-d 09:00:00'),
            'check_out_time' => $nextMonth->format('Y-m-d 17:00:00'),
        ]);

        $response = $this->get(route('attendance.list', ['month' => $nextMonth->format('Y-m')]));

        $response->assertStatus(200);
        $response->assertSee($nextMonth->format('Y/m'));

        // 勤怠モデルのformatted_dateアクセサが返す形式を再現
        $dayOfWeekNames = ['日', '月', '火', '水', '木', '金', '土'];
        $dayOfWeek = $dayOfWeekNames[$nextMonth->dayOfWeek];
        $formattedDate = $nextMonth->format('m/d') . '（' . $dayOfWeek . '）';
        $response->assertSee($formattedDate);
    }

    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移することをテスト
     *
     * @test
     */
    public function it_redirects_to_the_attendance_detail_page(): void
    {
        // ユーザーに紐づいた勤怠レコードを作成
        $attendance = Attendance::factory()->for($this->user)->create();

        // ルート名を 'attendance.show' に修正
        $response = $this->get(route('attendance.show', ['id' => $attendance->id]));

        $response->assertStatus(200);
        $response->assertViewIs('attendance.detail');
        // ビューにユーザー名と日付が表示されているかを確認
        $response->assertSee($this->user->name);
        
        // 勤怠詳細ページに表示されている日付の形式に合わせてアサーションを修正
        $formattedYear = Carbon::parse($attendance->date)->format('Y年');
        $formattedMonthDay = Carbon::parse($attendance->date)->isoFormat('M月D日');
        
        $response->assertSee($formattedYear);
        $response->assertSee($formattedMonthDay);
    }

    /**
     * ID 12: 勤怠詳細情報取得機能
     * 勤怠詳細画面の表示内容が正しく表示されることをテスト
     *
     * @test
     */
    public function it_shows_attendance_detail_information_correctly(): void
    {
        // 勤怠詳細テスト用の勤怠レコードと休憩レコードを作成
        $checkIn = Carbon::now()->subHours(8);
        $checkOut = Carbon::now();
        $breakStart = Carbon::now()->subHours(4);
        $breakEnd = Carbon::now()->subHours(3);

        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::now()->toDateString(),
            'check_in_time' => $checkIn,
            'check_out_time' => $checkOut,
        ]);
        
        $breakTime = BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start_time' => $breakStart,
            'break_end_time' => $breakEnd,
        ]);

        // 勤怠詳細ページにアクセス
        $response = $this->get(route('attendance.show', ['id' => $attendance->id]));
        $response->assertStatus(200);
        $response->assertViewIs('attendance.detail');

        // 1. 名前がログインユーザーの名前になっていることを確認
        $response->assertSee($this->user->name);

        // 2. 日付が選択した日付になっていることを確認
        // HTMLの形式に合わせて年と月日を個別にアサート
        $response->assertSee(Carbon::parse($attendance->date)->format('Y年'));
        $response->assertSee(Carbon::parse($attendance->date)->format('n月j日'));

        // 3. 出勤・退勤時間が一致していることを確認
        $response->assertSee($checkIn->format('H:i'));
        $response->assertSee($checkOut->format('H:i'));

        // 4. 休憩時間が一致していることを確認
        $response->assertSee($breakTime->break_start_time->format('H:i'));
        $response->assertSee($breakTime->break_end_time->format('H:i'));
    }

    /**
     * @test
     * 希望出勤時間が希望退勤時間よりも後である場合にバリデーションエラーが発生することをテストします。
     * @see \App\Http\Requests\CorrectionRequestStoreRequest
     */
    public function it_shows_validation_error_when_requested_check_in_time_is_after_requested_check_out_time(): void
    {
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'check_in_time' => now()->subHours(8),
            'check_out_time' => now()->subHours(1),
        ]);

        $invalidData = [
            'date' => now()->format('Y-m-d'),
            'requested_check_in_time' => '17:00',
            'requested_check_out_time' => '09:00',
            'reason' => 'テスト目的の修正依頼',
        ];

        // JSONリクエストとして送信し、バリデーションエラーが422で返されることを期待する
        $response = $this->json('POST', route('application.storeCorrectionRequest', ['attendance_id' => $attendance->id]), $invalidData);

        // バリデーションエラーを示す422ステータスであることを確認
        $response->assertStatus(422);

        // JSONレスポンスに指定されたフィールドのエラーが含まれていることを確認
        $response->assertJsonValidationErrors(['requested_check_out_time']);

        // 新しいエラーメッセージがJSONレスポンスに含まれていることを確認
        $response->assertJsonFragment([
            '退勤時間は出勤時間以降の時刻を入力してください。'
        ]);
    }
    /**
     * @test
     * 休憩開始時間が退勤時間よりも後である場合にバリデーションエラーが発生することをテストします。
     * PHPUnit実行時に「Expected response status code [422] but received 302」となる問題を解消するテストです。
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
     * @test
     * 備考欄が未入力の場合にバリデーションエラーが表示されることをテストします。
     *
     * @see \App\Http\Requests\CorrectionRequestStoreRequest
     */
    public function it_shows_validation_error_when_reason_is_empty(): void
    {
        // 勤怠レコードを準備
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'check_in_time' => now()->subHours(8),
            'check_out_time' => now()->subHours(1),
        ]);

        // 備考欄が空の無効なデータを作成
        $invalidData = [
            'date' => now()->format('Y-m-d'),
            'requested_check_in_time' => '09:00',
            'requested_check_out_time' => '18:00',
            'reason' => '', // 備考欄を未入力にする
        ];

        // JSONリクエストとして送信し、バリデーションエラーが422で返されることを期待する
        $response = $this->postJson(route('application.storeCorrectionRequest', ['attendance_id' => $attendance->id]), $invalidData);

        // バリデーションエラーを示す422ステータスであることを確認
        $response->assertStatus(422);

        // JSONレスポンスに指定されたフィールドのエラーが含まれていることを確認
        $response->assertJsonValidationErrors(['reason']);

        // 新しいエラーメッセージがJSONレスポンスに含まれていることを確認
        $response->assertJsonFragment([
            '備考を記入してください'
        ]);
    }
}
