<?php

use Livewire\Component;

new class extends Component
{
    public string $phone = ''; public string $code = ''; public ?int $bloggerId = null; public bool $sent = false;
    protected function rules(): array { return ['phone'=>['required','regex:/^09\d{9}$/'],'code'=>['required','digits:4']]; }
    public function send(): void { $this->validateOnly('phone'); app(\App\Actions\RequestOtp::class)->handle($this->phone, request()->ip()); $this->sent=true; session()->put('otp_phone', app(\App\Support\NormalizeIranianPhone::class)->handle($this->phone)); }
    public function verify(): void { $this->validateOnly('code'); app(\App\Actions\VerifyOtp::class)->handle((string) session('otp_phone'),$this->code); session()->put('otp_verified_phone',session('otp_phone')); $this->dispatch('otp-verified'); }
};
?>

<div><h2 class="text-xl font-bold">{{ __('event.cta') }}</h2>@if (! $sent)<form wire:submit="send" class="mt-4 flex gap-2"><input wire:model="phone" inputmode="numeric" class="w-full rounded-xl bg-slate-950 p-3 text-center" placeholder="۰۹۱۲۳۴۵۶۷۸۹"><button class="rounded-xl bg-rose-600 px-5 font-bold">دریافت کد</button></form>@else<form wire:submit="verify" class="mt-4"><p class="mb-3 text-sm text-slate-300">{{ __('event.otp_sent') }}</p><input wire:model="code" inputmode="numeric" class="w-full rounded-xl bg-slate-950 p-3 text-center"><button class="mt-3 w-full rounded-xl bg-rose-600 p-3 font-bold">تأیید کد</button></form>@endif @error('phone')<p class="mt-2 text-rose-300">{{ $message }}</p>@enderror @error('code')<p class="mt-2 text-rose-300">{{ $message }}</p>@enderror</div>
