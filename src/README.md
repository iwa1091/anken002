# Laravel Docker Compose Template

## 概要
このプロジェクトは、LaravelアプリケーションをDocker Composeで構築するためのテンプレートです。  
開発環境の迅速な立ち上げを目的としています。特に、APP_KEY の設定やファイルパーミッションなど、一般的なDocker環境で発生しやすい問題を回避するための詳細な手順が含まれています。

---

## データベースER図
プロジェクトのデータベーススキーマは、以下のリンクから参照できます。

- [ER図（dbdiagram.io）](https://dbdiagram.io/d/67ec841b4f7afba18402afbe)

---

## 前提条件
このプロジェクトをセットアップする前に、以下のソフトウェアがシステムにインストールされ、動作していることを確認してください。

- **Docker Desktop (またはDocker EngineとDocker Compose)**  
  - Macユーザーの場合: Docker Desktop for Macをインストールし、アプリケーションを起動してDockerエンジンが実行されている状態にしてください。

- **Git**

- **WSL2 (Windowsユーザーの場合)**  
  ホストOSとのボリュームマウントにおけるパフォーマンスとパーミッションの互換性のため、WSL2環境での利用を推奨します。

---

## 初期セットアップ手順
LaravelアプリケーションをDocker環境で起動するために、以下の手順を正確に順番に実行してください。

### 1. プロジェクトのクローン
まず、リポジトリをローカルにクローンし、プロジェクトのルートディレクトリに移動します。

```bash
git clone <あなたのリポジトリのURL> anken02
cd anken02/ # プロジェクトのルートディレクトリ（docker-compose.ymlがある場所）に移動
```

## 2.Laravel環境の初期化

```bash

# ホスト側の既存のvendorディレクトリを強制削除
cd src/
sudo rm -rf vendor/ || true 
rm -f composer.lock
rm -f .env

#.env.exampleから.envをコピー
cp .env.example .env

#セッションクッキー設定を追記
if ! grep -q "SESSION_SECURE_COOKIE=false" .env; then 
    echo "SESSION_SECURE_COOKIE=false" >> .env
fi
```

 ## 3.APP_KEYを生成して.envに設定
 ```bash
cd src/
ESCAPED_NEW_APP_KEY=$(printf %s "NEW_APP_KEY" | sed -e 's/[\/&]/\\&/g')
sed -i "" -e "/^APP_KEY=/d" .env
sed -i "" -e "/^APP_URL=/a APP_KEY={ESCAPED_NEW_APP_KEY}" .env

cat .env | grep APP_KEY
```

## 4. パーミッション調整
```bash
#ホスト側srcディレクトリのパーミッションを調整
cd anken02/src/
COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs
```

## 5. Composerインストール
```bash
COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs
```

## 6. Dockerコンテナのビルド
```bash
cd ../
docker-compose build php
```
## 7. コンテナ内部でのLaravel初期設定とキャッシュクリア
```bash
#コンテナ内部でのLaravel初期設定とパーミッション調整
docker-compose exec php bash -c '
    set -e;

    rm -rf /var/www/html/bootstrap/cache/* || true;
    rm -rf /var/www/html/storage/framework/cache/* || true;
    rm -rf /var/www/html/storage/framework/views/* || true;

    chown -R www-data:www-data /var/www/html/vendor;
    chmod -R 775 /var/www/html/vendor;

    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache;
    chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache;

    chmod 664 /var/www/html/.env;
    chown www-data:www-data /var/www/html/.env;

    php artisan cache:clear;
    php artisan config:clear;
    php artisan route:clear;
    php artisan view:clear;
    php artisan clear-compiled;
    php artisan optimize;
    php artisan package:discover --ansi;
    php artisan config:cache;
    php artisan view:cache;
    php artisan migrate:fresh --seed;

    php artisan tinker --execute="echo config(\"app.key\");";
'
```

## アプリケーションへのアクセス

- [アプリケーション](http://localhost/login)

- [phpMyAdmin](http://localhost:8080/)
- [ユーザー: laravel_user, パスワード: laravel_pass]

- [MailHog](http://localhost:8025/)

※ 初回アクセス時や問題が発生した場合は、ブラウザのキャッシュとCookieをクリアし、シークレットモードでアクセスしてください。