<?php

use Livewire\Component;

new class extends Component
{
    public \App\Models\Blogger $blogger;
    public function mount(\App\Models\Blogger $blogger): void { abort_unless($blogger->is_active, 404); if (! session()->has('ref_blogger_id')) session()->put('ref_blogger_id', $blogger->id); $this->blogger = $blogger; }
};
?>

<div class="min-h-screen bg-[#0a1531] text-white" dir="rtl"><main class="mx-auto max-w-6xl px-4 py-12"><p class="text-rose-300">{{ __('event.subtitle') }}</p><h1 class="mt-4 max-w-3xl text-3xl font-black leading-relaxed">{{ __('event.title') }}</h1><p class="mt-4 text-slate-300">با دعوت {{ $blogger->name }} · {{ __('event.date') }}</p><div class="mt-8 max-w-xl rounded-3xl border border-white/15 bg-white/5 p-6"><livewire:otp-box :blogger-id="$blogger->id" /></div><div class="mt-12"><livewire:registration-form /></div></main></div>
