# 1. ベースイメージ
FROM php:8.2-apache

# 2. 必要なツールとPostgreSQLドライバ
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip libonig-dev libpq-dev curl gnupg \
    && docker-php-ext-install pdo_pgsql mbstring zip bcmath opcache

# 3. Node.js (Tailwind用)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# 4. Apacheの設定
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# ★追加：これがないと「Not Found」になります！（.htaccessを有効化）
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

RUN a2enmod rewrite

# 5. Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. ファイルコピー
WORKDIR /var/www/html
COPY . .

# 7. インストール & ビルド
RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

# 8. 権限設定
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 9. ポート
EXPOSE 80

# 10. 起動コマンド（まずはサーバーを立ち上げることを最優先！）
CMD ["/bin/bash", "-c", "php artisan migrate --force && apache2-foreground"]