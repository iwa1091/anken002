<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Role;
use Carbon\Carbon;

class AdminAttendanceTest extends TestCase
{
    // 各テスト実行後にデータベースをリフレッシュし、クリーンな状態に戻す
    use RefreshDatabase;
    use WithFaker;

    protected User $adminUser;
    protected User $generalUser;

    /**
     * 各テスト実行前に必要なセットアップを行う
     */
    public function setUp(): void
    {
        parent::setUp();
        
        // テスト用の管理者ロールとスタッフロールを作成
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'staff']);

        // 管理者権限を持つユーザーと、スタッフユーザーを作成
        $this->adminUser = User::factory()->create([
            'role_id' => Role::where('name', 'admin')->first()->id,
            'name' => 'テスト管理者'
        ]);
        $this->staffUser = User::factory()->create([
            'role_id' => Role::where('name', 'staff')->first()->id,
            'name' => 'テストスタッフ'
        ]);
        // 一般ユーザーを作成し、プロパティに代入
        $this->generalUser = User::factory()->create();
    }

    /**
     * ID 12: 管理者勤怠一覧画面表示
     * 管理者ユーザーが勤怠一覧画面にアクセスできることをテスト
     */
    public function test_admin_can_access_attendance_list_page(): void
    {
        // 管理者としてadminガードでログイン
        $this->actingAs($this->adminUser, 'admin');
        
        // ルート名を 'admin.attendance.list' に修正し、アクセス
        $response = $this->get(route('admin.attendance.list'));
        
        // 期待通りステータスコード200が返るか検証
        $response->assertStatus(200);
        
        // 正しいビュー 'admin.attendance.list' がレンダリングされているか検証
        $response->assertViewIs('admin.attendance.list');
    }
    
    /**
     * ID 12: 管理者勤怠一覧画面表示
     * 一般ユーザーは勤怠一覧画面にアクセスできないことをテスト
     */
    public function test_general_user_cannot_access_admin_attendance_list_page(): void
    {
        // 一般ユーザーとしてログイン
        $this->actingAs($this->generalUser);
        
        // 管理者勤怠一覧画面にアクセス
        $response = $this->get(route('admin.attendance.list'));
        
        // 権限がないため、ログインページへリダイレクトされることを確認
        $response->assertStatus(302);
        
        // リダイレクト先が '/login' であることを確認
        $response->assertRedirect('/login');
    }

    /**
     * ID 12: 管理者勤怠一覧画面表示
     * 管理者画面に全ユーザーの勤怠データが表示されることをテスト
     */
    public function test_admin_can_see_all_users_attendance_data(): void
    {
        // 管理者としてadminガードでログイン
        $this->actingAs($this->adminUser, 'admin');
        
        // 複数ユーザーの勤怠レコードを作成
        $date = Carbon::parse('2023-11-01');

        Attendance::factory()->create([
            'user_id' => $this->adminUser->id,
            'date' => $date->toDateString(),
            'check_in_time' => Carbon::parse($date->toDateString() . ' 09:00:00'),
            'check_out_time' => Carbon::parse($date->toDateString() . ' 17:00:00'),
        ]);
        
        Attendance::factory()->create([
            'user_id' => $this->generalUser->id,
            'date' => $date->toDateString(),
            'check_in_time' => Carbon::parse($date->toDateString() . ' 09:00:00'),
            'check_out_time' => Carbon::parse($date->toDateString() . ' 17:00:00'),
        ]);

        // 日付をクエリパラメータとして渡してリクエスト
        $response = $this->get(route('admin.attendance.list', ['date' => $date->toDateString()]));
        
        $response->assertStatus(200);
        
        // 管理者自身の名前と一般ユーザーの名前が両方表示されているか確認
        $response->assertSeeText($this->adminUser->name);
        $response->assertSeeText($this->generalUser->name);
    }
    
    /**
     * ID 12: 勤怠一覧表示（日付別）
     * 管理者が日付を指定して勤怠情報を表示できることをテスト
     */
    public function test_admin_can_filter_attendance_by_date(): void
    {
        // 管理者としてadminガードでログイン
        $this->actingAs($this->adminUser, 'admin');
        
        // 異なる日付の勤怠レコードを作成
        $date1 = Carbon::parse('2023-11-01');
        $date2 = Carbon::parse('2023-11-02');

        Attendance::factory()->create([
            'user_id' => $this->generalUser->id,
            'date' => $date1->toDateString(),
            'check_in_time' => Carbon::parse($date1->toDateString() . ' 09:00:00'),
            'check_out_time' => Carbon::parse($date1->toDateString() . ' 17:00:00'),
        ]);

        Attendance::factory()->create([
            'user_id' => $this->generalUser->id,
            'date' => $date2->toDateString(),
            'check_in_time' => Carbon::parse($date2->toDateString() . ' 09:00:00'),
            'check_out_time' => Carbon::parse($date2->toDateString() . ' 17:00:00'),
        ]);

        // 2023-11-01の勤怠データのみをリクエスト
        $response = $this->get(route('admin.attendance.list', ['date' => $date1->toDateString()]));
        $response->assertStatus(200);
        
        // 指定した日付の勤怠データが表示されていることを確認
        // 'YYYY/MM/DD'形式で表示されることを想定
        $response->assertSeeText($date1->format('Y/m/d'));
        
        // 指定していない日付の勤怠データが表示されていないことを確認
        $response->assertDontSeeText($date2->format('Y/m/d'));
    }
    
    /**
     * ID 12: 勤怠一覧表示（日付別）
     * 前の日付へのリンクが正しく機能することをテスト
     */
    public function test_admin_can_go_to_previous_day(): void
    {
        // 管理者としてadminガードでログイン
        $this->actingAs($this->adminUser, 'admin');
        
        // 2023-11-02の勤怠データを表示する
        $response = $this->get(route('admin.attendance.list', ['date' => '2023-11-02']));
        $response->assertStatus(200);

        // 前の日付へのリンク（2023-11-01）が存在することを確認
        $previousDate = Carbon::parse('2023-11-02')->subDay()->toDateString();
        $response->assertSee(route('admin.attendance.list', ['date' => $previousDate]));
    }
    
    /**
     * ID 12: 勤怠一覧表示（日付別）
     * 次の日付へのリンクが正しく機能することをテスト
     */
    public function test_admin_can_go_to_next_day(): void
    {
        // 管理者としてadminガードでログイン
        $this->actingAs($this->adminUser, 'admin');
        
        // 2023-11-02の勤怠データを表示する
        $response = $this->get(route('admin.attendance.list', ['date' => '2023-11-02']));
        $response->assertStatus(200);

        // 次の日付へのリンク（2023-11-03）が存在することを確認
        $nextDate = Carbon::parse('2023-11-02')->addDay()->toDateString();
        $response->assertSee(route('admin.attendance.list', ['date' => $nextDate]));
    }
}
