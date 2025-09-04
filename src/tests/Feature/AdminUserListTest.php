<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminUserListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // テストに必要なロールを事前に作成
        Role::factory()->create(['name' => 'admin']);
        Role::factory()->create(['name' => 'staff']);
    }

    /**
     * 管理者ユーザーがスタッフ一覧ページにアクセスし、
     * 全ての一般ユーザーの氏名とメールアドレスを確認できるかをテストします。
     *
     * @return void
     */
    public function testAdminCanViewAllStaffUsersDetails()
    {
        // GIVEN: 'admin' ロールを持つ管理者ユーザーを1名作成
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        // GIVEN: 'staff' ロールを持つ一般ユーザーを複数名作成
        $staffRole = Role::where('name', 'staff')->first();
        $staffs = User::factory()->count(5)->create(['role_id' => $staffRole->id]);

        // WHEN: 管理者としてログインし、スタッフ一覧ページにアクセスする
        $response = $this->actingAs($admin, 'admin')->get(route('admin.staff.list'));

        // THEN:
        // 1. 正常なレスポンス（ステータスコード200）が返されることを確認
        $response->assertStatus(200);

        // 2. スタッフ一覧ページに、作成した全ての一般ユーザーの名前とメールアドレスが表示されていることを確認
        foreach ($staffs as $staff) {
            $response->assertSeeText($staff->name);
            $response->assertSeeText($staff->email);
        }
    }

    /**
     * 一般ユーザーとしてログインした場合、スタッフ一覧ページに
     * アクセスできないことをテストします。
     *
     * @return void
     */
    public function testGeneralUserCannotViewStaffList()
    {
        // GIVEN: 'staff' ロールを持つ一般ユーザーを1名作成
        $staffRole = Role::where('name', 'staff')->first();
        $staffUser = User::factory()->create(['role_id' => $staffRole->id]);

        // WHEN: 一般ユーザーとしてログインし、スタッフ一覧ページにアクセスする
        // actingAs()はデフォルトで'web'ガードを使用する。adminルートは'admin'ガードで保護されているため、
        // 認証に失敗し、ログインページにリダイレクトされる。
        $response = $this->actingAs($staffUser)->get(route('admin.staff.list'));

        // THEN: 管理者ログインページにリダイレクトされることを確認
        $response->assertRedirect(route('login'));
    }

    /**
     * 管理者が特定のユーザーの勤怠詳細ページにアクセスし、
     * 勤怠情報が正しく表示されることをテストします。
     *
     * @return void
     */
    public function testAdminCanViewStaffAttendanceDetails()
    {
        // GIVEN: 'admin' ロールを持つ管理者ユーザーを作成
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        // GIVEN: 'staff' ロールを持つ一般ユーザーを作成
        $staffRole = Role::where('name', 'staff')->first();
        $staffUser = User::factory()->create(['role_id' => $staffRole->id]);

        // GIVEN: 一般ユーザーの勤怠データを作成
        $date = Carbon::parse('2025-08-31');
        $attendance = Attendance::factory()->create([
            'user_id' => $staffUser->id,
            'date' => $date->toDateString(),
            'check_in_time' => $date->toDateString() . ' 09:00:00',
            'check_out_time' => $date->toDateString() . ' 18:00:00',
            'break_time' => 3600, // 60分
            'working_time' => 28800, // 8時間
        ]);

        // WHEN: 管理者としてログインし、スタッフの勤怠詳細ページにアクセスする
        // ルート名をadmin.staff.attendanceに修正
        $response = $this->actingAs($admin, 'admin')->get(route('admin.staff.attendance', [
            'id' => $staffUser->id,
            'month' => $date->format('Y-m'),
        ]));

        // THEN:
        // 1. 正常なレスポンス（ステータスコード200）が返されることを確認
        $response->assertStatus(200);

        // 2. ページに勤怠情報が正しく表示されていることを確認
        $response->assertSeeText($staffUser->name); // ユーザー名
        // Bladeテンプレートの表示内容に合わせるため、日付の形式を修正
        $response->assertSeeText('08/31（日）');
        $response->assertDontSeeText('07/31（日）');

        // WHEN: 「詳細」ボタンを押した結果をシミュレートする（詳細ページにアクセス）
        $detailUrl = route('admin.attendance.show', ['id' => $attendance->id]);
        $response = $this->actingAs($admin, 'admin')->get($detailUrl);

        // THEN: 詳細ページに正常に遷移し、勤怠情報が表示されていることを確認
        $response->assertStatus(200);
        $response->assertSeeText($staffUser->name); // ユーザー名
        $response->assertSeeText($date->format('Y年'));
        $response->assertSeeText($date->format('n月j日'));
        // 出勤時間と退勤時間の検証を柔軟にするため、正規表現を使用
        // HTMLのフォーマット（改行や空白）を考慮
        $this->assertMatchesRegularExpression(
            '/<input\s+type="time"[^>]*id="check_in_time"[^>]*value="09:00"[^>]*>/s',
            $response->getContent()
        );
        $this->assertMatchesRegularExpression(
            '/<input\s+type="time"[^>]*id="check_out_time"[^>]*value="18:00"[^>]*>/s',
            $response->getContent()
        );
    }

    /**
     * 「前月」ボタンを押した時に、表示月の前月の情報が表示されることをテストします。
     *
     * @return void
     */
    public function testAdminCanNavigateToPreviousMonth()
    {
        // GIVEN: 'admin' ロールを持つ管理者ユーザーを作成
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        // GIVEN: 'staff' ロールを持つ一般ユーザーを作成
        $staffRole = Role::where('name', 'staff')->first();
        $staffUser = User::factory()->create(['role_id' => $staffRole->id]);

        // GIVEN: 8月と7月の勤怠データを作成
        $augustDate = Carbon::parse('2025-08-15');
        $julyDate = Carbon::parse('2025-07-15');

        Attendance::factory()->create([
            'user_id' => $staffUser->id,
            'date' => $augustDate->toDateString(),
            'check_in_time' => $augustDate->toDateString() . ' 09:00:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $staffUser->id,
            'date' => $julyDate->toDateString(),
            'check_in_time' => $julyDate->toDateString() . ' 09:00:00',
        ]);

        // WHEN: 管理者としてログインし、8月の勤怠詳細ページにアクセス
        $response = $this->actingAs($admin, 'admin')->get(route('admin.staff.attendance', [
            'id' => $staffUser->id,
            'month' => $augustDate->format('Y-m'),
        ]));

        // THEN: ページに8月の情報が表示されていることを確認
        $response->assertStatus(200);
        // Bladeテンプレートの表示内容に合わせるため、日付の形式を修正
        $response->assertSeeText('08/15（金）');
        $response->assertDontSeeText('07/15（火）');

        // AND: 前月へのリンクが正しいURLで存在することを確認
        $previousMonthUrl = route('admin.staff.attendance', [
            'id' => $staffUser->id,
            'month' => $julyDate->format('Y-m'),
        ]);

        // assertSeeでHTMLの`href`属性を直接検証
        $response->assertSee('<a href="' . htmlspecialchars($previousMonthUrl) . '" class="month-nav-link">&lt;－前月</a>', false);

        // WHEN: 前月のページにアクセスする
        $response = $this->actingAs($admin, 'admin')->get($previousMonthUrl);

        // THEN: ページに7月の情報が表示されていることを確認
        $response->assertStatus(200);
        // Bladeテンプレートの表示内容に合わせるため、日付の形式を修正
        $response->assertSeeText('07/15（火）');
        $response->assertDontSeeText('08/15（金）');
    }
    /**
     * 「翌月」ボタンを押した時に、表示月の翌月の情報が表示されることをテストします。
     *
     * @return void
     */
    public function testAdminCanNavigateToNextMonth()
    {
        // GIVEN: 'admin' ロールを持つ管理者ユーザーを作成
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        // GIVEN: 'staff' ロールを持つ一般ユーザーを作成
        $staffRole = Role::where('name', 'staff')->first();
        $staffUser = User::factory()->create(['role_id' => $staffRole->id]);

        // GIVEN: 8月と9月の勤怠データを作成
        $augustDate = Carbon::parse('2025-08-15');
        $septemberDate = Carbon::parse('2025-09-15');

        Attendance::factory()->create([
            'user_id' => $staffUser->id,
            'date' => $augustDate->toDateString(),
            'check_in_time' => $augustDate->toDateString() . ' 09:00:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $staffUser->id,
            'date' => $septemberDate->toDateString(),
            'check_in_time' => $septemberDate->toDateString() . ' 09:00:00',
        ]);

        // WHEN: 管理者としてログインし、8月の勤怠詳細ページにアクセス
        $response = $this->actingAs($admin, 'admin')->get(route('admin.staff.attendance', [
            'id' => $staffUser->id,
            'month' => $augustDate->format('Y-m'),
        ]));

        // THEN: ページに8月の情報が表示されていることを確認
        $response->assertStatus(200);
        // Bladeテンプレートの表示内容に合わせるため、日付の形式を修正
        $response->assertSeeText('08/15（金）');
        $response->assertDontSeeText('09/15（月）');

        // AND: 翌月へのリンクが正しいURLで存在することを確認
        $nextMonthUrl = route('admin.staff.attendance', [
            'id' => $staffUser->id,
            'month' => $septemberDate->format('Y-m'),
        ]);

        $response->assertSee('<a href="' . htmlspecialchars($nextMonthUrl) . '" class="month-nav-link">翌月－&gt;</a>', false);

        // WHEN: 翌月のページにアクセスする
        $response = $this->actingAs($admin, 'admin')->get($nextMonthUrl);

        // THEN: ページに9月の情報が表示されていることを確認
        $response->assertStatus(200);
        // Bladeテンプレートの表示内容に合わせるため、日付の形式を修正
        $response->assertSeeText('09/15（月）');
        $response->assertDontSeeText('08/15（金）');
    }
    /**
     * 管理者が「詳細」ボタンを押した時に、その日の勤怠詳細画面に遷移することをテストします。
     *
     * @return void
     */
    public function testAdminCanViewDailyAttendanceDetails()
    {
        // GIVEN: 'admin' ロールを持つ管理者ユーザーを作成
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        // GIVEN: 'staff' ロールを持つ一般ユーザーを作成
        $staffRole = Role::where('name', 'staff')->first();
        $staffUser = User::factory()->create(['role_id' => $staffRole->id]);

        // GIVEN: 特定の日の勤怠データを作成
        $date = Carbon::parse('2025-08-15');
        $attendance = Attendance::factory()->create([
            'user_id' => $staffUser->id,
            'date' => $date->toDateString(),
            'check_in_time' => $date->toDateString() . ' 09:00:00',
            'check_out_time' => $date->toDateString() . ' 18:00:00',
        ]);

        // WHEN: 管理者としてログインし、スタッフの勤怠一覧ページにアクセスする
        $response = $this->actingAs($admin, 'admin')->get(route('admin.staff.attendance', [
            'id' => $staffUser->id,
            'month' => $date->format('Y-m'),
        ]));

        // THEN: ページに「詳細」ボタン（リンク）が存在し、正しいURLを指していることを確認
        $response->assertStatus(200);
        $detailUrl = route('admin.attendance.show', ['id' => $attendance->id]);
        $response->assertSee('<a href="' . htmlspecialchars($detailUrl) . '" class="action-button detail-button">詳細</a>', false);

        // WHEN: 「詳細」ボタンを押した結果をシミュレートする（詳細ページにアクセス）
        $response = $this->actingAs($admin, 'admin')->get($detailUrl);

        // THEN: 詳細ページに正常に遷移し、勤怠情報が表示されていることを確認
        $response->assertStatus(200);
        $response->assertSeeText($staffUser->name); // ユーザー名
        $response->assertSeeText($date->format('Y年'));
        $response->assertSeeText($date->format('n月j日'));

        // PHPUnitのネイティブな正規表現アサーションを使用
        $this->assertMatchesRegularExpression(
            '/<input\s+type="time"[^>]*id="check_in_time"[^>]*value="09:00"[^>]*>/s',
            $response->getContent()
        );
        $this->assertMatchesRegularExpression(
            '/<input\s+type="time"[^>]*id="check_out_time"[^>]*value="18:00"[^>]*>/s',
            $response->getContent()
        );
    }
}
