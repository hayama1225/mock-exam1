# coachtechフリマ

## セットアップ（Docker / bashに入る方式）

```bash
# 0) 取得
git clone https://github.com/hayama1225/mock-exam1.git
cd mock-exam1

# 1) コンテナ起動（初回は --build 推奨）
docker compose up -d --build

# 2) PHPコンテナに入る（以降は /var/www = リポジトリの ./src）
docker compose exec php bash
cd /var/www

# 3) 依存インストール
composer install

# 4) .env 作成 & アプリキー生成
cp .env.example .env
php artisan key:generate

# 5) マイグレーション & シーディング
php artisan migrate --seed

# 6) ストレージ公開（画像表示に必須）
php artisan storage:link

# 7) コンテナから抜ける
exit
```
## URL
アプリ（商品一覧）：http://localhost/
phpMyAdmin：http://localhost:8080/
MailHog：http://localhost:8025/
ユーザー登録（Fortify）：http://localhost/register
ログイン（Fortify）：http://localhost/login

### テストアカウント（Seeder投入後に使用可）
メール: seed-seller@example.com
パスワード: password123

### 参考メモ
- docker-compose.yml の version 警告
    Docker Compose v2 では version: は不要。気になる場合は先頭の version: '3.8' を削除。
- mbstring の Deprecated 警告
    mbstring.internal_encoding は PHP 8.1 で非推奨。開発では無視可。抑止する場合は PHP の ini から当該設定を除去/コメントアウト。
- Stripeキー
    .env にテストキー未設定でも画面は動作。ただし決済は不可。利用時は STRIPE_KEY / STRIPE_SECRET / STRIPE_WEBHOOK_SECRET をテスト用に設定。
- MailHog 統一
    .env は MAIL_MAILER=smtp, MAIL_HOST=mailhog, MAIL_PORT=1025 に統一（mailpit の行があれば削除）。

## 使用技術（実行環境）
- PHP 8.1.33
- Laravel 10.48.29
- MySQL 8.0.26
- nginx 1.21.1
- MailHog v1.0.1（Docker）

## ER図
```mermaid
erDiagram
  users {
    bigint id PK
    string name
    string email
    string password
    timestamp email_verified_at
    timestamp created_at
    timestamp updated_at
  }

  profiles {
    bigint id PK
    bigint user_id FK
    string avatar_path
    timestamp created_at
    timestamp updated_at
  }

  items {
    bigint id PK
    bigint seller_id FK
    string name
    string brand
    text description
    integer price
    string condition
    string image_path
    timestamp created_at
    timestamp updated_at
  }

  categories {
    bigint id PK
    string name
    timestamp created_at
    timestamp updated_at
  }

  category_item {
    bigint id PK
    bigint category_id FK
    bigint item_id FK
    timestamp created_at
    timestamp updated_at
  }

  likes {
    bigint id PK
    bigint user_id FK
    bigint item_id FK
    timestamp created_at
    timestamp updated_at
  }

  comments {
    bigint id PK
    bigint user_id FK
    bigint item_id FK
    text body
    timestamp created_at
    timestamp updated_at
  }

  purchases {
    bigint id PK
    bigint user_id FK
    bigint item_id FK
    string address
    timestamp created_at
    timestamp updated_at
  }

  users ||--|| profiles : has_one
  users ||--o{ items : sells
  users ||--o{ likes : likes
  users ||--o{ comments : comments
  users ||--o{ purchases : purchases

  items ||--o{ likes : liked_by
  items ||--o{ comments : commented_by
  items ||--o{ purchases : purchased_by

  items }o--o{ categories : through_category_item
```

