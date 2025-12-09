# 概要（laravel-start-template）
Laravel環境構築の省力化がメインです。
セットアップ手順に則って操作してください。

## 構築する開発環境
|モジュール|バージョン |
|---------|----------|
|nginx    |1.24.0    |
|php      |8.1.26-fpm|
|Laravel  |10.50.0   |
|mysql    |8.0.36    |

## セットアップ手順
`projectName`はプロジェクトルートディレクトリに読み替えてください 

### 1. プロジェクト作成 & テンプレート取得
```
mkdir `projectName`
git clone git@github.com:nayu1011/laravel-start-template.git `projectName`
cd `projectName`
```

### 2. `.env`（UID/GID）を自動生成
Docker のコンテナ内ユーザーWindowsとファイル所有者を一致させるため、  
以下のコマンドを実行して .env を自動生成してください。

```
echo "UID=$(id -u)" > .env
echo "GID=$(id -g)" >> .env
```

### 3. Laravel アプリ用の `.env` を作成
```
cp src/.env.example src/.env  
```
※ DB接続情報は `docker-compose.yml` に合わせて修正してください。

### 4. Docker コンテナを起動

```bash
docker compose up -d --build
```

### 5. PHP コンテナに入り Laravel をセットアップ

```bash
docker compose exec php bash
composer install
php artisan key:generate
```


必要に応じて:
```
npm install
```

## Git 管理しないもの
以下を参照してください
```
/.gitignore
/src/.gitignore
```