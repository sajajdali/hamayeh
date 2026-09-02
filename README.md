# Hamayesh

سامانهٔ ثبت‌نام ارجاعی گردهمایی کنکور، پیاده‌سازی‌شده با Laravel 13، Livewire 4، Tailwind CSS 4 و رابط فارسی راست‌به‌چپ.

## راه‌اندازی محلی

پیش‌نیازها: PHP 8.3+، Composer، Node.js، MySQL 8 و Redis.

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan serve
```

برای اجرای دستورات PHP در این محیط، از PHP 8.3 استفاده کنید:

```bash
/opt/homebrew/opt/php@8.3/bin/php artisan test
```

## کیفیت کد

```bash
./vendor/bin/pest
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
```

جزئیات تصمیم‌های فنی، مرحله‌های اجرا و قراردادهای پروژه در [مستندات پیاده‌سازی](docs/IMPLEMENTATION.md) قرار دارد.
