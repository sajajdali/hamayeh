<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $settings = [
            'event_starts_at' => '1853127000',
            'landing_agenda' => '{"eyebrow":"سرفصل‌های روز سمینار","title":"از صبح تا عصر، بدون حرف تکراری","items":[{"time":"۰۹:۰۰","title":"افتتاحیه و نقشه راه سال تحصیلی"},{"time":"۱۰:۳۰","title":"تکنیک‌های مطالعه و تست‌زنی — ریاضی و تجربی"},{"time":"۱۳:۰۰","title":"مدیریت استرس، خواب و تمرکز"},{"time":"۱۵:۰۰","title":"پرسش و پاسخ زنده با استاد گناوه‌ای"}]}',
            'landing_benefits' => '{"eyebrow":"چرا باید در این گردهمایی باشی؟","title":"چهار چیزی که با خودت از سالن بیرون می‌بری","items":[{"title":"برنامه‌ریزی واقعی","description":"یک برنامه هفتگی که با مدرسه، آزمون و زندگی واقعی‌ات جور دربیاید — نه جدول‌های آرمانی."},{"title":"تکنیک تست‌زنی","description":"روش‌های مدیریت زمان و انتخاب تست در کنکور ریاضی و تجربی، با حل زنده روی صحنه."},{"title":"امتحانات نهایی","description":"تأثیر معدل، نقشه مطالعه نهایی‌ها و اینکه کجا باید بین نهایی و کنکور تعادل بسازی."},{"title":"انگیزه‌ی ماندگار","description":"هم‌مسیر شدن با هزاران داوطلب دیگر؛ همان چیزی که تنهایی مطالعه‌کردن از تو گرفته است."}]}',
            'landing_teachers' => '{"eyebrow":"اساتید سمینار","title":"اساتید دروس تخصصی و عمومی آکادمی سینوهه","description":"همان کسانی که سال‌ها رتبه‌های برتر ریاضی و تجربی را ساخته‌اند، این‌بار روی یک صحنه.","items":[{"name":"دکتر احسان زارعی","subject":"زیست","photo_path":null},{"name":"استاد سیاوش بلغانی","subject":"ریاضی","photo_path":null},{"name":"استاد خسرو فیض‌آبادی","subject":"شیمی","photo_path":null},{"name":"استاد نوید شاهی","subject":"فیزیک","photo_path":null},{"name":"استاد حسین خرابی","subject":"هندسه و گسسته","photo_path":null},{"name":"استاد علی عطری","subject":"ادبیات","photo_path":null},{"name":"استاد فرشید مفتون","subject":"زبان","photo_path":null},{"name":"استاد جواد پوران","subject":"عربی","photo_path":null},{"name":"استاد حبیب صابری","subject":"دین و زندگی","photo_path":null},{"name":"استاد حسن علیرضانژاد","subject":"مشاور تحصیلی","photo_path":null}]}',
            'shsms_otp_template' => 'login',
            'shsms_template' => '',
        ];

        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now(), 'created_at' => now()],
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'event_starts_at',
            'landing_agenda',
            'landing_benefits',
            'landing_teachers',
            'shsms_otp_template',
            'shsms_template',
        ])->delete();
    }
};
