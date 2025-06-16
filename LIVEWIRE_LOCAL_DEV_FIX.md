# Livewire Local Development Configuration Fix

## Problem
Livewire was making requests to the production URL (https://api.askproai.de) even when running locally, causing:
1. "Uncaught SyntaxError: Unexpected token '<'" on login page
2. "POST https://api.askproai.de/livewire/update 500 (Internal Server Error)" after login

## Solution Applied

### 1. Environment Configuration Updates
- Changed `APP_ENV` from `production` to `local` in `.env`
- Changed `SESSION_SECURE_COOKIE` from `true` to `false` for local development
- Added empty `ASSET_URL` and `LIVEWIRE_ASSET_URL` variables to `.env`

### 2. Livewire Configuration
- Updated `/config/livewire.php` to use environment-based URLs:
  ```php
  'asset_url' => env('LIVEWIRE_ASSET_URL', null),
  'app_url' => null,
  ```

### 3. Dynamic URL Configuration
- Modified `AppServiceProvider` to dynamically set Livewire URLs based on the current request:
  ```php
  if (request()->getSchemeAndHttpHost() !== config('app.url')) {
      config(['livewire.asset_url' => request()->getSchemeAndHttpHost()]);
      config(['livewire.app_url' => request()->getSchemeAndHttpHost()]);
  }
  ```

### 4. Security Headers Update
- Updated Content Security Policy in `ThreatDetectionMiddleware` to allow Livewire WebSocket connections:
  ```php
  'Content-Security-Policy' => "default-src 'self' http: https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' http: https:; style-src 'self' 'unsafe-inline' http: https:; connect-src 'self' http: https: ws: wss:;"
  ```

### 5. Created Local Development Example
- Added `.env.local.example` file as a template for local development configuration

## Usage

### For Local Development
1. Copy `.env.local.example` to `.env` (or update your existing `.env` with local values)
2. Ensure `APP_ENV=local` and `SESSION_SECURE_COOKIE=false`
3. Run `php artisan config:cache` after changes

### For Production
1. Set `APP_ENV=production` and `SESSION_SECURE_COOKIE=true`
2. Ensure `APP_URL` is set to your production URL
3. Run `php artisan config:cache` after deployment

## Important Notes
- The application now automatically detects whether it's running locally or in production
- No hardcoded URLs in the Livewire configuration
- Session cookies are only secured in production environments
- Content Security Policy allows both HTTP and HTTPS connections for flexibility