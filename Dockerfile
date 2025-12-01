# --- 1. デザインを作る工程 (Node.js) ---
    FROM node:20 AS node_builder
    WORKDIR /app
    COPY . .
    RUN npm install && npm run build
    
    # --- 2. アプリを動かす工程 (PHP & Apache) ---
    FROM php:8.2-apache
    
    # 必要なツールと、Neon(PostgreSQL)用の部品を入れる
    RUN apt-get update && apt-get install -y \
        libzip-dev \
        zip \
        unzip \
        libonig-dev \
        libpq-dev \
        && docker-php-ext-install pdo_mysql pdo_pgsql zip bcmath
    
    # Apacheの設定（アクセス先をpublicフォルダに向ける）
    ENV APACHE_DOCUMENT_ROOT /var/www/html/public
    RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
    RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf
    RUN a2enmod rewrite
    
    # Composer（PHPのパッケージ管理）を入れる
    COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
    
    WORKDIR /var/www/html
    COPY . .
    
    # さっき作ったデザインファイルを持ってくる
    COPY --from=node_builder /app/public/build ./public/build
    
    # ライブラリをインストール
    RUN composer install --no-dev --optimize-autoloader
    
    # 権限の設定（書き込みできるようにする）
    RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
    
    # ポート80を開ける
    EXPOSE 80