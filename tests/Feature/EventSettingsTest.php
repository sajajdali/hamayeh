<?php

use App\Enums\UserRole;
use App\Http\Controllers\EventSettingsController;
use App\Models\Blogger;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

it('renders the event settings page for a super manager', function () {
    $manager = User::factory()->create(['role' => UserRole::Super]);

    $this->actingAs($manager)
        ->get(route('panel.event-settings.edit'))
        ->assertSee('تاریخ برگزاری گردهمایی')
        ->assertSee('انتخاب تاریخ شمسی')
        ->assertSee('id="panel-notifications"', false);
});

it('stores the selected event date for a super manager', function () {
    $manager = User::factory()->create(['role' => UserRole::Super]);
    Cache::put('public-landing:settings', ['event_starts_at' => '1'], now()->addHour());

    $this->actingAs($manager)
        ->put(route('panel.event-settings.update'), ['event_date' => '2028-09-15'])
        ->assertRedirect(route('panel.event-settings.edit'))
        ->assertSessionHas('status', 'تاریخ برگزاری و شمارش معکوس به‌روزرسانی شد.');

    $this->assertDatabaseHas('settings', [
        'key' => 'event_starts_at',
        'value' => CarbonImmutable::create(2028, 9, 15, 9, 0, 0, 'Asia/Tehran')->getTimestamp(),
    ]);
    expect(Cache::has('public-landing:settings'))->toBeFalse();
});

it('stores SHSMS template names as text settings', function () {
    $manager = User::factory()->create(['role' => UserRole::Super]);

    $this->actingAs($manager)
        ->put(route('panel.event-settings.update'), [
            'shsms_template' => 'reminder',
            'shsms_otp_template' => 'login_code',
        ])
        ->assertRedirect(route('panel.event-settings.edit'))
        ->assertSessionHas('status', 'تنظیمات الگوی پیامک SHSMS به‌روزرسانی شد.');

    $this->assertDatabaseHas('settings', ['key' => 'shsms_template', 'value' => 'reminder']);
    $this->assertDatabaseHas('settings', ['key' => 'shsms_otp_template', 'value' => 'login_code']);
});

it('forbids a mid-level manager from changing the event date', function () {
    $manager = User::factory()->create(['role' => UserRole::Mid]);

    $this->actingAs($manager)
        ->put(route('panel.event-settings.update'), ['event_date' => '2028-09-15'])
        ->assertForbidden();
});

it('uses the configured event timestamp in the landing countdown', function () {
    $blogger = Blogger::factory()->create();
    $timestamp = CarbonImmutable::create(2028, 9, 15, 9, 0, 0, 'Asia/Tehran')->getTimestamp();
    DB::table('settings')->insert([
        'key' => 'event_starts_at',
        'value' => $timestamp,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->get(route('landing', $blogger))
        ->assertOk()
        ->assertSee('const target = '.($timestamp * 1000).';', false);
});

it('loads province and city choices from the cached configuration file', function () {
    $blogger = Blogger::factory()->create();

    $this->get(route('landing', $blogger))
        ->assertOk()
        ->assertSee('const provinceCities = JSON.parse', false)
        ->assertSee(base64_encode(json_encode(config('province_cities'), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)), false);
});

it('updates landing benefits and seminar agenda from event settings', function () {
    $manager = User::factory()->create(['role' => UserRole::Super]);
    $blogger = Blogger::factory()->create();
    $agendaItems = [
        ['time' => '09:00', 'title' => 'آغاز رویداد'],
        ['time' => '10:30', 'title' => 'روش‌های مطالعه'],
        ['time' => '13:00', 'title' => 'مدیریت تمرکز'],
        ['time' => '15:00', 'title' => 'پرسش و پاسخ'],
        ['time' => '16:30', 'title' => 'جمع‌بندی پایانی'],
    ];

    $this->actingAs($manager)
        ->put(route('panel.event-settings.update'), [
            'event_date' => '2028-09-15',
            'benefits' => [
                'eyebrow' => 'دلیل حضور',
                'title' => 'چهار دستاورد مهم',
                'items' => [
                    ['title' => 'برنامه شخصی', 'description' => 'برنامه‌ای متناسب با مسیر شما.'],
                    ['title' => 'تست هوشمند', 'description' => 'روش حل تست‌های سخت.'],
                    ['title' => 'نهایی قوی', 'description' => 'تعادل بین نهایی و کنکور.'],
                    ['title' => 'انگیزه پایدار', 'description' => 'همراهی در مسیر موفقیت.'],
                ],
            ],
            'agenda' => [
                'eyebrow' => 'برنامه روز',
                'title' => 'سرفصل‌های کاربردی',
                'items' => $agendaItems,
            ],
        ])
        ->assertRedirect(route('panel.event-settings.edit'));

    $this->assertDatabaseHas('settings', ['key' => 'landing_benefits']);
    $this->assertDatabaseHas('settings', ['key' => 'landing_agenda']);

    $this->get(route('landing', $blogger))
        ->assertOk()
        ->assertSee('چهار دستاورد مهم')
        ->assertSee('انگیزه پایدار')
        ->assertSee('سرفصل‌های کاربردی')
        ->assertSee('const landingAgendaItems = JSON.parse', false)
        ->assertSee(base64_encode(json_encode($agendaItems, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)), false);
});

it('stores configurable seminar teachers and their photos', function () {
    Storage::fake('public');
    $manager = User::factory()->create(['role' => UserRole::Super]);
    $blogger = Blogger::factory()->create();

    $this->actingAs($manager)
        ->put(route('panel.event-settings.update'), [
            'teachers' => [
                'eyebrow' => 'اساتید منتخب',
                'title' => 'مدرسان روز سمینار',
                'description' => 'معرفی مدرسان برنامه.',
                'items' => [
                    ['name' => 'استاد اول', 'subject' => 'ریاضی', 'photo' => UploadedFile::fake()->image('teacher.png')],
                    ['name' => 'استاد دوم', 'subject' => 'فیزیک'],
                ],
            ],
        ])
        ->assertRedirect(route('panel.event-settings.edit'));

    $teachers = json_decode((string) DB::table('settings')->where('key', 'landing_teachers')->value('value'), true);
    $firstPhotoPath = $teachers['items'][0]['photo_path'];

    expect($teachers['eyebrow'])->toBe('اساتید منتخب');
    expect($teachers['items'])->toHaveCount(2);
    expect($teachers['items'][1]['name'])->toBe('استاد دوم');
    Storage::disk('public')->assertExists($firstPhotoPath);

    $this->get(route('design.teacher-image', ['image' => basename($firstPhotoPath)]))
        ->assertOk();

    $expectedTeachers = [
        'eyebrow' => 'اساتید منتخب',
        'title' => 'مدرسان روز سمینار',
        'description' => 'معرفی مدرسان برنامه.',
        'items' => [
            ['name' => 'استاد اول', 'subject' => 'ریاضی', 'photo' => route('design.teacher-image', ['image' => basename($firstPhotoPath)])],
            ['name' => 'استاد دوم', 'subject' => 'فیزیک', 'photo' => url('/design/assets/ostad-portrait.png')],
        ],
    ];

    $this->get(route('landing', $blogger))
        ->assertOk()
        ->assertSee('const teachers = JSON.parse', false)
        ->assertSee(base64_encode(json_encode($expectedTeachers, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)), false);
});

it('stores seo settings and exposes sharing metadata on the landing page', function () {
    Storage::fake('public');
    $manager = User::factory()->create(['role' => UserRole::Super]);
    $blogger = Blogger::factory()->create();

    $this->actingAs($manager)
        ->put(route('panel.event-settings.update'), [
            'seo' => [
                'site_title' => 'عنوان اختصاصی سایت',
                'description' => 'توضیح اختصاصی سایت',
                'share_title' => 'عنوان اشتراک‌گذاری',
                'share_description' => 'توضیح اشتراک‌گذاری',
                'image' => UploadedFile::fake()->image('share.png'),
            ],
        ])
        ->assertRedirect(route('panel.event-settings.edit'))
        ->assertSessionHas('status', 'تنظیمات سئو به‌روزرسانی شد.');

    $seo = json_decode((string) DB::table('settings')->where('key', 'site_seo')->value('value'), true);
    Storage::disk('public')->assertExists($seo['image_path']);

    $this->get(route('landing', $blogger))
        ->assertOk()
        ->assertSee('<title>عنوان اختصاصی سایت</title>', false)
        ->assertSee('property="og:title" content="عنوان اشتراک‌گذاری"', false)
        ->assertSee(route('design.seo-image', ['image' => basename($seo['image_path'])]), false);
});

it('updates dynamic landing copy, audience cards, and frequently asked questions', function () {
    $manager = User::factory()->create(['role' => UserRole::Super]);
    $blogger = Blogger::factory()->create();

    $landing = EventSettingsController::defaultLanding();
    $landing['hero']['brand_title'] = 'مجموعه نمونه';
    $landing['hero']['title'] = 'تیتر قابل ویرایش صفحه';
    $landing['audience']['title'] = 'مخاطبان نمونه';
    $landing['audience']['items'] = [['title' => 'دانش‌آموزان هدفمند', 'description' => 'برای ساختن مسیر روشن‌تر.']];
    $landing['faq']['title'] = 'پرسش‌های شما';
    $landing['faq']['items'] = [['question' => 'سوال جدید چیست؟', 'answer' => 'پاسخ از تنظیمات نمایش داده می‌شود.']];
    $landing['reservation']['title'] = 'رزرو نمونه';

    $this->actingAs($manager)
        ->put(route('panel.event-settings.update'), ['landing' => $landing])
        ->assertRedirect(route('panel.event-settings.edit'));

    $this->assertDatabaseHas('settings', ['key' => 'landing_content']);

    $this->get(route('landing', $blogger))
        ->assertSee(base64_encode(json_encode($landing, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)), false)
        ->assertSee('landing.audience.items', false)
        ->assertSee('landing.faq.items', false);
});
