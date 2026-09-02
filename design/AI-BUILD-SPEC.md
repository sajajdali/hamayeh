# AI Build Spec — سامانه ثبت‌نام ارجاعی گردهمایی کنکور

> این فایل را کامل به دستیار کدنویس (Claude Code / Cursor / Copilot Workspace) بده.
> دستور شروع: **«این فایل قرارداد کار توست. فاز ۰ را انجام بده، خروجی را گزارش کن، منتظر تأیید بمان، بعد فاز ۱. هیچ فازی را جلوتر نرو.»**

---

## 0) قوانین کار برای دستیار (اجباری)

1. **فاز به فاز پیش برو.** پایان هر فاز: فهرست فایل‌های ساخته/تغییر‌یافته + دستور تست + `git commit` با پیام مشخص. بعد توقف کن.
2. **هیچ چیزی خارج از این سند اختراع نکن.** اگر ابهامی بود، به‌جای حدس زدن سؤال بپرس.
3. **هر فاز باید تست داشته باشد.** فاز بدون تست سبز، تمام‌شده نیست.
4. **زبان کد انگلیسی، رابط کاربری فارسی و RTL.** نام جدول/ستون/کلاس انگلیسی، متن‌های UI در `lang/fa/*.php`.
5. **بدون داده ساختگی در محیط واقعی.** داده نمونه فقط در Seeder با فلگ `--demo`.
6. **امنیت پیش‌فرض:** هیچ کوئری بدون scope دسترسی، هیچ فایل بلیطی بدون لینک امضاشده، هیچ ورودی بدون FormRequest.
7. **Timezone پروژه `Asia/Tehran`** و تاریخ‌های نمایشی شمسی (`morilog/jalali`).

### قالب پیام کامیت
```
feat(phase-2): landing route, otp flow, registration form
```

---

## 1) خلاصه محصول

سامانه ثبت‌نام برای یک رویداد آموزشی. هر بلاگر لینک اختصاصی دارد؛ هر کسی از آن لینک ثبت‌نام کند، کد یکتای زیرمجموعه همان بلاگر می‌گیرد (`a10-1`, `a10-2`, ...). کاربر بلیط دیجیتال دریافت می‌کند؛ تیم پشتیبانی تماس می‌گیرد و ثبت‌نام را نهایی می‌کند.

**بازیگران:** مدیر کل، مدیر میانی، بلاگر، دانش‌آموز (کاربر عمومی).

**جریان اصلی:**
```
لینک بلاگر → شماره موبایل → کد ۴ رقمی → فرم ثبت‌نام → صدور کد یکتا + بلیط
        → وضعیت «در انتظار تماس» → تماس مدیر → «تأیید نهایی»
```

---

## 2) پشته فنی (تثبیت‌شده — تغییر نده)

| لایه | انتخاب |
|---|---|
| فریم‌ورک | **Laravel 13** (آخرین نسخه) |
| UI پنل | **Livewire 4** (single-file components) + Alpine.js |
| UI لندینگ | Blade (SSR) + Alpine؛ فقط OTP و فرم = Livewire |
| استایل | Tailwind CSS 4 (پیکربندی CSS-first) + RTL |
| دیتابیس | MySQL 8 |
| صف و کش | Redis + Horizon |
| تست | Pest 3 |
| کیفیت کد | Pint + Larastan level 6 |

**پکیج‌ها:** `livewire/livewire:^4.2`, `spatie/laravel-permission` (اختیاری — یا enum ساده), `spatie/browsershot`, `simplesoftwareio/simple-qrcode`, `maatwebsite/excel`, `spatie/laravel-backup`, `morilog/jalali`, `pestphp/pest`.

---

## 3) مدل داده کامل

### users (مدیران)
| ستون | نوع | توضیح |
|---|---|---|
| id | bigint | |
| name | string | |
| username | string unique | ورود با username نه ایمیل |
| password | string | hashed |
| role | enum(`super`,`mid`) | |
| is_active | boolean default true | |
| timestamps, softDeletes | | |

### bloggers
| ستون | نوع | توضیح |
|---|---|---|
| id | bigint | |
| name | string | |
| code | string(10) unique | پیشوند کد بلیط، مثل `a10` |
| slug | string(24) unique | آدرس کوتاه، مثل `alisaburi` |
| phone | string(11) nullable | |
| avatar_path | string nullable | |
| password | string nullable | ورود بلاگر به پنل |
| is_active | boolean default true | |
| seq | unsignedInteger default 0 | شمارنده ثبت‌نام |
| timestamps, softDeletes | | |

قواعد: `code` فقط `[a-z0-9]{2,10}` — `slug` فقط `[a-z0-9_-]{2,24}` و نباید در فهرست رزرو باشد.

### registrations
| ستون | نوع |
|---|---|
| id | bigint |
| ticket_code | string(24) unique |
| blogger_id | foreignId nullable, nullOnDelete |
| seq | unsignedInteger |
| full_name | string |
| phone | string(11), index |
| grade | enum(`10`,`11`,`12`,`alumni`) |
| field | enum(`math`,`science`) |
| school | string |
| gpa | decimal(4,2) nullable |
| study_city | string |
| father_job | string nullable |
| province | string |
| city | string |
| area | string nullable |
| guardian_name | string |
| guardian_phone | string(11) |
| status | enum(`pending`,`calling`,`approved`,`canceled`) default pending |
| ticket_path | string nullable |
| timestamps, softDeletes |

ایندکس‌های ترکیبی: `(blogger_id, created_at)`, `(status, created_at)`.

### activity_logs
`id, registration_id (fk cascade), actor_type, actor_id (morphs, nullable), type enum(call,sms,status,system), body text, meta json nullable, timestamps` — ایندکس `(registration_id, created_at)`.

### sms_templates
`id, name, body text, is_active bool, timestamps, softDeletes`

### sms_messages
`id, registration_id fk, sms_template_id fk nullable, to string, body text, provider_message_id nullable, status enum(queued,sent,delivered,failed), error nullable, sent_at nullable, timestamps`

### otp_codes
`id, phone string index, code_hash string, expires_at datetime, attempts tinyint default 0, consumed_at nullable, ip string nullable, timestamps`

### Enumها (PHP 8.1 backed enums در `app/Enums`)
`UserRole`, `RegistrationStatus`, `Grade`, `StudyField`, `ActivityType`, `SmsStatus` — هرکدام با متد `label(): string` فارسی.

---

## 4) قواعد کسب‌وکار (Business Rules)

**BR-1 — صدور کد بلیط اتمیک.** کد = `{blogger.code}-{seq}`. حتماً داخل تراکنش با `lockForUpdate`:

```php
return DB::transaction(function () use ($bloggerId, $data) {
    $b = Blogger::whereKey($bloggerId)->lockForUpdate()->firstOrFail();
    $b->increment('seq');

    return Registration::create([
        ...$data,
        'blogger_id'  => $b->id,
        'seq'         => $b->seq,
        'ticket_code' => "{$b->code}-{$b->seq}",
    ]);
});
```
اگر ثبت‌نام بدون بلاگر بود، پیشوند `x` و شمارنده در جدول تنظیمات.

**BR-2 — OTP.** طول ۴ رقم، انقضا ۱۲۰ ثانیه، حداکثر ۵ تلاش، پس از موفقیت `consumed_at`. سقف ارسال: ۳ بار در ۱۰ دقیقه به‌ازای شماره و ۱۰ بار در ۱۰ دقیقه به‌ازای IP.

**BR-3 — انتساب معرف.** بلاگر از session (کلید `ref_blogger_id`) خوانده می‌شود؛ عمر کوکی امضاشده ۳۰ روز؛ **اولین** لینک برنده است (last-touch نه، first-touch).

**BR-4 — بلاگر غیرفعال.** لینکش ۴۰۴ می‌دهد، ورودش به پنل رد می‌شود، ثبت‌نام‌های قبلی‌اش دست‌نخورده می‌مانند.

**BR-5 — حذف بلاگر.** فقط مدیر کل، با تأیید دومرحله‌ای و هشدار صریح. `SoftDeletes`؛ ثبت‌نام‌ها حذف نمی‌شوند بلکه `blogger_id = null` و در گزارش ثبت می‌شود.

**BR-6 — گذار وضعیت.** فقط این مسیرها مجازند:
```
pending → calling | approved | canceled
calling → approved | canceled
approved → canceled
canceled → pending
```
هر گذار یک `activity_log` با actor می‌سازد.

**BR-7 — نتیجه تماس ⇒ وضعیت.**
| نتیجه | وضعیت |
|---|---|
| پاسخ داد — تأیید کرد | approved |
| منصرف شد | canceled |
| بقیه موارد | calling |

**BR-8 — دسترسی داده.** بلاگر فقط `registrations` با `blogger_id = خودش`. اجرا در scope، نه در ویو.

---

## 5) نقشه مسیرها

```php
// routes/web.php  (Livewire 4: از Route::livewire برای کامپوننت‌های full-page)
Route::livewire('/', LandingHome::class)->name('home');

Route::prefix('panel')->group(function () {
    Route::livewire('login', Login::class)->name('login');
    Route::post('logout', LogoutController::class)->name('logout');

    Route::middleware('auth.panel')->group(function () {
        Route::livewire('/',         Panel\Registrations::class)->name('panel.registrations');
        Route::livewire('r/{registration}', Panel\RegistrationShow::class)->name('panel.registration');
        Route::livewire('bloggers',  Panel\Bloggers::class)->middleware('staff')->name('panel.bloggers');
        Route::livewire('bloggers/{blogger}', Panel\BloggerShow::class)->middleware('staff')->name('panel.blogger');
        Route::livewire('sms',       Panel\SmsTemplates::class)->middleware('staff')->name('panel.sms');
        Route::livewire('admins',    Panel\Admins::class)->middleware('super')->name('panel.admins');
        Route::livewire('activity',  Panel\ActivityLog::class)->middleware('staff')->name('panel.activity');
    });
});

Route::get('ticket/{registration:ticket_code}', TicketController::class)
    ->middleware('signed')->name('ticket.show');

// همیشه آخرین روت فایل:
Route::livewire('/{blogger:slug}', LandingBlogger::class)->name('landing');
```

> **نکات Livewire 4 که باید رعایت شود:**
> - کامپوننت‌ها را به‌صورت **single-file component** بنویس (منطق و مارک‌آپ در یک فایل، همان سینتکس Volt کلاس‌محور).
> - برای کامپوننت‌های full-page حتماً از `Route::livewire()` استفاده کن (روش ارجح و برای SFC الزامی).
> - `wire:model` در v4 فقط رویدادهای خودِ المان را می‌گیرد؛ اگر روی کانتینر استفاده کردی مودیفایر `.deep` لازم است.
> - مودیفایرهای `.blur/.change` حالا زمان سینک state را کنترل می‌کنند؛ برای رفتار قبلی `wire:model.live.blur`.
> - `<livewire>` تگ‌ها باید بسته شوند.

**Slugهای رزرو:** `panel, login, logout, ticket, api, admin, storage, assets, livewire, up`.

---

## 6) فازهای اجرا

هر فاز: **کار → تست → کامیت → توقف**.

### فاز ۰ — اسکلت
- نصب Laravel 13، Livewire 4 (`composer require livewire/livewire:^4.2`)، Tailwind 4 + RTL، Pest، Pint، Larastan.
- `config/app.php`: `timezone = Asia/Tehran`, `locale = fa`.
- لایه‌بندی پوشه‌ها: `app/{Actions,Enums,Services,Livewire/Panel,Policies,Support}`.
- `.env.example` کامل با کلیدهای پیامک.

**تست:** `php artisan test` سبز، `./vendor/bin/pint --test` تمیز.

---

### فاز ۱ — دامنه و داده
- تمام مهاجرت‌ها و مدل‌های بخش ۳ با `casts`, روابط، و `Enum`ها.
- Factories برای `Blogger`, `Registration`, `User`.
- `DatabaseSeeder` + `DemoSeeder` (فقط با `--class=DemoSeeder`): ۱ مدیر کل، ۱ مدیر میانی، ۳ بلاگر، ۳۰ ثبت‌نام با تاریخ‌های پراکنده.
- Action: `App\Actions\IssueRegistration` طبق BR-1.
- Scope: `Registration::visibleTo(Authenticatable $actor)`.

**تست:**
- `IssueRegistration` با ۵۰ اجرای هم‌زمان کد تکراری نسازد (تست تراکنش/یکتایی).
- scope برای بلاگر فقط رکوردهای خودش را بدهد.

---

### فاز ۲ — لندینگ و ثبت‌نام
- `LandingBlogger` (Blade SSR): تیتر رویداد، تاریخ، شمارش معکوس، بخش «به درد چه کسانی می‌خورد»، سؤالات متداول، CTA.
- **موبایل-اول**؛ فرم شماره موبایل باید در همان بخش اول بالای صفحه باشد.
- کامپوننت `OtpBox` (Livewire): مرحله شماره → **مدال** کد ۴ رقمی با تایمر ارسال مجدد ۱۲۰ ثانیه و «ویرایش شماره».
- کامپوننت `RegistrationForm` (Livewire) با فیلدها:
  `full_name`, انتخاب پایه به شکل جمله‌ی «اول مهر ۱۴۰۵ به پایه [دهم/یازدهم/دوازدهم] خواهم رفت», `field`, `school`, `gpa`, `study_city`, `father_job`, `province`, `city`, `area` (placeholder: سعادت‌آباد), `guardian_name`, `guardian_phone`.
- ولیدیشن: موبایل `^09\d{9}$`، معدل بین ۰ تا ۲۰، همه فیلدها جز `father_job`/`area`/`gpa` الزامی.
- در پایان `IssueRegistration` + هدایت به صفحه بلیط + پیام «در حال صدور نهایی و همکاران ما تماس خواهند گرفت».

**تست:** feature test کل مسیر لینک بلاگر تا صدور کد؛ تست منقضی‌شدن OTP؛ تست rate limit.

---

### فاز ۳ — بلیط
- Blade view بلیط با: تیتر کامل رویداد، لوگو، عکس استاد، نام دارنده بلیط، پایه، رشته، نام همراه، شماره همراه، مدرسه، شهر، معدل، تاریخ و ساعت، **کد ورود درشت**، جمله «کد ورود را تا پایان مراسم نزد خود نگه دارید»، QR، و نوار وضعیت.
- طراحی باید مثل بلیط کنسرت باشد: خط پرفراژ با ناچ گرد، بارکد، ناحیه ته‌بلیط.
- `GenerateTicketImage` Job → PNG (Browsershot) + PDF؛ ذخیره در `storage/app/tickets`.
- دانلود با `URL::temporarySignedRoute` (اعتبار ۲۴ ساعت).

**تست:** فایل بلیط ساخته می‌شود؛ لینک بدون امضا ۴۰۳ می‌دهد.

---

### فاز ۴ — احراز هویت و پنل پایه
- دو گارد: `web` (users) و `blogger` (bloggers). یک صفحه ورود مشترک: اول users، بعد bloggers.
- Middlewareها: `auth.panel`, `staff`, `super`.
- لایه‌ی پنل: هدر با آواتار و نام و نقش؛ **دسکتاپ: تب‌های افقی؛ زیر ۹۰۰px: همبرگر منو**.
- `Panel\Registrations`: جدول با ستون‌های نام، کد بلیط، بلاگر، پایه، رشته، شهر، وضعیت، تعداد تماس، تاریخ.
- فیلترها با `#[Url]`: جست‌وجو، بلاگر، وضعیت، پایه، رشته، بازه زمانی (امروز/دیروز/۷ روز).
- صفحه‌بندی سرور-ساید ۱۵ ردیف.
- کارت‌های آمار: کل، امروز، دیروز، در انتظار تماس، تأیید نهایی.

**تست:** بلاگر لاگین‌کرده فقط رکوردهای خودش را ببیند؛ مدیر میانی به `panel.admins` دسترسی نداشته باشد (۴۰۳).

---

### فاز ۵ — پرونده ثبت‌نام
- `Panel\RegistrationShow`: همه فیلدها + پیش‌نمایش بلیط + دکمه دانلود.
- بخش **ثبت نتیجه تماس**: انتخاب نتیجه + یادداشت → `activity_log` + به‌روزرسانی وضعیت طبق BR-7.
- دکمه‌های **تصمیم مدیر**: تأیید نهایی / در حال پیگیری / منصرف (فقط staff).
- **تاریخچه فعالیت** همان پرونده با نوع رویداد، نام عامل، متن و زمان.

**تست:** ثبت تماس «تأیید کرد» وضعیت را approved کند و یک لاگ بسازد.

---

### فاز ۶ — بلاگرها
- `Panel\Bloggers`: فرم تعریف (نام، کد، آدرس کوتاه، موبایل، رمز ورود) + فهرست کارت‌ها با آمار کل/امروز/دیروز/تأیید + کپی لینک + صفحه‌بندی ۹ تایی.
- `Panel\BloggerShow`: آمار، اطلاعات، آپلود عکس، فعال/غیرفعال، حذف با تأیید و هشدار، فهرست ثبت‌نام‌هایش با صفحه‌بندی.
- آواتار: آپلود، resize به ۴۰۰px، ذخیره در `storage/app/public/avatars`.
- **پنل بلاگر**: همان صفحه ثبت‌نام‌ها ولی محدود به خودش، بدون تماس/پیامک/تصمیم؛ عکس و خروج در هدر.

**تست:** لینک بلاگر غیرفعال ۴۰۴؛ ورود بلاگر غیرفعال رد شود.

---

### فاز ۷ — پیامک
- `SmsService` interface + درایور `Kavenegar` (+ درایور `Log` برای توسعه) در `config/sms.php`.
- `Panel\SmsTemplates`: افزودن/ویرایش/حذف قالب. متغیرها: `{نام} {کد} {تاریخ} {همراه} {پایه} {رشته}`.
- سه قالب پیش‌فرض در Seeder: **پیامک یادآوری**، **پیامک بلیط**، **پیامک آدرس**.
- در پرونده هر ثبت‌نام: انتخاب قالب، پیش‌نمایش متن پرشده، ارسال به دانش‌آموز یا همراه.
- ارسال در Job با retry؛ هر ارسال → `sms_messages` + `activity_log`.
- Webhook وضعیت تحویل: `POST /webhooks/sms` با تأیید امضا.
- ارسال گروهی: انتخاب چندتایی در جدول → ارسال قالب به همه (با تأیید و سقف).

**تست:** رندر قالب متغیرها را درست جایگزین کند؛ ارسال یک `sms_messages` و یک لاگ بسازد.

---

### فاز ۸ — مدیران و گزارش فعالیت
- `Panel\Admins` (فقط مدیر کل): افزودن مدیر با نقش کل/میانی، غیرفعال‌سازی، حذف (نه خودش).
- `Panel\ActivityLog`: کارت‌های آمار (تماس، پیامک، تغییر وضعیت، فعالیت امروز)، فیلتر جست‌وجو/نوع/عامل/بازه، صفحه‌بندی، خروجی CSV، کلیک روی رویداد → پرونده مربوطه.
- خروجی Excel ثبت‌نام‌ها با `maatwebsite/excel` در صف.

**تست:** مدیر میانی نتواند مدیر بسازد؛ خروجی CSV هدر فارسی و BOM داشته باشد.

---

### فاز ۹ — سخت‌سازی و استقرار
- Horizon + Supervisor؛ همه Jobها روی صف `default` و `sms`.
- Response cache لندینگ با invalidate هنگام تغییر بلاگر.
- `spatie/laravel-backup` روزانه به فضای ابری جدا.
- Sentry برای خطاها؛ هشدار روی نرخ خطای پیامک.
- Security headers، HTTPS اجباری، `APP_DEBUG=false`.
- تست بار ساده (k6 یا ab) روی `/{slug}` و مسیر OTP.

**تست:** `php artisan test` کامل سبز؛ Larastan level 6 بدون خطا.

---

## 7) محتوای ثابت رابط کاربری

- **تیتر اصلی:** «بزرگ‌ترین گردهمایی داوطلبین کنکور رشته‌های ریاضی و تجربی؛ پایه‌های دهم، یازدهم، دوازدهم و فارغ‌التحصیلان سال‌های گذشته در کشور»
- **زیرعنوان:** «سمینار موفقیت در کنکور و امتحانات نهایی»
- **تاریخ:** ۲۵ شهریور ۱۴۰۷
- **CTA اصلی:** «برای ثبت‌نام شماره موبایل خود را وارد کنید»
- **پیام پایان ثبت‌نام:** «در حال صدور نهایی است و همکاران ما برای نهایی کردن با شما تماس خواهند گرفت.»
- **هشدار بلیط:** «کد ورود را همیشه تا پایان مراسم نزد خود نگه دارید.»

همه این متن‌ها در `lang/fa/event.php` باشند، نه هاردکد در Blade.

---

## 8) استانداردهای کد

- Actionها تک‌مسئولیتی با متد `handle()`؛ منطق کسب‌وکار در کامپوننت Livewire نوشته نشود.
- کامپوننت Livewire فقط state و فراخوانی Action؛ در v4 هر کامپوننت یک single-file component.
- هیچ کوئری در حلقه (N+1 ممنوع) — از `with()` استفاده کن؛ تست با Laravel Debugbar.
- همه‌ی ورودی‌ها از FormRequest یا `rules()` در Livewire.
- ارقام فارسی فقط در لایه نمایش (یک `Str::macro` یا Blade directive)، نه در دیتابیس.
- شماره موبایل قبل از ذخیره نرمال‌سازی شود (تبدیل ارقام فارسی/عربی به لاتین، حذف فاصله و `+98`).

---

## 9) چک‌لیست تحویل نهایی

- [ ] لینک هر بلاگر کار می‌کند و معرف درست ثبت می‌شود
- [ ] کد بلیط یکتا و ترتیبی است، حتی زیر بار هم‌زمان
- [ ] OTP با محدودیت نرخ و انقضا کار می‌کند
- [ ] بلیط PNG و PDF با QR صادر و امن دانلود می‌شود
- [ ] پنل: فیلتر، صفحه‌بندی، پرونده، تماس، وضعیت، خروجی اکسل
- [ ] پیامک: قالب‌ها، ارسال تکی و گروهی، لاگ تحویل
- [ ] نقش‌ها: مدیر کل، مدیر میانی، بلاگر — با تست دسترسی
- [ ] گزارش فعالیت کامل با فیلتر و خروجی
- [ ] موبایل-اول در همه صفحات؛ همبرگر منو زیر ۹۰۰px
- [ ] بکاپ روزانه فعال و یک‌بار بازیابی آزمایشی انجام شده
