<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User; // Userモデルをインポート
use Laravel\Fortify\Fortify; // Fortifyファサードをインポート
use Illuminate\Support\Facades\Route; // Routeファサードをインポート (Fortify::routes()で使用)
// Fortifyのカスタムアクションは、config/fortify.php で指定されているため、
// ここで個別にuseする必要はありません。

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any authentication / authorization services.
     */
    public function boot(): void
    {
        // 管理者アクセス用のゲートを定義
        Gate::define('admin-access', function (User $user) {
            return $user->isAdmin();
        });

        // ★★★ ここが重要：Fortifyのルートを登録します ★★★
        // Fortifyの様々な認証ルート (ログイン、登録、パスワードリセット、メール認証など) を登録します。
        // これがないと、メール認証などのFortifyの機能に関連するルートが動作しません。
        // Fortifyのカスタムアクション (CreateNewUserなど) の設定は、
        // Laravel\Fortify\FortifyServiceProvider の中で config/fortify.php を読み込んで行われます。
        // そのため、AuthServiceProviderで重複して設定する必要はありません。
        //Fortify::routes(); 
        // ログイン試行のレートリミッターは通常、FortifyServiceProvider自身が設定するか、
        // アプリケーション固有のRateLimiterサービスプロバイダで定義します。
        // こちらのAuthServiceProviderでの設定は、通常不要です。
        // FortifyServiceProvider.phpに移動するか、デフォルトのままにします。
        /*
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());
            return Limit::perMinute(5)->by($throttleKey);
        });
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
        */
    }
}
