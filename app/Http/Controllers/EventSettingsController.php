<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEventDateRequest;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EventSettingsController extends Controller
{
    private const EventStartsAtKey = 'event_starts_at';

    private const LandingAgendaKey = 'landing_agenda';

    private const LandingBenefitsKey = 'landing_benefits';

    private const LandingContentKey = 'landing_content';

    private const LandingTeachersKey = 'landing_teachers';

    private const SeoKey = 'site_seo';

    private const PublicSettingsCacheKey = 'public-landing:settings';

    private const ShsmsTemplateKey = 'shsms_template';

    private const ShsmsOtpTemplateKey = 'shsms_otp_template';

    public function edit(): View
    {
        return view('panel.event-settings', [
            'eventStartsAt' => $this->eventStartsAt(),
            'benefits' => $this->benefits(),
            'agenda' => $this->agenda(),
            'teachers' => $this->teachers(),
            'seo' => $this->seo(),
            'landing' => $this->landing(),
            'shsmsTemplate' => (string) DB::table('settings')->where('key', self::ShsmsTemplateKey)->value('value'),
            'shsmsOtpTemplate' => (string) DB::table('settings')->where('key', self::ShsmsOtpTemplateKey)->value('value'),
        ]);
    }

    public function update(UpdateEventDateRequest $request): RedirectResponse
    {
        $updatesDate = $request->filled('event_date');
        $updatesLandingContent = $request->has('benefits') || $request->has('agenda') || $request->has('teachers') || $request->has('landing');
        $updatesSeoSettings = $request->has('seo');
        $updatesSmsSettings = $request->has('shsms_template') || $request->has('shsms_otp_template');

        if ($updatesDate) {
            $eventStartsAt = CarbonImmutable::createFromFormat(
                'Y-m-d H:i',
                $request->string('event_date')->toString().' 09:00',
                config('app.timezone'),
            );

            $this->saveSetting(self::EventStartsAtKey, $eventStartsAt->getTimestamp());
        }

        if ($request->has('benefits')) {
            $this->saveSetting(
                self::LandingBenefitsKey,
                json_encode($request->validated('benefits'), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            );
        }

        if ($request->has('agenda')) {
            $this->saveSetting(
                self::LandingAgendaKey,
                json_encode($request->validated('agenda'), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            );
        }

        if ($request->has('teachers')) {
            $this->saveTeachers($request->validated('teachers'));
        }

        if ($updatesSeoSettings) {
            $this->saveSeo($request->validated('seo'));
        }

        if ($request->has('landing')) {
            $this->saveSetting(
                self::LandingContentKey,
                json_encode($request->validated('landing'), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            );
        }

        if ($request->has('shsms_template')) {
            $this->saveSetting(self::ShsmsTemplateKey, $request->string('shsms_template')->trim()->toString());
        }

        if ($request->has('shsms_otp_template')) {
            $this->saveSetting(self::ShsmsOtpTemplateKey, $request->string('shsms_otp_template')->trim()->toString());
        }

        return to_route('panel.event-settings.edit')->with(
            'status',
            $updatesDate && ! $updatesLandingContent
                ? 'تاریخ برگزاری و شمارش معکوس به‌روزرسانی شد.'
                : ($updatesSeoSettings ? 'تنظیمات سئو به‌روزرسانی شد.' : ($updatesSmsSettings ? 'تنظیمات الگوی پیامک SHSMS به‌روزرسانی شد.' : 'محتوای صفحهٔ بلاگرها به‌روزرسانی شد.')),
        );
    }

    private function eventStartsAt(): CarbonImmutable
    {
        $timestamp = DB::table('settings')->where('key', self::EventStartsAtKey)->value('value');

        return $timestamp
            ? CarbonImmutable::createFromTimestamp((int) $timestamp, config('app.timezone'))
            : CarbonImmutable::create(2028, 9, 15, 9, 0, 0, config('app.timezone'));
    }

    /** @return array{eyebrow: string, title: string, items: array<int, array{title: string, description: string}>} */
    private function benefits(): array
    {
        $benefits = json_decode((string) DB::table('settings')->where('key', self::LandingBenefitsKey)->value('value'), true);

        return is_array($benefits) && count($benefits['items'] ?? []) === 4
            ? $benefits
            : $this->defaultBenefits();
    }

    /** @return array{eyebrow: string, title: string, items: array<int, array{time: string, title: string}>} */
    private function agenda(): array
    {
        $agenda = json_decode((string) DB::table('settings')->where('key', self::LandingAgendaKey)->value('value'), true);

        return is_array($agenda) && count($agenda['items'] ?? []) >= 1 && count($agenda['items'] ?? []) <= 20
            ? $agenda
            : $this->defaultAgenda();
    }

    /** @return array{eyebrow: string, title: string, description: string, items: array<int, array{name: string, subject: string, photo_path: ?string}>} */
    private function teachers(): array
    {
        $teachers = json_decode((string) DB::table('settings')->where('key', self::LandingTeachersKey)->value('value'), true);

        return is_array($teachers) && count($teachers['items'] ?? []) >= 1 && count($teachers['items'] ?? []) <= 20
            ? $teachers
            : self::defaultTeachers();
    }

    /** @return array{site_title: string, description: string, share_title: string, share_description: string, image_path: ?string} */
    private function seo(): array
    {
        $seo = json_decode((string) DB::table('settings')->where('key', self::SeoKey)->value('value'), true);

        return is_array($seo) ? array_replace(self::defaultSeo(), $seo) : self::defaultSeo();
    }

    /** @return array{site_title: string, description: string, share_title: string, share_description: string, image_path: null} */
    public static function defaultSeo(): array
    {
        return [
            'site_title' => 'گردهمایی کنکور | گروه آموزشی استاد محسن گناوه‌ای',
            'description' => 'سمینار موفقیت در کنکور و امتحانات نهایی برای داوطلبان ریاضی و تجربی.',
            'share_title' => 'گردهمایی بزرگ داوطلبان کنکور',
            'share_description' => 'ثبت‌نام رایگان در سمینار موفقیت در کنکور و امتحانات نهایی.',
            'image_path' => null,
        ];
    }

    /** @param array{site_title: string, description: string, share_title: string, share_description: string, image_path?: string, image?: UploadedFile} $seo */
    private function saveSeo(array $seo): void
    {
        $currentPath = $this->seo()['image_path'];
        $imagePath = $currentPath;

        if (($seo['image'] ?? null) instanceof UploadedFile) {
            $imagePath = $seo['image']->store('seo', 'public');
        }

        if ($currentPath && $currentPath !== $imagePath && Storage::disk('public')->exists($currentPath)) {
            Storage::disk('public')->delete($currentPath);
        }

        $seo['image_path'] = $imagePath;
        unset($seo['image']);
        $this->saveSetting(self::SeoKey, json_encode($seo, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    /** @return array{hero: array<string, string>, audience: array{eyebrow: string, title: string, items: array<int, array{title: string, description: string}>}, faq: array{eyebrow: string, title: string, items: array<int, array{question: string, answer: string}>}, reservation: array{title: string, description: string, cta_label: string}} */
    private function landing(): array
    {
        $landing = json_decode((string) DB::table('settings')->where('key', self::LandingContentKey)->value('value'), true);

        return is_array($landing) ? array_replace(self::defaultLanding(), $landing) : self::defaultLanding();
    }

    /** @return array{hero: array<string, string>, audience: array{eyebrow: string, title: string, items: array<int, array{title: string, description: string}>}, faq: array{eyebrow: string, title: string, items: array<int, array{question: string, answer: string}>}, reservation: array{title: string, description: string, cta_label: string}} */
    public static function defaultLanding(): array
    {
        return [
            'hero' => [
                'brand_title' => 'گروه آموزشی استاد محسن گناوه‌ای',
                'brand_subtitle' => 'GENAVEHEI EDUCATIONAL GROUP',
                'cta_label' => 'ثبت‌نام رایگان',
                'eyebrow' => 'سمینار موفقیت در کنکور و امتحانات نهایی',
                'title' => 'بزرگ‌ترین گردهمایی داوطلبین کنکور رشته‌های ریاضی و تجربی؛ پایه‌های دهم، یازدهم، دوازدهم و فارغ‌التحصیلان سال‌های گذشته در کشور',
                'description' => 'یک روز کامل، روی صحنه‌ای که مسیر کنکور و امتحانات نهایی‌ات را عوض می‌کند: برنامه‌ریزی واقعی، تکنیک‌های تست‌زنی، مدیریت استرس و نقشه راه رشته‌های ریاضی و تجربی.',
                'date_label' => 'تاریخ برگزاری',
                'capacity_label' => 'ظرفیت سالن',
                'capacity_value' => 'محدود',
                'cost_label' => 'هزینه',
                'cost_value' => 'حضور رایگان',
            ],
            'audience' => [
                'eyebrow' => 'این سمینار به درد چه کسانی می‌خورد؟',
                'title' => 'اگر یکی از این‌ها هستی، جای تو همین سالن است',
                'items' => [
                    ['title' => 'دهمی‌ها و یازدهمی‌ها', 'description' => 'می‌خواهی از همین حالا پایه را محکم ببندی و سال کنکور را از صفر شروع نکنی.'],
                    ['title' => 'دوازدهمی‌ها', 'description' => 'هم‌زمان امتحان نهایی و کنکور داری و به یک برنامه دقیق و واقع‌بینانه نیاز داری.'],
                    ['title' => 'فارغ‌التحصیل‌ها', 'description' => 'یک سال پشت کنکور بوده‌ای و این بار می‌خواهی روش مطالعه‌ات را عوض کنی، نه فقط ساعت مطالعه را.'],
                ],
            ],
            'faq' => [
                'eyebrow' => 'سوالات متداول',
                'title' => 'هر چیزی که قبل از ثبت‌نام می‌پرسند',
                'items' => [
                    ['question' => 'شرکت در این گردهمایی هزینه دارد؟', 'answer' => 'نه؛ حضور رایگان است، اما ظرفیت سالن محدود است و ورود فقط با بلیط و کد اختصاصی که پس از ثبت‌نام صادر می‌شود انجام می‌شود.'],
                    ['question' => 'بلیط و کد ورودم را از کجا می‌گیرم؟', 'answer' => 'بعد از تأیید شماره موبایل و تکمیل فرم، بلیط با نام خودت و یک کد اختصاصی صادر می‌شود.'],
                    ['question' => 'اگر شهر دیگری زندگی می‌کنم چه؟', 'answer' => 'ثبت‌نام برای همه شهرها باز است و جزئیات محل برگزاری پیش از مراسم پیامک می‌شود.'],
                ],
            ],
            'reservation' => [
                'title' => 'جای خودت را رزرو کن',
                'description' => 'ثبت‌نام با شماره موبایل، کمتر از یک دقیقه.',
                'cta_label' => 'شروع ثبت‌نام',
            ],
        ];
    }

    /** @return array{eyebrow: string, title: string, description: string, items: array<int, array{name: string, subject: string, photo_path: null}>} */
    public static function defaultTeachers(): array
    {
        return [
            'eyebrow' => 'اساتید سمینار',
            'title' => 'اساتید دروس تخصصی و عمومی آکادمی سینوهه',
            'description' => 'همان کسانی که سال‌ها رتبه‌های برتر ریاضی و تجربی را ساخته‌اند، این‌بار روی یک صحنه.',
            'items' => [
                ['name' => 'استاد خسرو فیض‌آبادی', 'subject' => 'شیمی', 'photo_path' => null],
                ['name' => 'دکتر احسان زارعی', 'subject' => 'زیست', 'photo_path' => null],
                ['name' => 'استاد سیاوش بلغانی', 'subject' => 'ریاضی', 'photo_path' => null],
                ['name' => 'استاد نوید شاهی', 'subject' => 'فیزیک', 'photo_path' => null],
                ['name' => 'استاد حسین خرابی', 'subject' => 'هندسه و گسسته', 'photo_path' => null],
                ['name' => 'استاد علی عطری', 'subject' => 'ادبیات', 'photo_path' => null],
                ['name' => 'استاد فرشید مفتون', 'subject' => 'زبان', 'photo_path' => null],
                ['name' => 'استاد جواد پوران', 'subject' => 'عربی', 'photo_path' => null],
                ['name' => 'استاد حبیب صابری', 'subject' => 'دین و زندگی', 'photo_path' => null],
                ['name' => 'استاد حسن علیرضانژاد', 'subject' => 'مشاور تحصیلی', 'photo_path' => null],
            ],
        ];
    }

    /** @param array{eyebrow: string, title: string, description: string, items: array<int, array{name: string, subject: string, photo_path?: string, photo?: UploadedFile}>} $teachers */
    private function saveTeachers(array $teachers): void
    {
        $existingPaths = collect($this->teachers()['items'])
            ->pluck('photo_path')
            ->filter(fn (mixed $path): bool => $this->isManagedTeacherPhoto($path))
            ->values()
            ->all();
        $items = [];

        foreach ($teachers['items'] as $teacher) {
            $photoPath = in_array($teacher['photo_path'] ?? null, $existingPaths, true) ? $teacher['photo_path'] : null;

            if (($teacher['photo'] ?? null) instanceof UploadedFile) {
                $photoPath = $teacher['photo']->store('teachers', 'public');
            }

            $items[] = [
                'name' => $teacher['name'],
                'subject' => $teacher['subject'],
                'photo_path' => $photoPath,
            ];
        }

        $currentPaths = collect($items)->pluck('photo_path')->filter()->unique()->all();

        foreach (array_diff($existingPaths, $currentPaths) as $path) {
            Storage::disk('public')->delete($path);
        }

        $teachers['items'] = $items;
        $this->saveSetting(self::LandingTeachersKey, json_encode($teachers, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    private function isManagedTeacherPhoto(mixed $path): bool
    {
        return is_string($path) && str_starts_with($path, 'teachers/');
    }

    private function saveSetting(string $key, int|string $value): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now(), 'created_at' => now()],
        );

        Cache::forget(self::PublicSettingsCacheKey);
    }

    /** @return array{eyebrow: string, title: string, items: array<int, array{title: string, description: string}>} */
    private function defaultBenefits(): array
    {
        return [
            'eyebrow' => 'چرا باید در این گردهمایی باشی؟',
            'title' => 'چهار چیزی که با خودت از سالن بیرون می‌بری',
            'items' => [
                ['title' => 'برنامه‌ریزی واقعی', 'description' => 'یک برنامه هفتگی که با مدرسه، آزمون و زندگی واقعی‌ات جور دربیاید — نه جدول‌های آرمانی.'],
                ['title' => 'تکنیک تست‌زنی', 'description' => 'روش‌های مدیریت زمان و انتخاب تست در کنکور ریاضی و تجربی، با حل زنده روی صحنه.'],
                ['title' => 'امتحانات نهایی', 'description' => 'تأثیر معدل، نقشه مطالعه نهایی‌ها و اینکه کجا باید بین نهایی و کنکور تعادل بسازی.'],
                ['title' => 'انگیزه‌ی ماندگار', 'description' => 'هم‌مسیر شدن با هزاران داوطلب دیگر؛ همان چیزی که تنهایی مطالعه‌کردن از تو گرفته است.'],
            ],
        ];
    }

    /** @return array{eyebrow: string, title: string, items: array<int, array{time: string, title: string}>} */
    private function defaultAgenda(): array
    {
        return [
            'eyebrow' => 'سرفصل‌های روز سمینار',
            'title' => 'از صبح تا عصر، بدون حرف تکراری',
            'items' => [
                ['time' => '۰۹:۰۰', 'title' => 'افتتاحیه و نقشه راه سال تحصیلی'],
                ['time' => '۱۰:۳۰', 'title' => 'تکنیک‌های مطالعه و تست‌زنی — ریاضی و تجربی'],
                ['time' => '۱۳:۰۰', 'title' => 'مدیریت استرس، خواب و تمرکز'],
                ['time' => '۱۵:۰۰', 'title' => 'پرسش و پاسخ زنده با استاد گناوه‌ای'],
            ],
        ];
    }
}
