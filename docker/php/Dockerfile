# 安定版のPHP8.1.26-FPM
FROM php:8.1.26-fpm 

ARG UID
ARG GID

# appuser(appgroup)を作成
RUN groupadd -g ${GID} appgroup \
  && useradd -u ${UID} -g appgroup -m appuser

# PHP拡張・依存パッケージのインストール(root)
RUN apt update \
  && apt install -y \
  default-mysql-client \
  zlib1g-dev \
  libzip-dev \
  unzip \
  libjpeg-dev \
  libpng-dev \
  libfreetype6-dev \
  && docker-php-ext-configure gd \
  --with-freetype \
  --with-jpeg \
  && docker-php-ext-install \
  pdo_mysql zip gd

# Composerのインストール（root） バージョン2.6.6（安定版）を指定
RUN curl -sS https://getcomposer.org/installer | php -- --version=2.6.6 \
  && mv composer.phar /usr/local/bin/composer

# composer/psyshなどがHOMEを使うため設定
ENV HOME=/home/appuser

# 作業ディレクトリ設定
WORKDIR /var/www

# entrypointとwait-for-mysqlをコピー（root）
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
COPY wait-for-mysql.sh /usr/local/bin/wait-for-mysql.sh

# 実行権限を付与（root のうちにやる）
RUN chmod +x /usr/local/bin/entrypoint.sh \
  && chmod +x /usr/local/bin/wait-for-mysql.sh

# 以降はappuserで動作（rootではなく）
USER appuser

# Composerの初期化（キャッシュ作成など）
RUN composer --version

# デフォルトコマンドをphp-fpmに設定
CMD ["php-fpm"]
