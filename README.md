# Sequence

Vehicle Sequencing (Logistics) application built with Laravel 12.

## Requirements

- PHP 8.2+
- MySQL 8.0+
- Node.js 18+
- Composer 2.x

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run dev
```

## Running

```bash
php artisan serve --port=9000
```

Or use the combined dev command:

```bash
composer dev
```

++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
=====================================================================131225
MySQL database and restore it to another database

1. mysqldump -h 127.0.0.1 -u root -p --single-transaction --routines --triggers
   --events bhukkad_central > backup.sql
2. mysql -h 127.0.0.1 -u root -p bhukkad_central < backup.sql
3. curl -o backup.sql
   https://bhukkad-central.s3.ap-south-1.amazonaws.com/upload/backup.sql
4. php artisan serve --host=127.0.0.1 --port=9000
5. php artisan optimize:clear
6. Use Antigravity Browser Control to handle this issue
7. /Users/pinakranjansahoo/.gemini/antigravity/browser_recordings

php artisan migrate:fresh --seed. (THI IS VERY DESTRUCTIVE COMMAND ONLY USE IT
WHEN YOU ARE SURE)

1. php artisan price:activate-scheduled
2. should run daily (typically at midnight or via cron).
3. antigravity-usage quota --refresh
4. antigravity-usage