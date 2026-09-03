@extends('layouts.app')

@section('content')
    <main class="min-h-screen bg-[#060d20] bg-[radial-gradient(760px_460px_at_82%_-12%,rgba(216,31,42,.2),transparent_62%)] text-[#eaf0ff]" dir="rtl">
        <header class="sticky top-0 z-20 border-b border-white/10 bg-[#060d20]/90 px-4 py-3 backdrop-blur sm:px-7">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-black text-white">پنل مدیریت گردهمایی</p>
                    <p class="mt-0.5 text-xs text-[#8b98ba]">تنظیمات رویداد</p>
                </div>
                <a href="{{ route('panel.registrations') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-3.5 py-2 text-xs font-extrabold text-[#cdd7f0] transition hover:border-rose-400/50 hover:bg-white/10 hover:text-white">
                    <span aria-hidden="true">→</span>
                    بازگشت به پنل
                </a>
            </div>
        </header>

        <section class="mx-auto grid max-w-6xl gap-5 px-4 py-8 sm:px-7 lg:grid-cols-[1.1fr_.9fr] lg:py-12">
            <div class="rounded-3xl border border-white/15 bg-[linear-gradient(150deg,rgba(216,31,42,.2),rgba(255,255,255,.04))] p-6 shadow-[0_40px_80px_-50px_rgba(0,0,0,.95)] sm:p-8">
                <span class="inline-flex rounded-full border border-rose-300/25 bg-rose-400/10 px-3 py-1 text-xs font-black text-rose-200">تنظیمات زمان‌بندی</span>
                <h1 class="mt-5 text-3xl font-black tracking-tight text-white sm:text-4xl">تاریخ برگزاری گردهمایی</h1>
                <p class="mt-4 max-w-xl text-sm leading-8 text-[#cdd7f0]">تاریخ رویداد را به‌شکل شمسی انتخاب کنید. شمارش معکوس سایت برای ساعت ۹ صبح روز انتخاب‌شده به‌روزرسانی می‌شود.</p>

                <div class="mt-7 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl border border-white/10 bg-[#060e24]/60 p-4">
                        <p class="text-xs font-bold text-[#8b98ba]">زمان شروع شمارش</p>
                        <p class="mt-2 text-sm font-black text-white">۹:۰۰ صبح، به وقت تهران</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-[#060e24]/60 p-4">
                        <p class="text-xs font-bold text-[#8b98ba]">نمایش در سایت</p>
                        <p class="mt-2 text-sm font-black text-white">شمارش معکوس صفحهٔ اصلی</p>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-white/15 bg-white/[.055] p-6 shadow-[0_40px_80px_-50px_rgba(0,0,0,.95)] sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-black text-white">انتخاب تاریخ</p>
                        <p class="mt-1 text-xs leading-6 text-[#8b98ba]">تاریخ شمسی برگزاری رویداد را ثبت کنید.</p>
                    </div>
                    <span class="flex size-10 items-center justify-center rounded-2xl bg-rose-500/15 text-lg text-rose-200" aria-hidden="true">⌁</span>
                </div>

                <form method="POST" action="{{ route('panel.event-settings.update') }}" class="mt-7 space-y-5">
                    @csrf
                    @method('PUT')

                    <input id="event-date" name="event_date" type="hidden" value="{{ $eventStartsAt->format('Y-m-d') }}">

                    <div class="space-y-2.5">
                        <label for="jalali-date" class="block text-sm font-bold text-[#cdd7f0]">تاریخ شمسی</label>
                        <button id="jalali-date" type="button" class="group flex w-full items-center justify-between rounded-2xl border border-white/15 bg-[#060e24]/80 px-4 py-4 text-right outline-none transition hover:border-rose-400/70 hover:bg-[#0b1530] focus:border-rose-400 focus:ring-4 focus:ring-rose-400/15" aria-label="انتخاب تاریخ شمسی" aria-haspopup="dialog" aria-expanded="false">
                            <span id="jalali-date-label" class="text-base font-black text-white"></span>
                            <span class="inline-flex items-center gap-2 text-sm font-extrabold text-rose-200 transition group-hover:text-rose-100"><span aria-hidden="true">⌄</span> تقویم</span>
                        </button>
                        @error('event_date')
                            <p class="text-sm font-bold text-rose-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <button class="w-full rounded-2xl bg-[#d81f2a] px-5 py-4 text-sm font-black text-white shadow-[0_14px_32px_-14px_rgba(216,31,42,.9)] transition hover:bg-[#ef2f3b] focus:outline-none focus:ring-4 focus:ring-rose-400/30">ذخیره تاریخ</button>
                </form>
            </div>
        </section>

        <section class="mx-auto max-w-6xl px-4 pb-10 sm:px-7 lg:pb-14">
            <form method="POST" action="{{ route('panel.event-settings.update') }}" enctype="multipart/form-data" class="rounded-3xl border border-white/15 bg-white/[.055] p-5 shadow-[0_40px_80px_-50px_rgba(0,0,0,.95)] sm:p-6">
                @csrf
                @method('PUT')

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-base font-black text-white">تنظیمات سئو</h2>
                        <p class="mt-1 max-w-2xl text-sm leading-7 text-[#cdd7f0]">عنوان و توضیحات سایت و اطلاعاتی که هنگام ارسال لینک در تلگرام و واتس‌اپ نمایش داده می‌شود.</p>
                    </div>
                    <span class="flex size-10 items-center justify-center rounded-2xl bg-rose-500/15 text-rose-200" aria-hidden="true">⌕</span>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <label class="space-y-2"><span class="text-xs font-bold text-[#cdd7f0]">عنوان سایت (Title)</span><input name="seo[site_title]" value="{{ old('seo.site_title', $seo['site_title']) }}" class="w-full rounded-xl border border-white/15 bg-[#060e24]/80 px-3.5 py-3 text-sm font-bold text-white outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-400/15"></label>
                    <label class="space-y-2"><span class="text-xs font-bold text-[#cdd7f0]">عنوان اشتراک‌گذاری</span><input name="seo[share_title]" value="{{ old('seo.share_title', $seo['share_title']) }}" class="w-full rounded-xl border border-white/15 bg-[#060e24]/80 px-3.5 py-3 text-sm font-bold text-white outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-400/15"></label>
                    <label class="space-y-2 md:col-span-2"><span class="text-xs font-bold text-[#cdd7f0]">توضیحات سایت (Meta Description)</span><textarea name="seo[description]" rows="2" class="w-full resize-y rounded-xl border border-white/15 bg-[#060e24]/80 px-3.5 py-3 text-sm leading-7 text-white outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-400/15">{{ old('seo.description', $seo['description']) }}</textarea></label>
                    <label class="space-y-2 md:col-span-2"><span class="text-xs font-bold text-[#cdd7f0]">توضیحات اشتراک‌گذاری</span><textarea name="seo[share_description]" rows="2" class="w-full resize-y rounded-xl border border-white/15 bg-[#060e24]/80 px-3.5 py-3 text-sm leading-7 text-white outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-400/15">{{ old('seo.share_description', $seo['share_description']) }}</textarea></label>
                    <div class="rounded-2xl border border-white/10 bg-[#060e24]/55 p-4 md:col-span-2">
                        <input type="hidden" name="seo[image_path]" value="{{ $seo['image_path'] ?? '' }}">
                        <label class="block cursor-pointer space-y-2"><span class="text-xs font-bold text-[#cdd7f0]">عکس اشتراک‌گذاری (OG Image)</span><span class="block text-xs text-[#8b98ba]">JPG، PNG یا WebP تا ۴ مگابایت؛ نسبت پیشنهادی ۱۲۰۰×۶۳۰.</span><input type="file" name="seo[image]" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-[#cdd7f0] file:ml-4 file:rounded-xl file:border-0 file:bg-rose-400/15 file:px-3 file:py-2 file:text-xs file:font-bold file:text-rose-100"></label>
                    </div>
                </div>

                @error('seo')<p class="mt-4 text-sm font-bold text-rose-200">{{ $message }}</p>@enderror
                <button class="mt-5 w-full rounded-2xl bg-[#d81f2a] px-5 py-4 text-sm font-black text-white shadow-[0_14px_32px_-14px_rgba(216,31,42,.9)] transition hover:bg-[#ef2f3b] focus:outline-none focus:ring-4 focus:ring-rose-400/30">ذخیره تنظیمات سئو</button>
            </form>
        </section>

        <section class="mx-auto max-w-6xl px-4 pb-10 sm:px-7 lg:pb-14">
            <form method="POST" action="{{ route('panel.event-settings.update') }}" enctype="multipart/form-data" class="flex flex-col space-y-5">
                @csrf
                @method('PUT')

                <div class="flex flex-col gap-3 rounded-3xl border border-rose-300/20 bg-rose-400/[.06] p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                    <div>
                        <p class="text-base font-black text-white">محتوای صفحهٔ بلاگرها</p>
                        <p class="mt-1 text-sm leading-7 text-[#cdd7f0]">متن این دو بخش بلافاصله در تمام صفحه‌های عمومی بلاگرها نمایش داده می‌شود.</p>
                    </div>
                    <span class="rounded-full border border-rose-300/25 bg-rose-400/10 px-3 py-1.5 text-xs font-black text-rose-100">نمایش عمومی</span>
                </div>

                <div class="grid gap-5 lg:grid-cols-2" style="order:2">
                    <section class="rounded-3xl border border-white/15 bg-white/[.055] p-5 shadow-[0_40px_80px_-50px_rgba(0,0,0,.95)] sm:p-6">
                        <div class="mb-6 flex items-start justify-between gap-4">
                            <div>
                                <p class="text-base font-black text-white">چرا باید در این گردهمایی باشی؟</p>
                                <p class="mt-1 text-xs leading-6 text-[#8b98ba]">تیتر و چهار مزیت این بخش را تنظیم کنید.</p>
                            </div>
                            <span class="flex size-10 items-center justify-center rounded-2xl bg-rose-500/15 text-rose-200" aria-hidden="true">✦</span>
                        </div>

                        <div class="space-y-4">
                            <label class="block space-y-2">
                                <span class="text-xs font-bold text-[#cdd7f0]">عنوان کوچک</span>
                                <input name="benefits[eyebrow]" value="{{ old('benefits.eyebrow', $benefits['eyebrow']) }}" class="w-full rounded-xl border border-white/15 bg-[#060e24]/80 px-3.5 py-3 text-sm font-bold text-white outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-400/15">
                            </label>
                            <label class="block space-y-2">
                                <span class="text-xs font-bold text-[#cdd7f0]">تیتر اصلی</span>
                                <input name="benefits[title]" value="{{ old('benefits.title', $benefits['title']) }}" class="w-full rounded-xl border border-white/15 bg-[#060e24]/80 px-3.5 py-3 text-sm font-bold text-white outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-400/15">
                            </label>

                            <div class="space-y-3 pt-1">
                                @foreach ($benefits['items'] as $index => $benefit)
                                    <div class="rounded-2xl border border-white/10 bg-[#060e24]/55 p-3.5">
                                        <p class="mb-3 text-xs font-black text-rose-200">مزیت {{ $index + 1 }}</p>
                                        <div class="space-y-3">
                                            <input name="benefits[items][{{ $index }}][title]" value="{{ old("benefits.items.{$index}.title", $benefit['title']) }}" aria-label="عنوان مزیت {{ $index + 1 }}" class="w-full rounded-xl border border-white/10 bg-[#060e24] px-3 py-2.5 text-sm font-bold text-white outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-400/15">
                                            <textarea name="benefits[items][{{ $index }}][description]" rows="3" aria-label="توضیح مزیت {{ $index + 1 }}" class="w-full resize-y rounded-xl border border-white/10 bg-[#060e24] px-3 py-2.5 text-sm leading-7 text-[#cdd7f0] outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-400/15">{{ old("benefits.items.{$index}.description", $benefit['description']) }}</textarea>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </section>

                    <section class="rounded-3xl border border-white/15 bg-white/[.055] p-5 shadow-[0_40px_80px_-50px_rgba(0,0,0,.95)] sm:p-6">
                        <div class="mb-6 flex items-start justify-between gap-4">
                            <div>
                                <p class="text-base font-black text-white">سرفصل‌های روز سمینار</p>
                                <p class="mt-1 text-xs leading-6 text-[#8b98ba]">تیتر و سرفصل‌های زمان‌بندی‌شدهٔ روز سمینار را مدیریت کنید.</p>
                            </div>
                            <span class="flex size-10 items-center justify-center rounded-2xl bg-rose-500/15 text-rose-200" aria-hidden="true">◷</span>
                        </div>

                        <div class="space-y-4">
                            <label class="block space-y-2">
                                <span class="text-xs font-bold text-[#cdd7f0]">عنوان کوچک</span>
                                <input name="agenda[eyebrow]" value="{{ old('agenda.eyebrow', $agenda['eyebrow']) }}" class="w-full rounded-xl border border-white/15 bg-[#060e24]/80 px-3.5 py-3 text-sm font-bold text-white outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-400/15">
                            </label>
                            <label class="block space-y-2">
                                <span class="text-xs font-bold text-[#cdd7f0]">تیتر اصلی</span>
                                <input name="agenda[title]" value="{{ old('agenda.title', $agenda['title']) }}" class="w-full rounded-xl border border-white/15 bg-[#060e24]/80 px-3.5 py-3 text-sm font-bold text-white outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-400/15">
                            </label>

                            @php($agendaItems = old('agenda.items', $agenda['items']))

                            <div class="space-y-3 pt-1" data-agenda-list>
                                @foreach ($agendaItems as $index => $agendaItem)
                                    <div class="grid gap-3 rounded-2xl border border-white/10 bg-[#060e24]/55 p-3.5 sm:grid-cols-[5.5rem_1fr_auto]" data-agenda-item>
                                        <label class="space-y-2">
                                            <span class="text-xs font-black text-rose-200">زمان</span>
                                            <input data-agenda-name="agenda[items][__INDEX__][time]" name="agenda[items][{{ $index }}][time]" value="{{ $agendaItem['time'] }}" aria-label="زمان سرفصل {{ $index + 1 }}" class="w-full rounded-xl border border-white/10 bg-[#060e24] px-3 py-2.5 text-center text-sm font-black text-rose-100 outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-400/15">
                                        </label>
                                        <label class="space-y-2">
                                            <span class="text-xs font-black text-[#cdd7f0]" data-agenda-title-label>سرفصل {{ $index + 1 }}</span>
                                            <input data-agenda-name="agenda[items][__INDEX__][title]" name="agenda[items][{{ $index }}][title]" value="{{ $agendaItem['title'] }}" aria-label="عنوان سرفصل {{ $index + 1 }}" class="w-full rounded-xl border border-white/10 bg-[#060e24] px-3 py-2.5 text-sm font-bold text-white outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-400/15">
                                        </label>
                                        <div class="flex items-end gap-1.5 sm:flex-col sm:justify-end">
                                            <button type="button" data-agenda-action="up" class="inline-flex size-10 items-center justify-center rounded-xl border border-white/15 bg-white/5 text-sm font-black text-[#cdd7f0] transition hover:border-rose-400/50 hover:bg-white/10 hover:text-white disabled:cursor-not-allowed disabled:opacity-35" aria-label="انتقال سرفصل به بالا">↑</button>
                                            <button type="button" data-agenda-action="down" class="inline-flex size-10 items-center justify-center rounded-xl border border-white/15 bg-white/5 text-sm font-black text-[#cdd7f0] transition hover:border-rose-400/50 hover:bg-white/10 hover:text-white disabled:cursor-not-allowed disabled:opacity-35" aria-label="انتقال سرفصل به پایین">↓</button>
                                            <button type="button" data-agenda-action="remove" class="inline-flex size-10 items-center justify-center rounded-xl border border-rose-400/35 bg-rose-400/10 text-sm font-black text-rose-200 transition hover:bg-rose-400/20 disabled:cursor-not-allowed disabled:opacity-35" aria-label="حذف سرفصل">×</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" data-agenda-add class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-rose-300/40 bg-rose-400/[.07] px-4 py-3 text-sm font-black text-rose-100 transition hover:bg-rose-400/[.14] disabled:cursor-not-allowed disabled:opacity-40">
                                <span aria-hidden="true">+</span>
                                افزودن سرفصل
                            </button>

                            <template data-agenda-template>
                                <div class="grid gap-3 rounded-2xl border border-white/10 bg-[#060e24]/55 p-3.5 sm:grid-cols-[5.5rem_1fr_auto]" data-agenda-item>
                                    <label class="space-y-2">
                                        <span class="text-xs font-black text-rose-200">زمان</span>
                                        <input data-agenda-name="agenda[items][__INDEX__][time]" value="" aria-label="زمان سرفصل" placeholder="۰۹:۰۰" class="w-full rounded-xl border border-white/10 bg-[#060e24] px-3 py-2.5 text-center text-sm font-black text-rose-100 outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-400/15">
                                    </label>
                                    <label class="space-y-2">
                                        <span class="text-xs font-black text-[#cdd7f0]" data-agenda-title-label>سرفصل</span>
                                        <input data-agenda-name="agenda[items][__INDEX__][title]" value="" aria-label="عنوان سرفصل" placeholder="عنوان سرفصل جدید" class="w-full rounded-xl border border-white/10 bg-[#060e24] px-3 py-2.5 text-sm font-bold text-white outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-400/15">
                                    </label>
                                    <div class="flex items-end gap-1.5 sm:flex-col sm:justify-end">
                                        <button type="button" data-agenda-action="up" class="inline-flex size-10 items-center justify-center rounded-xl border border-white/15 bg-white/5 text-sm font-black text-[#cdd7f0] transition hover:border-rose-400/50 hover:bg-white/10 hover:text-white disabled:cursor-not-allowed disabled:opacity-35" aria-label="انتقال سرفصل به بالا">↑</button>
                                        <button type="button" data-agenda-action="down" class="inline-flex size-10 items-center justify-center rounded-xl border border-white/15 bg-white/5 text-sm font-black text-[#cdd7f0] transition hover:border-rose-400/50 hover:bg-white/10 hover:text-white disabled:cursor-not-allowed disabled:opacity-35" aria-label="انتقال سرفصل به پایین">↓</button>
                                        <button type="button" data-agenda-action="remove" class="inline-flex size-10 items-center justify-center rounded-xl border border-rose-400/35 bg-rose-400/10 text-sm font-black text-rose-200 transition hover:bg-rose-400/20 disabled:cursor-not-allowed disabled:opacity-35" aria-label="حذف سرفصل">×</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </section>
                </div>

                <section class="rounded-3xl border border-white/15 bg-white/[.055] p-5 shadow-[0_40px_80px_-50px_rgba(0,0,0,.95)] sm:p-6" style="order:1">
                    <div class="mb-6 flex items-start justify-between gap-4">
                        <div>
                            <p class="text-base font-black text-white">اساتید سمینار</p>
                            <p class="mt-1 text-xs leading-6 text-[#8b98ba]">تیترهای بخش و کارت هر استاد را از اینجا مدیریت کنید. عکس‌ها با فرمت JPG، PNG یا WebP و حداکثر ۲ مگابایت هستند.</p>
                        </div>
                        <span class="flex size-10 items-center justify-center rounded-2xl bg-rose-500/15 text-rose-200" aria-hidden="true">♟</span>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="block space-y-2">
                            <span class="text-xs font-bold text-[#cdd7f0]">عنوان کوچک</span>
                            <input name="teachers[eyebrow]" value="{{ old('teachers.eyebrow', $teachers['eyebrow']) }}" class="w-full rounded-xl border border-white/15 bg-[#060e24]/80 px-3.5 py-3 text-sm font-bold text-white outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-400/15">
                        </label>
                        <label class="block space-y-2">
                            <span class="text-xs font-bold text-[#cdd7f0]">تیتر اصلی</span>
                            <input name="teachers[title]" value="{{ old('teachers.title', $teachers['title']) }}" class="w-full rounded-xl border border-white/15 bg-[#060e24]/80 px-3.5 py-3 text-sm font-bold text-white outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-400/15">
                        </label>
                        <label class="block space-y-2 md:col-span-2">
                            <span class="text-xs font-bold text-[#cdd7f0]">توضیح زیر تیتر</span>
                            <textarea name="teachers[description]" rows="2" class="w-full resize-y rounded-xl border border-white/15 bg-[#060e24]/80 px-3.5 py-3 text-sm leading-7 text-white outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-400/15">{{ old('teachers.description', $teachers['description']) }}</textarea>
                        </label>
                    </div>

                    @php($teacherItems = old('teachers.items', $teachers['items']))

                    <div class="mt-5 space-y-3" data-teacher-list>
                        @foreach ($teacherItems as $index => $teacher)
                            @php($teacherPhoto = !empty($teacher['photo_path']) ? route('design.teacher-image', ['image' => basename($teacher['photo_path'])]) : url('/design/assets/ostad-portrait.png'))
                            <div class="grid gap-4 rounded-2xl border border-white/10 bg-[#060e24]/55 p-4 md:grid-cols-[5.5rem_1fr_1fr_auto]" data-teacher-item>
                                <div class="flex flex-col items-center gap-2">
                                    <img src="{{ $teacherPhoto }}" alt="پیش‌نمایش استاد" data-teacher-preview class="rounded-full border-2 border-rose-300/40 object-cover" style="width:80px; height:80px; flex:none;">
                                    <label class="cursor-pointer text-center text-xs font-bold text-rose-200 underline underline-offset-4">
                                        عکس
                                        <input type="file" accept="image/jpeg,image/png,image/webp" data-teacher-name="teachers[items][__INDEX__][photo]" name="teachers[items][{{ $index }}][photo]" style="position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0;" data-teacher-photo>
                                    </label>
                                    <input type="hidden" data-teacher-name="teachers[items][__INDEX__][photo_path]" name="teachers[items][{{ $index }}][photo_path]" value="{{ $teacher['photo_path'] ?? '' }}">
                                </div>
                                <label class="space-y-2">
                                    <span class="text-xs font-black text-[#cdd7f0]" data-teacher-name-label>نام استاد {{ $index + 1 }}</span>
                                    <input data-teacher-name="teachers[items][__INDEX__][name]" name="teachers[items][{{ $index }}][name]" value="{{ $teacher['name'] }}" aria-label="نام استاد {{ $index + 1 }}" class="w-full rounded-xl border border-white/10 bg-[#060e24] px-3 py-2.5 text-sm font-bold text-white outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-400/15">
                                </label>
                                <label class="space-y-2">
                                    <span class="text-xs font-black text-rose-200">درس یا حوزه</span>
                                    <input data-teacher-name="teachers[items][__INDEX__][subject]" name="teachers[items][{{ $index }}][subject]" value="{{ $teacher['subject'] }}" aria-label="درس استاد {{ $index + 1 }}" class="w-full rounded-xl border border-white/10 bg-[#060e24] px-3 py-2.5 text-sm font-bold text-white outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-400/15">
                                </label>
                                <div class="flex items-end gap-1.5 md:flex-col md:justify-end">
                                    <button type="button" data-teacher-action="up" class="inline-flex size-10 items-center justify-center rounded-xl border border-white/15 bg-white/5 text-sm font-black text-[#cdd7f0] transition hover:border-rose-400/50 hover:bg-white/10 hover:text-white disabled:cursor-not-allowed disabled:opacity-35" aria-label="انتقال استاد به بالا">↑</button>
                                    <button type="button" data-teacher-action="down" class="inline-flex size-10 items-center justify-center rounded-xl border border-white/15 bg-white/5 text-sm font-black text-[#cdd7f0] transition hover:border-rose-400/50 hover:bg-white/10 hover:text-white disabled:cursor-not-allowed disabled:opacity-35" aria-label="انتقال استاد به پایین">↓</button>
                                    <button type="button" data-teacher-action="remove" class="inline-flex size-10 items-center justify-center rounded-xl border border-rose-400/35 bg-rose-400/10 text-sm font-black text-rose-200 transition hover:bg-rose-400/20 disabled:cursor-not-allowed disabled:opacity-35" aria-label="حذف استاد">×</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" data-teacher-add class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-rose-300/40 bg-rose-400/[.07] px-4 py-3 text-sm font-black text-rose-100 transition hover:bg-rose-400/[.14] disabled:cursor-not-allowed disabled:opacity-40"><span aria-hidden="true">+</span> افزودن استاد</button>

                    <template data-teacher-template>
                        <div class="grid gap-4 rounded-2xl border border-white/10 bg-[#060e24]/55 p-4 md:grid-cols-[5.5rem_1fr_1fr_auto]" data-teacher-item>
                            <div class="flex flex-col items-center gap-2">
                                <img src="{{ url('/design/assets/ostad-portrait.png') }}" alt="پیش‌نمایش استاد" data-teacher-preview class="rounded-full border-2 border-rose-300/40 object-cover" style="width:80px; height:80px; flex:none;">
                                <label class="cursor-pointer text-center text-xs font-bold text-rose-200 underline underline-offset-4">عکس<input type="file" accept="image/jpeg,image/png,image/webp" data-teacher-name="teachers[items][__INDEX__][photo]" style="position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0;" data-teacher-photo></label>
                                <input type="hidden" data-teacher-name="teachers[items][__INDEX__][photo_path]" value="">
                            </div>
                            <label class="space-y-2"><span class="text-xs font-black text-[#cdd7f0]" data-teacher-name-label>نام استاد</span><input data-teacher-name="teachers[items][__INDEX__][name]" value="" aria-label="نام استاد" placeholder="نام و نام خانوادگی" class="w-full rounded-xl border border-white/10 bg-[#060e24] px-3 py-2.5 text-sm font-bold text-white outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-400/15"></label>
                            <label class="space-y-2"><span class="text-xs font-black text-rose-200">درس یا حوزه</span><input data-teacher-name="teachers[items][__INDEX__][subject]" value="" aria-label="درس استاد" placeholder="مثال: ریاضی" class="w-full rounded-xl border border-white/10 bg-[#060e24] px-3 py-2.5 text-sm font-bold text-white outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-400/15"></label>
                            <div class="flex items-end gap-1.5 md:flex-col md:justify-end"><button type="button" data-teacher-action="up" class="inline-flex size-10 items-center justify-center rounded-xl border border-white/15 bg-white/5 text-sm font-black text-[#cdd7f0] disabled:cursor-not-allowed disabled:opacity-35" aria-label="انتقال استاد به بالا">↑</button><button type="button" data-teacher-action="down" class="inline-flex size-10 items-center justify-center rounded-xl border border-white/15 bg-white/5 text-sm font-black text-[#cdd7f0] disabled:cursor-not-allowed disabled:opacity-35" aria-label="انتقال استاد به پایین">↓</button><button type="button" data-teacher-action="remove" class="inline-flex size-10 items-center justify-center rounded-xl border border-rose-400/35 bg-rose-400/10 text-sm font-black text-rose-200 disabled:cursor-not-allowed disabled:opacity-35" aria-label="حذف استاد">×</button></div>
                        </div>
                    </template>
                </section>

                @if ($errors->has('benefits') || $errors->has('agenda') || $errors->has('teachers'))
                    <p class="rounded-2xl border border-rose-400/30 bg-rose-400/10 px-4 py-3 text-sm font-bold text-rose-100" style="order:3">لطفاً همهٔ فیلدهای هر بخش را کامل کنید.</p>
                @endif

                <button class="w-full rounded-2xl bg-[#d81f2a] px-5 py-4 text-sm font-black text-white shadow-[0_14px_32px_-14px_rgba(216,31,42,.9)] transition hover:bg-[#ef2f3b] focus:outline-none focus:ring-4 focus:ring-rose-400/30" style="order:4">ذخیره محتوای صفحهٔ بلاگرها</button>
            </form>
        </section>

        <section class="mx-auto max-w-6xl px-4 pb-10 sm:px-7 lg:pb-14">
            <form method="POST" action="{{ route('panel.event-settings.update') }}" class="rounded-3xl border border-white/15 bg-white/[.055] p-5 shadow-[0_40px_80px_-50px_rgba(0,0,0,.95)] sm:p-6">
                @csrf
                @method('PUT')

                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-base font-black text-white">پیامک واقعی SHSMS</h2>
                        <p class="mt-1 max-w-2xl text-sm leading-7 text-[#cdd7f0]">فقط نام الگویی را که در پنل SHSMS ساخته‌اید وارد کنید. موقع ارسال، پارامترها خودکار جایگزین می‌شوند.</p>
                    </div>
                    <span class="rounded-full border border-rose-300/25 bg-rose-400/10 px-3 py-1.5 text-xs font-black text-rose-100">param1 تا param4</span>
                </div>

                <label class="mt-5 block max-w-xl space-y-2">
                    <span class="text-xs font-bold text-[#cdd7f0]">نام الگوی پیامک رویداد</span>
                    <input name="shsms_template" value="{{ old('shsms_template', $shsmsTemplate) }}" placeholder="reminder" dir="ltr" class="w-full rounded-xl border border-white/15 bg-[#060e24]/80 px-3.5 py-3 text-sm font-bold text-white outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-400/15">
                    @error('shsms_template')
                        <p class="text-sm font-bold text-rose-300">{{ $message }}</p>
                    @enderror
                </label>

                <label class="mt-4 block max-w-xl space-y-2">
                    <span class="text-xs font-bold text-[#cdd7f0]">نام الگوی کد ورود</span>
                    <input name="shsms_otp_template" value="{{ old('shsms_otp_template', $shsmsOtpTemplate) }}" placeholder="login_code" dir="ltr" class="w-full rounded-xl border border-white/15 bg-[#060e24]/80 px-3.5 py-3 text-sm font-bold text-white outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-400/15">
                    @error('shsms_otp_template')
                        <p class="text-sm font-bold text-rose-300">{{ $message }}</p>
                    @enderror
                </label>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach (['param1' => 'نام ثبت‌نام‌کننده', 'param2' => 'تاریخ رویداد', 'param3' => 'ساعت شروع', 'param4' => 'عنوان رویداد'] as $parameter => $value)
                        <div class="rounded-2xl border border-white/10 bg-[#060e24]/60 p-3.5">
                            <p class="font-mono text-xs font-black text-rose-200">{{ $parameter }}</p>
                            <p class="mt-1 text-sm font-bold text-white">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-3 rounded-2xl border border-white/10 bg-[#060e24]/60 p-3.5">
                    <p class="font-mono text-xs font-black text-rose-200">الگوی کد ورود: param1</p>
                    <p class="mt-1 text-sm font-bold text-white">فقط کد یک‌بارمصرف ورود</p>
                    <p class="mt-2 text-xs leading-6 text-[#8b98ba]">برای ورود خودکار در Chrome Android، خط آخر متن الگوی SHSMS را به‌شکل <code class="text-rose-200">@your-domain.ir #%param1%</code> قرار دهید. در iPhone هم کد با پیشنهاد خودکار کیبورد نمایش داده می‌شود.</p>
                </div>

                <p class="mt-5 text-xs leading-6 text-[#8b98ba]">برای ارسال واقعی، مقدار <code class="text-rose-200">SHSMS_API_TOKEN</code> را در فایل محیط اجرا ثبت کنید و <code class="text-rose-200">SMS_SEND_SANDBOX=false</code> باشد.</p>
                <button class="mt-5 w-full rounded-2xl bg-[#d81f2a] px-5 py-4 text-sm font-black text-white shadow-[0_14px_32px_-14px_rgba(216,31,42,.9)] transition hover:bg-[#ef2f3b] focus:outline-none focus:ring-4 focus:ring-rose-400/30">ذخیره تنظیمات پیامک</button>
            </form>
        </section>

        <section class="mx-auto max-w-6xl px-4 pb-10 sm:px-7 lg:pb-14">
            <form method="POST" action="{{ route('panel.event-settings.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="rounded-3xl border border-rose-300/20 bg-rose-400/[.06] p-5 sm:p-6">
                    <p class="text-base font-black text-white">متن‌های صفحهٔ بلاگرها</p>
                    <p class="mt-1 text-sm leading-7 text-[#cdd7f0]">متن‌های بالای صفحه، مخاطبان، سوالات متداول و باکس رزرو را از اینجا مدیریت کنید.</p>
                </div>

                <section class="rounded-3xl border border-white/15 bg-white/[.055] p-5 sm:p-6">
                    <h2 class="text-base font-black text-white">متن‌های بالای صفحه</h2>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        @foreach (['brand_title' => 'نام مجموعه', 'brand_subtitle' => 'نام انگلیسی مجموعه', 'cta_label' => 'متن دکمه ثبت‌نام', 'eyebrow' => 'تیتر کوچک رویداد', 'date_label' => 'عنوان تاریخ', 'capacity_label' => 'عنوان ظرفیت', 'capacity_value' => 'مقدار ظرفیت', 'cost_label' => 'عنوان هزینه', 'cost_value' => 'مقدار هزینه'] as $field => $label)
                            <label class="block space-y-2">
                                <span class="text-xs font-bold text-[#cdd7f0]">{{ $label }}</span>
                                <input name="landing[hero][{{ $field }}]" value="{{ old("landing.hero.{$field}", $landing['hero'][$field]) }}" class="w-full rounded-xl border border-white/15 bg-[#060e24]/80 px-3.5 py-3 text-sm font-bold text-white outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-400/15">
                            </label>
                        @endforeach
                        <label class="block space-y-2 md:col-span-2">
                            <span class="text-xs font-bold text-[#cdd7f0]">تیتر اصلی</span>
                            <textarea name="landing[hero][title]" rows="3" class="w-full resize-y rounded-xl border border-white/15 bg-[#060e24]/80 px-3.5 py-3 text-sm leading-7 text-white outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-400/15">{{ old('landing.hero.title', $landing['hero']['title']) }}</textarea>
                        </label>
                        <label class="block space-y-2 md:col-span-2">
                            <span class="text-xs font-bold text-[#cdd7f0]">توضیح زیر تیتر</span>
                            <textarea name="landing[hero][description]" rows="3" class="w-full resize-y rounded-xl border border-white/15 bg-[#060e24]/80 px-3.5 py-3 text-sm leading-7 text-white outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-400/15">{{ old('landing.hero.description', $landing['hero']['description']) }}</textarea>
                        </label>
                    </div>
                </section>

                <section class="rounded-3xl border border-white/15 bg-white/[.055] p-5 sm:p-6">
                    <h2 class="text-base font-black text-white">بخش مخاطبان</h2>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <input name="landing[audience][eyebrow]" value="{{ old('landing.audience.eyebrow', $landing['audience']['eyebrow']) }}" aria-label="تیتر کوچک مخاطبان" class="rounded-xl border border-white/15 bg-[#060e24]/80 px-3.5 py-3 text-sm font-bold text-white outline-none focus:border-rose-400">
                        <input name="landing[audience][title]" value="{{ old('landing.audience.title', $landing['audience']['title']) }}" aria-label="تیتر مخاطبان" class="rounded-xl border border-white/15 bg-[#060e24]/80 px-3.5 py-3 text-sm font-bold text-white outline-none focus:border-rose-400">
                    </div>
                    <div id="audience-items" class="mt-5 space-y-3">
                        @foreach (old('landing.audience.items', $landing['audience']['items']) as $index => $item)
                            <div class="content-item rounded-2xl border border-white/10 bg-[#060e24]/55 p-4">
                                <div class="grid gap-3 md:grid-cols-[1fr_2fr_auto]">
                                    <input name="landing[audience][items][{{ $index }}][title]" value="{{ $item['title'] }}" aria-label="عنوان مخاطب" placeholder="عنوان" class="rounded-xl border border-white/10 bg-[#060e24] px-3 py-2.5 text-sm text-white outline-none focus:border-rose-400">
                                    <textarea name="landing[audience][items][{{ $index }}][description]" rows="2" aria-label="توضیح مخاطب" placeholder="توضیح" class="resize-y rounded-xl border border-white/10 bg-[#060e24] px-3 py-2.5 text-sm text-white outline-none focus:border-rose-400">{{ $item['description'] }}</textarea>
                                    <button type="button" data-remove-item class="rounded-xl border border-rose-400/30 px-3 text-xs font-bold text-rose-200">حذف</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" data-add-item="audience" class="mt-4 rounded-xl border border-white/15 bg-white/5 px-4 py-2.5 text-xs font-black text-[#cdd7f0]">+ افزودن مخاطب</button>
                </section>

                <section class="rounded-3xl border border-white/15 bg-white/[.055] p-5 sm:p-6">
                    <h2 class="text-base font-black text-white">سوالات متداول</h2>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <input name="landing[faq][eyebrow]" value="{{ old('landing.faq.eyebrow', $landing['faq']['eyebrow']) }}" aria-label="تیتر کوچک سوالات" class="rounded-xl border border-white/15 bg-[#060e24]/80 px-3.5 py-3 text-sm font-bold text-white outline-none focus:border-rose-400">
                        <input name="landing[faq][title]" value="{{ old('landing.faq.title', $landing['faq']['title']) }}" aria-label="تیتر سوالات" class="rounded-xl border border-white/15 bg-[#060e24]/80 px-3.5 py-3 text-sm font-bold text-white outline-none focus:border-rose-400">
                    </div>
                    <div id="faq-items" class="mt-5 space-y-3">
                        @foreach (old('landing.faq.items', $landing['faq']['items']) as $index => $item)
                            <div class="content-item rounded-2xl border border-white/10 bg-[#060e24]/55 p-4">
                                <div class="grid gap-3 md:grid-cols-[1fr_auto]">
                                    <input name="landing[faq][items][{{ $index }}][question]" value="{{ $item['question'] }}" aria-label="سوال" placeholder="سوال" class="rounded-xl border border-white/10 bg-[#060e24] px-3 py-2.5 text-sm text-white outline-none focus:border-rose-400">
                                    <button type="button" data-remove-item class="rounded-xl border border-rose-400/30 px-3 text-xs font-bold text-rose-200">حذف</button>
                                    <textarea name="landing[faq][items][{{ $index }}][answer]" rows="3" aria-label="پاسخ" placeholder="پاسخ" class="resize-y rounded-xl border border-white/10 bg-[#060e24] px-3 py-2.5 text-sm text-white outline-none focus:border-rose-400 md:col-span-2">{{ $item['answer'] }}</textarea>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" data-add-item="faq" class="mt-4 rounded-xl border border-white/15 bg-white/5 px-4 py-2.5 text-xs font-black text-[#cdd7f0]">+ افزودن سوال</button>
                </section>

                <section class="rounded-3xl border border-white/15 bg-white/[.055] p-5 sm:p-6">
                    <h2 class="text-base font-black text-white">باکس رزرو</h2>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        @foreach (['title' => 'تیتر', 'cta_label' => 'متن دکمه'] as $field => $label)
                            <label class="block space-y-2"><span class="text-xs font-bold text-[#cdd7f0]">{{ $label }}</span><input name="landing[reservation][{{ $field }}]" value="{{ old("landing.reservation.{$field}", $landing['reservation'][$field]) }}" class="w-full rounded-xl border border-white/15 bg-[#060e24]/80 px-3.5 py-3 text-sm font-bold text-white outline-none focus:border-rose-400"></label>
                        @endforeach
                        <label class="block space-y-2 md:col-span-2"><span class="text-xs font-bold text-[#cdd7f0]">توضیح</span><textarea name="landing[reservation][description]" rows="3" class="w-full resize-y rounded-xl border border-white/15 bg-[#060e24]/80 px-3.5 py-3 text-sm text-white outline-none focus:border-rose-400">{{ old('landing.reservation.description', $landing['reservation']['description']) }}</textarea></label>
                    </div>
                </section>

                <button class="w-full rounded-2xl bg-[#d81f2a] px-5 py-4 text-sm font-black text-white shadow-[0_14px_32px_-14px_rgba(216,31,42,.9)] transition hover:bg-[#ef2f3b] focus:outline-none focus:ring-4 focus:ring-rose-400/30">ذخیره متن‌های صفحهٔ بلاگرها</button>
            </form>
        </section>
    </main>

    <dialog id="jalali-picker" class="m-auto w-[min(92vw,26rem)] max-w-none rounded-3xl border border-white/15 bg-[#0d1a3c] p-0 text-[#eaf0ff] shadow-[0_32px_90px_-25px_rgba(0,0,0,.95)] backdrop:bg-[#030714]/80 backdrop:backdrop-blur-sm">
        <div class="p-5 sm:p-6" dir="rtl">
            <div class="flex items-center justify-between gap-3 border-b border-white/10 pb-4">
                <button id="next-month" type="button" class="rounded-xl border border-white/15 bg-white/5 px-3 py-2 text-xs font-black text-[#cdd7f0] transition hover:border-rose-400/60 hover:bg-white/10 hover:text-white">ماه بعد</button>
                <strong id="picker-title" class="text-base font-black text-white"></strong>
                <button id="previous-month" type="button" class="rounded-xl border border-white/15 bg-white/5 px-3 py-2 text-xs font-black text-[#cdd7f0] transition hover:border-rose-400/60 hover:bg-white/10 hover:text-white">ماه قبل</button>
            </div>
            <div class="mt-5 grid grid-cols-7 gap-1 text-center text-xs font-black text-[#8b98ba]">
                <span>ش</span><span>ی</span><span>د</span><span>س</span><span>چ</span><span>پ</span><span>ج</span>
            </div>
            <div id="calendar-days" class="mt-3 grid grid-cols-7 gap-1.5"></div>
            <button id="close-picker" type="button" class="mt-5 w-full rounded-xl border border-white/15 bg-white/5 py-3 text-sm font-black text-[#cdd7f0] transition hover:bg-white/10 hover:text-white">بستن تقویم</button>
        </div>
    </dialog>

    <script>
        (() => {
            const faDigits = '۰۱۲۳۴۵۶۷۸۹';
            const monthNames = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
            const hiddenInput = document.getElementById('event-date');
            const dateButton = document.getElementById('jalali-date');
            const dateLabel = document.getElementById('jalali-date-label');
            const dialog = document.getElementById('jalali-picker');
            const title = document.getElementById('picker-title');
            const days = document.getElementById('calendar-days');

            const fa = value => String(value).replace(/\d/g, digit => faDigits[digit]);
            const pad = value => String(value).padStart(2, '0');
            const div = (value, by) => Math.floor(value / by);

            function gregorianToJalali(year, month, day) {
                const gregorianDaysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
                let gy = year - 1600;
                let gm = month - 1;
                let gd = day - 1;
                let dayNumber = 365 * gy + div(gy + 3, 4) - div(gy + 99, 100) + div(gy + 399, 400);

                for (let index = 0; index < gm; index++) dayNumber += gregorianDaysInMonth[index];
                if (gm > 1 && ((gy % 4 === 0 && gy % 100 !== 0) || gy % 400 === 0)) dayNumber++;
                dayNumber += gd;

                let jalaliDayNumber = dayNumber - 79;
                const jalaliCycles = div(jalaliDayNumber, 12053);
                jalaliDayNumber %= 12053;
                let jy = 979 + 33 * jalaliCycles + 4 * div(jalaliDayNumber, 1461);
                jalaliDayNumber %= 1461;
                if (jalaliDayNumber >= 366) {
                    jy += div(jalaliDayNumber - 1, 365);
                    jalaliDayNumber = (jalaliDayNumber - 1) % 365;
                }
                const jm = jalaliDayNumber < 186 ? 1 + div(jalaliDayNumber, 31) : 7 + div(jalaliDayNumber - 186, 30);
                const jd = 1 + (jalaliDayNumber < 186 ? jalaliDayNumber % 31 : (jalaliDayNumber - 186) % 30);
                return { year: jy, month: jm, day: jd };
            }

            function jalaliToGregorian(year, month, day) {
                let jy = year + 1595;
                let dayNumber = -355668 + 365 * jy + div(jy, 33) * 8 + div((jy % 33) + 3, 4) + day + (month < 7 ? (month - 1) * 31 : (month - 7) * 30 + 186);
                let gy = 400 * div(dayNumber, 146097);
                dayNumber %= 146097;
                let leap = true;

                if (dayNumber >= 36525) {
                    dayNumber--;
                    gy += 100 * div(dayNumber, 36524);
                    dayNumber %= 36524;
                    if (dayNumber >= 365) dayNumber++;
                    else leap = false;
                }
                gy += 4 * div(dayNumber, 1461);
                dayNumber %= 1461;
                if (dayNumber >= 366) {
                    leap = false;
                    dayNumber--;
                    gy += div(dayNumber, 365);
                    dayNumber %= 365;
                }
                const gregorianDaysInMonth = [31, leap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
                let gm = 0;
                while (dayNumber >= gregorianDaysInMonth[gm]) dayNumber -= gregorianDaysInMonth[gm++];
                return { year: gy, month: gm + 1, day: dayNumber + 1 };
            }

            function daysInMonth(year, month) {
                const current = jalaliToGregorian(year, month, 1);
                const next = month === 12 ? jalaliToGregorian(year + 1, 1, 1) : jalaliToGregorian(year, month + 1, 1);
                return Math.round((Date.UTC(next.year, next.month - 1, next.day) - Date.UTC(current.year, current.month - 1, current.day)) / 86400000);
            }

            function selectedDate() {
                const [year, month, day] = hiddenInput.value.split('-').map(Number);
                return gregorianToJalali(year, month, day);
            }

            let visible = selectedDate();

            function updateLabel() {
                const date = selectedDate();
                dateLabel.textContent = `${fa(date.day)} ${monthNames[date.month - 1]} ${fa(date.year)}`;
            }

            function renderCalendar() {
                title.textContent = `${monthNames[visible.month - 1]} ${fa(visible.year)}`;
                days.innerHTML = '';
                const first = jalaliToGregorian(visible.year, visible.month, 1);
                const firstWeekday = (new Date(Date.UTC(first.year, first.month - 1, first.day)).getUTCDay() + 1) % 7;
                const selected = selectedDate();

                for (let index = 0; index < firstWeekday; index++) days.append(document.createElement('span'));
                for (let day = 1; day <= daysInMonth(visible.year, visible.month); day++) {
                    const button = document.createElement('button');
                    const isSelected = selected.year === visible.year && selected.month === visible.month && selected.day === day;
                    button.type = 'button';
                    button.textContent = fa(day);
                    button.className = `flex aspect-square items-center justify-center rounded-xl text-sm font-black transition focus:outline-none focus:ring-2 focus:ring-rose-300/70 ${isSelected ? 'bg-[#d81f2a] text-white shadow-[0_8px_20px_-8px_rgba(216,31,42,.9)]' : 'text-[#cdd7f0] hover:bg-white/10 hover:text-white'}`;
                    button.addEventListener('click', () => {
                        const gregorian = jalaliToGregorian(visible.year, visible.month, day);
                        hiddenInput.value = `${gregorian.year}-${pad(gregorian.month)}-${pad(gregorian.day)}`;
                        updateLabel();
                        dialog.close();
                        dateButton.setAttribute('aria-expanded', 'false');
                    });
                    days.append(button);
                }
            }

            dateButton.addEventListener('click', () => {
                visible = selectedDate();
                renderCalendar();
                dialog.showModal();
                dateButton.setAttribute('aria-expanded', 'true');
            });
            document.getElementById('close-picker').addEventListener('click', () => dialog.close());
            document.getElementById('previous-month').addEventListener('click', () => {
                visible = visible.month === 1 ? { year: visible.year - 1, month: 12 } : { year: visible.year, month: visible.month - 1 };
                renderCalendar();
            });
            document.getElementById('next-month').addEventListener('click', () => {
                visible = visible.month === 12 ? { year: visible.year + 1, month: 1 } : { year: visible.year, month: visible.month + 1 };
                renderCalendar();
            });
            dialog.addEventListener('close', () => dateButton.setAttribute('aria-expanded', 'false'));
            dialog.addEventListener('click', event => {
                if (event.target === dialog) dialog.close();
            });
            updateLabel();
        })();
    </script>
    <script>
        (() => {
            const list = document.querySelector('[data-teacher-list]');
            const template = document.querySelector('[data-teacher-template]');
            const addButton = document.querySelector('[data-teacher-add]');

            if (!list || !template || !addButton) {
                return;
            }

            const items = () => Array.from(list.querySelectorAll('[data-teacher-item]'));

            const refreshItems = () => {
                const teacherItems = items();

                teacherItems.forEach((item, index) => {
                    item.querySelectorAll('[data-teacher-name]').forEach(input => {
                        input.name = input.dataset.teacherName.replace('__INDEX__', index);
                    });

                    const nameInput = item.querySelector('[data-teacher-name$="[name]"]');
                    const subjectInput = item.querySelector('[data-teacher-name$="[subject]"]');
                    item.querySelector('[data-teacher-name-label]').textContent = `نام استاد ${index + 1}`;
                    nameInput.setAttribute('aria-label', `نام استاد ${index + 1}`);
                    subjectInput.setAttribute('aria-label', `درس استاد ${index + 1}`);
                    item.querySelector('[data-teacher-action="up"]').disabled = index === 0;
                    item.querySelector('[data-teacher-action="down"]').disabled = index === teacherItems.length - 1;
                    item.querySelector('[data-teacher-action="remove"]').disabled = teacherItems.length === 1;
                });

                addButton.disabled = teacherItems.length >= 20;
            };

            const setPreview = input => {
                const file = input.files?.[0];

                if (!file) {
                    return;
                }

                input.closest('[data-teacher-item]').querySelector('[data-teacher-preview]').src = URL.createObjectURL(file);
            };

            addButton.addEventListener('click', () => {
                if (items().length >= 20) {
                    return;
                }

                const item = template.content.firstElementChild.cloneNode(true);
                list.append(item);
                refreshItems();
                item.querySelector('[data-teacher-name$="[name]"]').focus();
            });

            list.addEventListener('change', event => {
                if (event.target.matches('[data-teacher-photo]')) {
                    setPreview(event.target);
                }
            });

            list.addEventListener('click', event => {
                const button = event.target.closest('[data-teacher-action]');

                if (!button) {
                    return;
                }

                const item = button.closest('[data-teacher-item]');
                const action = button.dataset.teacherAction;

                if (action === 'remove' && items().length > 1) {
                    item.remove();
                }

                if (action === 'up' && item.previousElementSibling) {
                    list.insertBefore(item, item.previousElementSibling);
                }

                if (action === 'down' && item.nextElementSibling) {
                    list.insertBefore(item.nextElementSibling, item);
                }

                refreshItems();
            });

            refreshItems();
        })();
    </script>
    <script>
        (() => {
            const list = document.querySelector('[data-agenda-list]');
            const template = document.querySelector('[data-agenda-template]');
            const addButton = document.querySelector('[data-agenda-add]');

            if (!list || !template || !addButton) {
                return;
            }

            const items = () => Array.from(list.querySelectorAll('[data-agenda-item]'));

            const refreshItems = () => {
                const agendaItems = items();

                agendaItems.forEach((item, index) => {
                    item.querySelectorAll('[data-agenda-name]').forEach(input => {
                        input.name = input.dataset.agendaName.replace('__INDEX__', index);
                    });

                    const titleLabel = item.querySelector('[data-agenda-title-label]');
                    const titleInput = item.querySelector('[data-agenda-name$="[title]"]');
                    const timeInput = item.querySelector('[data-agenda-name$="[time]"]');

                    titleLabel.textContent = `سرفصل ${index + 1}`;
                    titleInput.setAttribute('aria-label', `عنوان سرفصل ${index + 1}`);
                    timeInput.setAttribute('aria-label', `زمان سرفصل ${index + 1}`);
                    item.querySelector('[data-agenda-action="up"]').disabled = index === 0;
                    item.querySelector('[data-agenda-action="down"]').disabled = index === agendaItems.length - 1;
                    item.querySelector('[data-agenda-action="remove"]').disabled = agendaItems.length === 1;
                });

                addButton.disabled = agendaItems.length >= 20;
            };

            addButton.addEventListener('click', () => {
                if (items().length >= 20) {
                    return;
                }

                const item = template.content.firstElementChild.cloneNode(true);
                list.append(item);
                refreshItems();
                item.querySelector('[data-agenda-name$="[time]"]').focus();
            });

            list.addEventListener('click', event => {
                const button = event.target.closest('[data-agenda-action]');

                if (!button) {
                    return;
                }

                const item = button.closest('[data-agenda-item]');
                const action = button.dataset.agendaAction;

                if (action === 'remove' && items().length > 1) {
                    item.remove();
                }

                if (action === 'up' && item.previousElementSibling) {
                    list.insertBefore(item, item.previousElementSibling);
                }

                if (action === 'down' && item.nextElementSibling) {
                    list.insertBefore(item.nextElementSibling, item);
                }

                refreshItems();
            });

            refreshItems();
        })();
    </script>
    <script>
        (() => {
            const containers = {
                audience: document.getElementById('audience-items'),
                faq: document.getElementById('faq-items'),
            };

            const fields = {
                audience: index => `
                    <div class="content-item rounded-2xl border border-white/10 bg-[#060e24]/55 p-4">
                        <div class="grid gap-3 md:grid-cols-[1fr_2fr_auto]">
                            <input name="landing[audience][items][${index}][title]" aria-label="عنوان مخاطب" placeholder="عنوان" class="rounded-xl border border-white/10 bg-[#060e24] px-3 py-2.5 text-sm text-white outline-none focus:border-rose-400">
                            <textarea name="landing[audience][items][${index}][description]" rows="2" aria-label="توضیح مخاطب" placeholder="توضیح" class="resize-y rounded-xl border border-white/10 bg-[#060e24] px-3 py-2.5 text-sm text-white outline-none focus:border-rose-400"></textarea>
                            <button type="button" data-remove-item class="rounded-xl border border-rose-400/30 px-3 text-xs font-bold text-rose-200">حذف</button>
                        </div>
                    </div>`,
                faq: index => `
                    <div class="content-item rounded-2xl border border-white/10 bg-[#060e24]/55 p-4">
                        <div class="grid gap-3 md:grid-cols-[1fr_auto]">
                            <input name="landing[faq][items][${index}][question]" aria-label="سوال" placeholder="سوال" class="rounded-xl border border-white/10 bg-[#060e24] px-3 py-2.5 text-sm text-white outline-none focus:border-rose-400">
                            <button type="button" data-remove-item class="rounded-xl border border-rose-400/30 px-3 text-xs font-bold text-rose-200">حذف</button>
                            <textarea name="landing[faq][items][${index}][answer]" rows="3" aria-label="پاسخ" placeholder="پاسخ" class="resize-y rounded-xl border border-white/10 bg-[#060e24] px-3 py-2.5 text-sm text-white outline-none focus:border-rose-400 md:col-span-2"></textarea>
                        </div>
                    </div>`,
            };

            document.querySelectorAll('[data-add-item]').forEach(button => {
                button.addEventListener('click', () => {
                    const type = button.dataset.addItem;
                    const container = containers[type];

                    if (container.children.length >= (type === 'faq' ? 15 : 12)) {
                        return;
                    }

                    container.insertAdjacentHTML('beforeend', fields[type](container.children.length));
                });
            });

            document.addEventListener('click', event => {
                const button = event.target.closest('[data-remove-item]');

                if (button) {
                    button.closest('.content-item').remove();
                }
            });
        })();
    </script>
@endsection
