# BookShelf 書籍レビューアプリ

## 概要
COACHTECH 模擬案件にて作成した成果物です。(バックエンド部分のみ)
会員登録したユーザーが書籍を登録し、他のユーザーがその書籍をお気に入りに追加したりレビューできる書籍レビューアプリです。
レビュー評価をもとにしたランキング機能も備えており、人気の本を簡単に見つけられます。

## 機能一覧
- 認証機能
- 書籍登録機能 (CRUD)
- レビュー機能
- 書籍のお気に入り機能
- レビューのいいね機能
- ランキング機能
- 公開API (書籍の登録、データの取得等)

## 使用技術
- PHP 8.5
- Laravel 10.x
- Laravel Sail (Docker)
- Laravel Fortify
- Tailwind css
- MySQL 8.4
- phpMyAdmin

## 環境構築

### 必要なツール
- Docker Desktop
- Git
- テキストエディタ

### 1. リポジトリをクローン
git clone https://github.com/omu-39/bookshelf-app.git

### 2. ディレクトリ移動
cd bookshelf-app-git

### 3. Sailを含む依存パッケージのインストール
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    -e COMPOSER_CACHE_DIR=/tmp/composer_cache \
    laravelsail/php82-composer:latest \
    composer install

### 4. 環境変数を設定
cp .env.example .env

### 5. Sailの起動
./vendor/bin/sail up -d

### 6. アプリケーションキーの生成
./vendor/bin/sail artisan key:generate

### 7. DBのセットアップ
./vendor/bin/sail artisan migrate --seed

### 8. NPM依存パッケージのインストール
./vendor/bin/sail npm install

### 9. Alpine.jsのインストール
./vendor/bin/sail npm install alpinejs

### 10. Tailwind CSSのインストール
./vendor/bin/sail npm install

### 11. CSS/JSのビルド
- 本番用
./vendor/bin/sail npm run build

- 開発用
./vendor/bin/sail npm run dev


## ER図
![ER図(alt)](ER.png)

## テストアカウント
name:山田太郎 (書籍登録者)
email:yamada@example.com
password:password
------------------------------
name:鈴木花子
email:suzuki@example.com
password:password
------------------------------
name:田中一郎
email:tanaka@example.com
password:password
------------------------------
name:佐藤美咲
email:sato@example.com
password:password
------------------------------
name:高橋健太
email:takahashi@example.com
password:password
------------------------------

※初期データとして山田太郎のアカウントで書籍を11件登録しております。

## URL
- `http://localhost:8080` : phpMyAdmin

### Web画面
- `http://localhost/books` : 書籍一覧
- `http://localhost/books/{book}` : 書籍詳細
- `http://localhost/books/create` : 書籍登録フォーム（ログイン時）
- `http://localhost/books/{book}/edit` : 書籍編集フォーム（ログイン時）
- `http://localhost/books/{book}/reviews` : レビュー投稿（ログイン時）
- `http://localhost/reviews/{review}/edit` : レビュー編集フォーム（ログイン時）
- `http://localhost/reviews/{review}/like` : レビューいいね（ログイン時）
- `http://localhost/favorites` : お気に入り一覧（ログイン時）
- `http://localhost/books/{book}/favorite` : お気に入り切り替え（ログイン時）
- `http://localhost/genres` : ジャンル一覧（ログイン時）
- `http://localhost/genres/create` : ジャンル登録フォーム（ログイン時）
- `http://localhost/genres/{genre}` : ジャンル詳細（ログイン時）
- `http://localhost/genres/{genre}/edit` : ジャンル編集フォーム（ログイン時）
- `http://localhost/ranking` : レビューランキング一覧

※ `{book}` や `{genre}`、`{review}` には実際の ID を入れて使用します。

## 公開API

### 提供する機能
- 書籍データの取得
- 書籍登録
- 書籍更新
- 書籍削除

### エンドポイント一覧
- `http://localhost/api/v1/books` : 書籍一覧取得
- `http://localhost/api/v1/books/{book}` : 書籍詳細取得
- `http://localhost/api/v1/books` : 書籍作成（POST）
- `http://localhost/api/v1/books/{book}` : 書籍更新（PUT/PATCH）
- `http://localhost/api/v1/books/{book}` : 書籍削除（DELETE）

※ `{book}` には実際の ID を入れて使用します。
