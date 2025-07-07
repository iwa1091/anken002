<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Contracts\VerifyEmailViewResponse; // ★★★ 追加: VerifyEmailViewResponse インターフェースをインポート
use Laravel\Fortify\Http\Responses\ViewVerifyEmailResponse; // ★★★ 追加: ViewVerifyEmailResponse クラスをインポート

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Fortifyのデフォルト設定をマージ
        $this->mergeConfigFrom(__DIR__.'/../../vendor/laravel/fortify/config/fortify.php', 'fortify');

        // ★★★ ここに VerifyEmailViewResponse のバインドを追加 ★★★
        // Fortifyがメール認証ビューをレンダリングする際に使用するレスポンスクラスを定義します。
        $this->app->singleton(VerifyEmailViewResponse::class, ViewVerifyEmailResponse::class);

        // Fortifyのデフォルトアクションのバインド
        $this->app->singleton(\Laravel\Fortify\Contracts\CreatesNewUsers::class, CreateNewUser::class);
        $this->app->singleton(\Laravel\Fortify\Contracts\UpdatesUserProfileInformation::class, UpdateUserProfileInformation::class);
        $this->app->singleton(\Laravel\Fortify\Contracts\UpdatesUserPasswords::class, UpdateUserPassword::class);
        $this->app->singleton(\Laravel\Fortify\Contracts\ResetsUserPasswords::class, ResetUserPassword::class);
        $this->app->singleton(\Laravel\Fortify\Contracts\TwoFactorLoginResponse::class, RedirectIfTwoFactorAuthenticatable::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fortifyのデフォルトアクションを登録
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        // Fortifyのルートを登録 (web.phpでRoute::fortify()を使用している場合)
        // もしweb.phpでカスタムルートを使用している場合は、この行はコメントアウトまたは削除してください。
        // Route::fortify();

        // レートリミットの設定
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        // Fortifyのビューパスを設定
        Fortify::loginView(function () {
            return view('auth.login');
        });

        Fortify::registerView(function () {
            return view('auth.register');
        });

        Fortify::verifyEmailView(function () {
            return view('auth.verify-email');
        });

        // パスワードリセット関連のビューも必要に応じて設定
        Fortify::requestPasswordResetLinkView(function () {
            return view('auth.forgot-password');
        });

        Fortify::resetPasswordView(function () {
            return view('auth.reset-password');
        });
    }
}
