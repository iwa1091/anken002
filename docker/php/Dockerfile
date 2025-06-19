FROM php:8.3-fpm

# 必要なパッケージをインストール
RUN apt update \
    && apt install -y --no-install-recommends \
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
    && docker-php-ext-install -j$(nproc) pdo_mysql opcache bcmath gd exif pcntl intl zip \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && apt clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# Composerのインストール (マルチステージビルド推奨)
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# アプリケーションのコードをコンテナ内にコピー
# docker-compose.yml の volumes: - ./src:/var/www/ に合わせる
WORKDIR /var/www
COPY ./src /var/www  

# Composerの依存関係をインストール
# Laravelプロジェクトには必須です
RUN composer install --no-dev --optimize-autoloader --no-interaction

# ストレージディレクトリのパーミッション設定
# Laravelが書き込みを行うために必要です
RUN chmod -R 777 storage bootstrap/cache

# PHPの設定ファイルをコピー (docker-compose.ymlのマウントパスに合わせる)
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# コンテナがリッスンするポート
EXPOSE 9000

# コンテナ起動時にPHP-FPMを実行します
CMD ["php-fpm"]
