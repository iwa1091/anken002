Laravel Docker Compose 開発環境
概要
このプロジェクトは、LaravelアプリケーションをDocker Composeで構築するためのテンプレートです。開発環境の迅速な立ち上げを目的としています。特に、APP_KEY の設定やファイルパーミッションなど、一般的なDocker環境で発生しやすい問題を回避するための詳細な手順が含まれています。

前提条件
このプロジェクトをセットアップする前に、以下のソフトウェアがシステムにインストールされていることを確認してください。

Docker Desktop (またはDocker EngineとDocker Compose)

Git

(Windowsユーザーの場合) WSL2 (Windows Subsystem for Linux 2): ホストOSとのボリュームマウントにおけるパフォーマンスとパーミッションの互換性のため、WSL2環境での利用を推奨します。

初期セットアップ手順
LaravelアプリケーションをDocker環境で起動するために、以下の手順を正確に順番に実行してください。

1. プロジェクトのクローン
まず、リポジトリをローカルにクローンし、プロジェクトのルートディレクトリに移動します。

git clone <あなたのリポジトリのURL> anken02
cd anken02/ # プロジェクトのルートディレクトリに移動

2. .envファイルの準備とAPP_KEYの生成
.env ファイルを作成し、アプリケーションキーを生成します。この手順は、Docker環境特有のパーミッション問題を回避するために、ホスト側から一時的にPHPコンテナを利用して行います。

プロジェクトルートディレクトリ (/home/ri309/anken02/) で実行:

# srcディレクトリに移動してクリーンアップ
cd src/
sudo rm -rf vendor/ # ホスト側の既存のvendorディレクトリを強制削除
rm -f composer.lock # ホスト側の既存のcomposer.lockファイルを削除
rm -f .env # 既存の.envファイルを削除

# .env.exampleから.envをコピー
cp .env.example .env

# ローカル開発環境でのセッションクッキー問題を回避するため、SESSION_SECURE_COOKIE=false を追記
if ! grep -q "SESSION_SECURE_COOKIE=false" .env; then
    echo "SESSION_SECURE_COOKIE=false" >> .env
fi

# 一時的にPHPコンテナを起動してAPP_KEYを生成
# (生成後、自動的にPHPコンテナは停止します)
cd ../ # プロジェクトルートに戻る
docker-compose -f /home/ri309/anken02/docker-compose.yml up -d php > /dev/null 2>&1 # phpサービスのみ起動（サイレント）
sleep 5 # コンテナが起動するまで数秒待つ
NEW_APP_KEY=$(docker-compose exec php php -r 'echo base64_encode(random_bytes(32));')
docker-compose -f /home/ri309/anken02/docker-compose.yml stop php > /dev/null 2>&1

# 生成したAPP_KEYをsrc/.envファイルに挿入/置換
cd src/ # srcディレクトリに戻る
ESCAPED_NEW_APP_KEY=$(printf %s "$NEW_APP_KEY" | sed -e 's/[\/&]/\\&/g')
sed -i "/^APP_KEY=/d" .env # 既存のAPP_KEY行を削除
sed -i "/^APP_URL=/a APP_KEY=${ESCAPED_NEW_APP_KEY}" .env # APP_URLの直後に新しいAPP_KEYを挿入

echo "--- .env content on host (確認用) ---"
cat .env | grep APP_KEY # APP_KEYが設定されたことを確認

# ホスト側srcディレクトリのパーミッションを調整
# これにより、composer install時のPermission deniedを防ぎ、Dockerとの連携を円滑にします。
sudo chown -R $(whoami):$(whoami) . # 現在のユーザーをオーナーにする
sudo find . -type d -exec chmod u+rwx,g+rx,o+rx {} + # ディレクトリ: オーナーにrwx, グループ・その他にrx
sudo find . -type f -exec chmod u+rw,g+r,o+r {} +   # ファイル: オーナーにrw, グループ・その他にr
sudo chmod -R g+w vendor/ || true # vendorがまだ存在しない場合もエラーにしない (グループ書き込み権限を付与)
sudo chmod -R g+w storage/ || true # storageにグループ書き込み権限を付与
sudo chmod -R g+w bootstrap/cache/ || true # bootstrap/cacheにグループ書き込み権限を付与

3. ホスト側でのComposer依存関係のインストール
プロジェクトの依存関係をホスト側でインストールし、vendor ディレクトリを生成します。これにより、Dockerコンテナ内でのComposer実行時のパーミッション問題を回避します。

src ディレクトリ (/home/ri309/anken02/src/) で実行:

COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs

4. Dockerコンテナのビルド
docker/php/Dockerfile や app/Http/Kernel.php の変更を反映させるために、PHPコンテナをビルドします。

プロジェクトルートディレクトリ (/home/ri309/anken02/) で実行:

cd ../ # srcディレクトリからプロジェクトルートに戻る (もしsrcにいる場合)
docker-compose build php

5. Dockerコンテナの起動
全てのDockerコンテナ（nginx, php, mysql, phpmyadmin, mailhog）を起動します。

プロジェクトルートディレクトリ (/home/ri309/anken02/) で実行:

docker-compose up -d

6. コンテナ内部でのLaravel初期設定とパーミッション調整
LaravelアプリケーションがDockerコンテナ内で正しく動作するための最終設定とパーミッション調整を行います。特に、storage と bootstrap/cache のパーミッションを調整し、Laravel のコマンドを安全に実行します。

プロジェクトルートディレクトリ (/home/ri309/anken02/) で実行:

docker-compose exec php bash -c '
    set -e; # コマンドが失敗した場合、即座に終了

    echo "Forcing deletion of Laravel cache directories inside container...";
    rm -rf /var/www/html/bootstrap/cache/* || true;
    rm -rf /var/www/html/storage/framework/cache/* || true;
    rm -rf /var/www/html/storage/framework/views/* || true;
    echo "Laravel cache directories forced deleted.";
    
    echo "Adjusting ownership and permissions for vendor, storage, bootstrap/cache, .env inside container...";
    chown -R www-data:www-data /var/www/html/vendor;
    chmod -R 775 /var/www/html/vendor;
    
    # storage と bootstrap/cache ディレクトリのパーミッションをwww-dataに設定（一時的に777で最大権限を付与）
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache;
    chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache; 
    
    # .env ファイルのパーミッションと所有者もwww-dataに設定
    chmod 664 /var/www/html/.env;
    chown www-data:www-data /var/www/html/.env;
    echo "Permissions adjusted inside container.";
    
    echo "Running php artisan cache:clear...";
    php artisan cache:clear;
    echo "Running php artisan config:clear...";
    php artisan config:clear;
    echo "Running php artisan route:clear...";
    php artisan route:clear;
    echo "Running php artisan view:clear...";
    php artisan view:clear;
    echo "Running php artisan clear-compiled...";
    php artisan clear-compiled;
    
    echo "Running php artisan optimize...";
    php artisan optimize;
    echo "Running php artisan package:discover --ansi...";
    php artisan package:discover --ansi;
    
    echo "Running php artisan config:cache (bakes .env values into config)...";
    php artisan config:cache;
    echo "Running php artisan view:cache...";
    php artisan view:cache;
    
    echo "Running php artisan migrate:fresh --seed...";
    php artisan migrate:fresh --seed;
    
    echo "--- VERIFYING APP_KEY inside container config (should match .env) ---";
    # php artisan tinker --execute で直接config('app.key')の値を取得
    php artisan tinker --execute="echo config(\'app.key\');";
    echo "--- Container setup completed ---";
'

7. PHPコンテナの強制再作成
コンテナ内部での設定が確実にPHPプロセスに反映されるように、PHPコンテナを強制的に再作成します。

プロジェクトルートディレクトリ (/home/ri309/anken02/) で実行:

docker-compose up -d --force-recreate php

アプリケーションへのアクセス
全てのセットアップが完了したら、ブラウザから以下のURLにアクセスしてください。

アプリケーション: http://localhost/

phpMyAdmin: http://localhost:8080/ (ユーザー: laravel_user, パスワード: laravel_pass)

MailHog: http://localhost:8025/

初回アクセス時や問題が発生した場合は、必ずブラウザのキャッシュとCookieをクリアし、シークレットモード（プライベートブラウジング）でアクセスを試みてください。

トラブルシューティング
「Unsupported cipher or incorrect key length.」エラー
このエラーは、APP_KEY が正しく設定されていないか、Laravelのキャッシュが古いキーを保持している場合に発生します。
上記の手順「2. .envファイルの準備とAPP_KEYの生成」と「6. コンテナ内部でのLaravel初期設定とパーミッション調整」を再度、正確に実行してください。特に、php artisan key:generate が成功し、APP_KEY が .env ファイルに書き込まれたことを確認してください。

「Permission denied」エラー (特に storage/logs/laravel.log への書き込み時)
これは、Dockerコンテナ内のPHPプロセス（www-dataユーザー）が、ログファイルやキャッシュディレクトリへの書き込み権限を持っていない場合に発生します。
上記の手順「2. .envファイルの準備とAPP_KEYの生成」でのホスト側のパーミッション調整と、「6. コンテナ内部でのLaravel初期設定とパーミッション調整」でのコンテナ内のパーミッション調整（特に chown -R www-data:www-data と chmod -R 777 の部分）が正しく適用されているか確認してください。

ReflectionException または Class "Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance" does not exist
このプロジェクトはLaravel 10を使用しており、このミドルウェアクラスはLaravel 10で削除されています。
app/Http/Kernel.php ファイルを開き、protected $middleware 配列から以下の行を削除していることを確認してください。

\Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,

修正後、手順4以降を再度実行してPHPコンテナをビルドし直してください。

その他の問題
docker-compose logs php および docker-compose logs nginx コマンドで、各コンテナのログを確認し、詳細なエラーメッセージを探してください。

docker-compose restart の代わりに docker-compose up -d --force-recreate [service_name] を使用して、コンテナを完全に再作成してみてください。