# Quick Start

Get AskPro API Gateway up and running in minutes.

## Prerequisites

- PHP 8.2+
- MySQL 8.0+
- Redis
- Node.js 18+
- Composer

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/fabianSp77/askproai-api.git
cd askproai-api
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure Database

Edit `.env` with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=askproai
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 5. Run Migrations

```bash
php artisan migrate
php artisan db:seed
```

### 6. Build Assets

```bash
npm run build
```

### 7. Start the Server

```bash
php artisan serve
```

Visit `http://localhost:8000/admin` to access the admin panel.

## Default Credentials

| Email | Password |
|-------|----------|
| admin@askproai.de | (set in seeder) |

## Next Steps

- [Architecture Overview](/guide/architecture) - Understand the system design
- [Service Gateway](/guide/service-gateway) - Configure ticket management
- [Retell Integration](/guide/retell) - Set up voice AI
- [Cal.com Integration](/guide/calcom) - Configure scheduling

## Common Issues

### Permission Errors

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Queue Not Processing

```bash
php artisan queue:work --daemon
```

### Cache Issues

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```
