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

## 管理者による勤怠修正について

本システムでは、管理者は勤怠情報を直接修正可能としています。
この場合、修正内容は `attendances` および `breaks` テーブルに
直接反映され、申請履歴（applications）は作成されません。

これは本アプリケーションが小規模システムであり、
修正履歴の厳密な証跡管理を要件としていないためです。

なお、将来的な拡張として、以下の対応が考えられます。

- 管理者修正も `applications` テーブルに記録する
- `source_type` カラムを追加し、
  - `user_request`（ユーザー申請）
  - `admin_fix`（管理者直接修正）
  を区別することで証跡管理を強化する

## テーブル仕様書
applicationsのstatusはdefault値'pending'とする。
（レコードinsert時点で申請が発生したとみなすため）