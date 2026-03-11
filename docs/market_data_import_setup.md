# SkinEmporium - Real Market Data Setup (CSFloat + Steam)

## 1) What was added in code
- Real DB models: `Skin`, `Listing`, `Order`, `Payment`, `Watchlist`
- User fields for Steam and wallet
- Migrations for marketplace tables
- CSFloat importer service: `app/Services/CsfloatMarketImporter.php`
- Artisan command: `php artisan market:import-csfloat`

## 2) Run database migrations
```bash
php artisan migrate
```

## 3) Add API credentials to `.env`
```env
CSFLOAT_BASE_URL=https://csfloat.com/api/v1
CSFLOAT_LISTINGS_PATH=/listings
CSFLOAT_API_KEY=your_csfloat_api_key

STEAM_WEB_API_KEY=your_steam_web_api_key
STEAM_OPENID_REALM="${APP_URL}"
STEAM_OPENID_RETURN_TO="${APP_URL}/auth/steam/callback"
```

## 4) Import listings from CSFloat
```bash
php artisan market:import-csfloat --pages=1 --limit=50
```

Increase pages if you want more records:
```bash
php artisan market:import-csfloat --pages=5 --limit=50
```

## 5) API key setup notes
- CSFloat API key: create/login your CSFloat account and generate API key from their developer/api settings. Docs: https://docs.csfloat.com/
- Steam Web API key: generate from Steam community developer page. Docs: https://steamcommunity.com/dev
- Sign in with Steam (OpenID): use Steam OpenID flow (not OAuth). Reference: https://steamcommunity.com/dev

## 6) Current scope
- Importer focuses on listing + item data into local DB.
- Advanced marketplace logic (escrow, bot trading, pattern index, full purchase sync) is not included yet.
