# FinanceBuddy.mk — Setup & Run (Фаза 0)

Овој фолдер (`financebuddy/`) е Laravel апликацијата (official Laravel Vue starter kit:
Inertia.js + Vue 3 + Tailwind 4 + Fortify auth). `vendor/` и `node_modules/` НЕ се
вклучени намерно — се генерираат при инсталација (стандардна Laravel пракса).

## Барања на серверот / dev машината
- PHP **8.3+** со екстензии: `pdo_mysql`, `mbstring`, `xml`, `curl`, `zip`, `bcmath`, `gd`
- Composer 2
- Node.js 20+ и npm
- MySQL 8+ (веќе инсталиран на серверот)

## Чекори за прв пат

```bash
cd financebuddy

# 1. PHP зависности
composer install

# 2. App key (.env веќе постои, конфигуриран за PostgreSQL)
php artisan key:generate

# 3. Создади база и корисник во MySQL
#    (промени лозинка по желба, истата стави ја во .env -> DB_PASSWORD)
mysql -e "CREATE DATABASE financebuddy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER 'financebuddy'@'127.0.0.1' IDENTIFIED BY 'СМЕНИ_МЕ';"
mysql -e "GRANT ALL PRIVILEGES ON financebuddy.* TO 'financebuddy'@'127.0.0.1'; FLUSH PRIVILEGES;"

# 4. Внеси ја лозинката во .env
#    DB_PASSWORD=СМЕНИ_МЕ

# 5. Миграции (создава табели за auth, sessions, cache, queue)
php artisan migrate

# 6. Frontend зависности + build
npm install
npm run build
```

## Локален развој (dev сервер)
```bash
php artisan serve        # backend на http://localhost:8000
npm run dev              # Vite dev сервер (во втор терминал, за hot reload)
```
Отвори `http://localhost:8000` → Welcome страница → Register/Login работи.

## Production (DigitalOcean, кратко)
```bash
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```
Постави nginx да покажува на `financebuddy/public` и PHP-FPM. `.env` со `APP_ENV=production`,
`APP_DEBUG=false`, вистинскиот `APP_URL`.

## Што е вградено (auth, Фаза 0)
- Регистрација, најава, заборавена/ресет лозинка, верификација на е-пошта
- 2FA (two-factor) и passkeys (преку Fortify)
- Settings: профил, безбедност, изглед (light/dark)
- Dashboard (празен, заштитен со `auth`)

## Следно (според 02-ROADMAP.md)
- Доврши Фаза 0: основен layout (sidebar/topbar/command palette skeleton) + Spatie Permission (роли: accountant, company_admin)
- Потоа Фаза 1: companies, warehouses, items
