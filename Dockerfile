# 1. ベースイメージ（PHP 8.2 + Apache）
FROM php:8.2-apache

# 2. 必要なパッケージのインストール（PostgreSQL, Node.jsなど）
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip libonig-dev libpq-dev curl gnupg \
    && docker-php-ext-install pdo_pgsql mbstring zip bcmath opcache

# 3. Node.js (Tailwind用) をインストール
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# 4. Apacheの設定（publicフォルダをルートにする）
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf
RUN a2enmod rewrite

# 5. Composer（PHPのパッケージ管理）を入れる
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. アプリのファイルをコピー
WORKDIR /var/www/html
COPY . .

# 7. ライブラリのインストール & ビルド
RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

# 8. 権限の設定（書き込み許可）
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 9. ポート設定
EXPOSE 80

# 10. 起動コマンド（マイグレーションしてからApacheを起動）
# ★ここが重要！シェル経由で実行することでエラーを防ぎます
# migrate:fresh --seed を実行して、DBを強制リセットする
CMD ["/bin/bash", "-c", "php artisan migrate --force && apache2-foreground"]