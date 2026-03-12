# SkinEmporium

SkinEmporium is a Laravel 12 learning project for a CS2 skin marketplace. It includes:
- Market browsing
- Sell flow with Steam OpenID connect
- Steam trade-link validation
- Steam inventory loading for listing creation

## Requirements
- PHP 8.2+
- Composer 2+
- Node.js 18+
- npm 9+

## Install
1. Install PHP dependencies:
```bash
composer install
```

2. Create env file:
```bash
cp .env.example .env
```

3. Generate app key:
```bash
php artisan key:generate
```

4. Create SQLite database file:
```bash
touch database/database.sqlite
```

5. Run migrations and seed data:
```bash
php artisan migrate --seed
```

6. Install frontend dependencies:
```bash
npm install
```

## Run (local)
Use two terminals.

Terminal 1:
```bash
php artisan serve
```

Terminal 2:
```bash
npm run dev
```

Open `http://127.0.0.1:8000`.

## Steam setup (Sell page)
In `.env`, set these values:

```env
APP_URL=http://127.0.0.1:8000
STEAM_OPENID_REALM="${APP_URL}"
STEAM_OPENID_RETURN_TO="${APP_URL}/auth/steam/callback"
STEAM_WEB_API_KEY=your_steam_web_api_key
```

Notes:
- `STEAM_WEB_API_KEY` is optional for OpenID login itself, but recommended for profile name/avatar enrichment.
- Your Steam trade link must match the connected Steam account.

## Seeded data
`php artisan migrate --seed` runs `DatabaseSeeder` and creates:
- `test@example.com` (basic local test user)

## Useful commands
```bash
php artisan test
php artisan route:list
npm run build
```
