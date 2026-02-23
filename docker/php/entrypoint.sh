#!/bin/bash

# Laravel アプリケーションの初期化スクリプト
# Laravel のマイグレーションやシーディング、書き込み権限の設定を行う
set -e

# Laravel 実行に必要なディレクトリの権限調整
# 対象ディレクトリの配列
TARGET_DIRS=(
  /var/www/storage
  /var/www/bootstrap/cache
)

# Laravelが必要な書き込み権限を自動修正（root所有事故の対策）
# 対象ディレクトリに775（グループ書き込み権限）を付与する
for DIR in "${TARGET_DIRS[@]}"; do
  if [ -d "$DIR" ]; then
    chmod -R 775 "$DIR"
  fi
done

# Laravel実行ユーザーにディレクトリの所有権を変更（必要に応じてコメントアウトを外す）
#chown -R appuser:appgroup /var/www/storage /var/www/bootstrap/cache
# MySQL の起動完了を待機してからマイグレーションとシーディングを実行
# ./wait-for-mysql.sh mysql:3306 -- php artisan migrate:refresh --seed --force

# php-fpm を実行（PID置換）
exec "$@"
