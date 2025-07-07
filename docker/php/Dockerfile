# docker/php/Dockerfile

# ベースイメージとして PHP-FPM (FastCGI Process Manager) を使用
FROM php:8.3-fpm

# 必要なシステムパッケージと PHP 拡張機能をインストール
# 全てのインストールと設定を単一のRUNコマンドにまとめ、行継続の問題を回避
RUN apt update && apt install -y --no-install-recommends \
    build-essential \
    libzip-dev \
    unzip \
    zlib1g-dev \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    libicu-dev \
    libxml2-dev \
    libmagickwand-dev \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) pdo_mysql opcache bcmath gd exif pcntl intl zip xml \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && apt clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# Composer をインストール (バイナリのみ)
COPY --from=composer:2.7.2 /usr/bin/composer /usr/local/bin/composer

# アプリケーションの作業ディレクトリを設定
WORKDIR /var/www/html

# これらのディレクトリはボリュームマウントされるホストから提供されるため、
# Dockerfileでパーミッションを設定する必要はありません。
# RUN chmod -R 777 storage bootstrap/cache の行は削除しました

# カスタム PHP 設定ファイルをコピー
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# コンテナ起動時に PHP-FPM が自動的に実行されるように設定 (デフォルトの動作)
CMD ["php-fpm"]
