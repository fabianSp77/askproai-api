# Technology Stack

**Analysis Date:** 2026-01-19

## Languages

**Primary:**
- PHP 8.3 - Backend application logic, API endpoints, services

**Secondary:**
- JavaScript/ES Modules - Frontend interactivity, Alpine.js components
- TypeScript - E2E testing configuration (Playwright)

## Runtime

**Environment:**
- PHP 8.3.23 (CLI/FPM)
- Node.js (for Vite build tooling)

**Package Manager:**
- Composer 2.x - PHP dependencies
- npm - JavaScript dependencies
- Lockfiles: `composer.lock`, `package-lock.json` present

## Frameworks

**Core:**
- Laravel 11.31+ - Full-stack PHP framework
- Filament 3.3+ - Admin panel and resource management
- Livewire - Reactive components (via Filament)

**Frontend:**
- Alpine.js 3.15 - Lightweight JavaScript framework
- Tailwind CSS 3.4 - Utility-first CSS framework
- Flowbite - UI component library
- FullCalendar 6.1 - Calendar widgets (daygrid, timegrid, resource-timeline)

**Testing:**
- Pest 3.0 - PHP testing framework (PHPUnit wrapper)
- Playwright 1.57 - E2E browser testing
- Puppeteer 24.23 - Browser automation (legacy E2E)

**Build/Dev:**
- Vite 6.0 - Asset bundling and HMR
- VitePress 1.6 - Documentation site generator
- Laravel Pint - PHP code style fixer
- Concurrently - Parallel process runner

## Key Dependencies

**Critical:**
- `laravel/framework` ^11.31 - Core framework
- `filament/filament` ^3.3 - Admin panel
- `spatie/laravel-permission` ^6.21 - Role/permission management
- `spatie/laravel-activitylog` ^4.10 - Audit logging
- `laravel/sanctum` (via framework) - API authentication

**Infrastructure:**
- `laravel-notification-channels/twilio` ^4.1 - SMS notifications
- `pusher/pusher-php-server` ^7.2 - Real-time broadcasting
- `twilio/sdk` ^8.8 - SMS/WhatsApp communication
- `league/flysystem-aws-s3-v3` ^3.0 - S3/MinIO file storage
- `barryvdh/laravel-dompdf` ^3.1 - PDF generation
- `maatwebsite/excel` ^3.1 - Excel import/export
- `giggsey/libphonenumber-for-php` ^9.0 - Phone number parsing
- `stichoza/google-translate-php` ^5.3 - Free translation service
- `spatie/icalendar-generator` ^3.0 - ICS calendar files

**Observability:**
- `laravel/telescope` ^5.11 - Request/job debugging
- `laravel/pail` ^1.1 - Real-time log tailing

**API Documentation:**
- `dedoc/scramble` ^0.13.10 - OpenAPI spec generation

## Configuration

**Environment:**
- `.env` file for environment-specific configuration
- `config/*.php` for application settings
- Key configs: `services.php`, `calcom.php`, `retell.php`, `billing.php`, `gateway.php`, `features.php`

**Build:**
- `vite.config.js` - Asset bundling (CSS + JS bundles)
- `tailwind.config.js` - Tailwind CSS customization with dark mode
- `postcss.config.js` - PostCSS processing
- `phpunit.xml` - Test configuration

**Feature Flags:**
- `config/features.php` - Extensive feature flag system
  - Phonetic name matching
  - Slot locking (Redis-based)
  - Slot intelligence system
  - Processing time/split appointments
  - Customer portal phases
  - Parallel Cal.com booking

## Platform Requirements

**Development:**
- PHP 8.2+ with extensions: `pdo_mysql`, `phpredis`, `curl`, `gd`
- MySQL/MariaDB or SQLite
- Redis (optional, for caching/locking)
- Node.js 18+ for frontend tooling
- Composer 2.x

**Production:**
- PHP 8.2+ with FPM
- MySQL/MariaDB (PostgreSQL configured but MySQL primary)
- Redis for caching, sessions, slot locking
- Queue worker for async jobs
- S3-compatible storage for audio files

**Testing:**
- MySQL database named `testing`
- PHP extensions for feature tests
- Playwright browsers for E2E

## Entry Points

**Web:**
- `public/index.php` - HTTP entry point
- `artisan` - CLI entry point

**Frontend Bundles:**
- `resources/css/app.css` - Main stylesheet
- `resources/js/app.js` - Main JavaScript
- `resources/js/app-admin.js` - Admin-specific bundle

**Config Entry:**
- `bootstrap/app.php` - Application bootstrap
- `config/app.php` - Core application config

---

*Stack analysis: 2026-01-19*
