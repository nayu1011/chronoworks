#!/bin/bash

# MySQL が起動するまで待機し、その後に指定されたコマンドを実行するスクリプト

# Exit on error
set -e

# 引数の解析
HOST="$1"

# MySQL ユーザー名とパスワードは環境変数から取得
shift

# 残りの引数を CMD に格納
CMD="$@"

# MySQL 接続情報の確認
echo -e "\033[33m[wait-for-mysql]\033[0m Waiting for MySQL at $HOST..."

# MySQL が起動するまでループで待機
until mysql --protocol=TCP -h "$HOST" -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" -e "SELECT 1" >/dev/null 2>&1; do
  echo -e "\033[31m[wait-for-mysql]\033[0m MySQL not ready. Retrying..."
  # 1秒待機
  sleep 1
done

# MySQL が起動したらメッセージを表示 
echo -e "\033[32m[wait-for-mysql]\033[0m MySQL is ready!"

# 指定されたコマンドを実行
exec $CMD
