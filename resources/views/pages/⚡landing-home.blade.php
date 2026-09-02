<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="min-h-screen bg-[#0a1531] text-white" dir="rtl"><main class="mx-auto max-w-6xl px-4 py-12"><p class="text-rose-300">{{ __('event.subtitle') }}</p><h1 class="mt-4 max-w-3xl text-3xl font-black leading-relaxed">{{ __('event.title') }}</h1><p class="mt-4 text-slate-300">{{ __('event.date') }} · حضور رایگان</p><div class="mt-8 max-w-xl rounded-3xl border border-white/15 bg-white/5 p-6"><livewire:otp-box /></div><section class="mt-16"><h2 class="text-2xl font-bold">چهار چیزی که با خودت از سالن بیرون می‌بری</h2><div class="mt-6 grid gap-4 md:grid-cols-4"><p class="rounded-2xl bg-white/5 p-5">برنامه‌ریزی واقعی</p><p class="rounded-2xl bg-white/5 p-5">تکنیک تست‌زنی</p><p class="rounded-2xl bg-white/5 p-5">امتحانات نهایی</p><p class="rounded-2xl bg-white/5 p-5">انگیزه ماندگار</p></div></section><div class="mt-12"><livewire:registration-form /></div></main></div>
