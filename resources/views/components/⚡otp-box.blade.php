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

<div
    x-data
    x-on:otp-code-received.window="if ($refs.otpCode) { $refs.otpCode.value = $event.detail; $refs.otpCode.dispatchEvent(new Event('input', { bubbles: true })); $refs.otpForm.requestSubmit(); } else { window.pendingOtpCode = $event.detail; }"
>
    <h2 class="text-xl font-bold">{{ __('event.cta') }}</h2>

    @if (! $sent)
        <form wire:submit="send" x-on:submit.capture="window.requestOtpAutofill()" class="mt-4 flex gap-2">
            <input wire:model="phone" type="tel" inputmode="numeric" autocomplete="tel" class="w-full rounded-xl bg-slate-950 p-3 text-center" placeholder="۰۹۱۲۳۴۵۶۷۸۹">
            <button class="rounded-xl bg-rose-600 px-5 font-bold">دریافت کد</button>
        </form>
    @else
        <form wire:submit="verify" x-ref="otpForm" x-on:submit="window.stopOtpAutofill()" class="mt-4">
            <p class="mb-3 text-sm text-slate-300">{{ __('event.otp_sent') }}</p>
            <input
                wire:model="code"
                x-ref="otpCode"
                x-init="$nextTick(() => { if (window.pendingOtpCode) { window.dispatchEvent(new CustomEvent('otp-code-received', { detail: window.pendingOtpCode })); window.pendingOtpCode = null; } })"
                type="text"
                inputmode="numeric"
                autocomplete="one-time-code"
                pattern="[0-9]*"
                class="w-full rounded-xl bg-slate-950 p-3 text-center"
            >
            <button class="mt-3 w-full rounded-xl bg-rose-600 p-3 font-bold">تأیید کد</button>
        </form>
    @endif

    @error('phone')<p class="mt-2 text-rose-300">{{ $message }}</p>@enderror
    @error('code')<p class="mt-2 text-rose-300">{{ $message }}</p>@enderror

    <script>
        window.requestOtpAutofill = () => {
            if (! ('OTPCredential' in window) || ! navigator.credentials) {
                return;
            }

            window.stopOtpAutofill?.();

            const controller = new AbortController();
            window.otpAutofillController = controller;
            window.setTimeout(() => controller.abort(), 120000);

            navigator.credentials.get({
                otp: { transport: ['sms'] },
                signal: controller.signal,
            }).then((credential) => {
                if (! credential?.code) {
                    return;
                }

                window.stopOtpAutofill();
                window.dispatchEvent(new CustomEvent('otp-code-received', { detail: credential.code }));
            }).catch(() => {});
        };

        window.stopOtpAutofill = () => {
            window.otpAutofillController?.abort();
            window.otpAutofillController = null;
        };
    </script>
</div>
