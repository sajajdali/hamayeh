<?php

use App\Enums\UserRole;
use App\Models\Blogger;
use App\Support\NormalizeIranianPhone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public string $portal = 'admin';

    public string $title = 'ورود مدیریت';
    public string $username = '';
    public string $password = '';

    public function mount(?string $portal = null): void
    {
        $portal ??= match (request()->route()?->getName()) {
            'blogger.login' => 'blogger',
            'sales-manager.login' => 'sales_manager',
            default => 'admin',
        };

        [$this->portal, $this->title] = match ($portal) {
            'blogger.login' => ['blogger', 'ورود بلاگرها'],
            'blogger' => ['blogger', 'ورود بلاگرها'],
            'sales_manager' => ['sales_manager', 'ورودی مدیر'],
            default => ['admin', 'ورود مدیریت'],
        };
    }

    public function login(): void
    {
        $credentials = $this->validate(
            ['username' => ['required', 'string'], 'password' => ['required', 'string']],
            [
                'username.required' => 'نام کاربری را وارد کنید.',
                'password.required' => 'رمز عبور را وارد کنید.',
            ],
        );

        auth('web')->logout();
        auth('blogger')->logout();

        $authenticated = match ($this->portal) {
            'blogger' => $this->authenticateBlogger($credentials),
            'sales_manager' => auth('web')->attempt([
                ...$credentials,
                'role' => UserRole::Mid->value,
                'is_active' => true,
            ]),
            default => auth('web')->attempt([
                ...$credentials,
                'role' => UserRole::Super->value,
                'is_active' => true,
            ]),
        };

        if (! $authenticated) {
            $this->addError('login', 'نام کاربری یا رمز عبور صحیح نیست.');

            return;
        }

        session()->regenerate();
        session()->put('login_portal', $this->portal);
        $this->redirectRoute('panel.registrations');
    }

    /** @param array{username: string, password: string} $credentials */
    private function authenticateBlogger(array $credentials): bool
    {
        $identifier = trim($credentials['username']);
        $phone = app(NormalizeIranianPhone::class)->handle($identifier);

        $blogger = Blogger::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($identifier, $phone): void {
                $query->where('slug', mb_strtolower($identifier))
                    ->orWhere('code', mb_strtolower($identifier))
                    ->orWhere('phone', $phone);
            })
            ->first();

        if (! $blogger || ! $blogger->password || ! Hash::check($credentials['password'], $blogger->password)) {
            return false;
        }

        auth('blogger')->login($blogger);

        return true;
    }
};
?>

<div style="min-height:100vh; background:radial-gradient(760px 460px at 82% -12%, rgba(216,31,42,.2), transparent 62%), #060d20; color:#eaf0ff" dir="rtl">
    <style>html,body{margin:0;padding:0;background:#060d20}body{font-family:'Vazirmatn',system-ui,sans-serif;direction:rtl;-webkit-font-smoothing:antialiased}*{box-sizing:border-box}input,button{font-family:inherit}input::placeholder{color:#66739a}@keyframes riseIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}@keyframes spin{to{transform:rotate(360deg)}}</style>
    <div style="min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px">
        <div style="width:100%; max-width:400px; background:linear-gradient(180deg, rgba(255,255,255,.09), rgba(255,255,255,.04)); border:1px solid rgba(255,255,255,.15); border-radius:24px; padding:28px; box-shadow:0 40px 80px -50px rgba(0,0,0,.95); animation:riseIn .4s ease both">
            <div style="display:flex; align-items:center; gap:11px; margin-bottom:24px"><img src="{{ asset('images/logo-genavehei.png') }}" alt="" style="width:46px; height:46px; object-fit:contain; background:#fff; border-radius:13px; padding:3px"><div style="line-height:1.4"><div style="font-weight:900; font-size:15px; color:#fff">{{ $title }}</div><div style="font-size:11.5px; color:#8b98ba">گروه آموزشی استاد محسن گناوه‌ای</div></div></div>
            <form wire:submit="login">
                <label style="display:flex; flex-direction:column; gap:7px; margin-bottom:12px">
                    <span style="font-size:12.5px; font-weight:700; color:#cdd7f0">{{ $portal === 'blogger' ? 'شماره موبایل یا نام کاربری' : 'نام کاربری' }}</span>
                    <input wire:model="username" placeholder="{{ $portal === 'blogger' ? 'شماره موبایل، شناسه یا کد بلاگر' : 'نام کاربری' }}" autocomplete="username" style="direction:ltr; background:rgba(6,14,36,.75); border:1.5px solid rgba(255,255,255,.16); border-radius:13px; padding:13px 14px; color:#fff; font-size:15px; outline:none">
                    @error('username')<span role="alert" style="font-size:13px; font-weight:600; color:#ff8d96">{{ $message }}</span>@enderror
                </label>
                <label style="display:flex; flex-direction:column; gap:7px; margin-bottom:16px">
                    <span style="font-size:12.5px; font-weight:700; color:#cdd7f0">رمز عبور</span>
                    <input wire:model="password" type="password" placeholder="••••" autocomplete="current-password" style="direction:ltr; background:rgba(6,14,36,.75); border:1.5px solid rgba(255,255,255,.16); border-radius:13px; padding:13px 14px; color:#fff; font-size:15px; outline:none">
                    @error('password')<span role="alert" style="font-size:13px; font-weight:600; color:#ff8d96">{{ $message }}</span>@enderror
                </label>
                @error('login')<div role="alert" style="margin-bottom:12px; font-size:13px; font-weight:600; color:#ff8d96">{{ $message }}</div>@enderror
                <button type="submit" wire:loading.attr="disabled" wire:target="login" style="width:100%; background:#d81f2a; color:#fff; font-weight:800; font-size:15.5px; border:none; border-radius:13px; padding:14px; cursor:pointer; box-shadow:0 14px 32px -14px rgba(216,31,42,.9)">
                    <span wire:loading.remove wire:target="login">ورود</span>
                    <span wire:loading.flex wire:target="login" style="align-items:center; justify-content:center; gap:8px"><span aria-hidden="true" style="width:17px; height:17px; border:2px solid rgba(255,255,255,.35); border-top-color:#fff; border-radius:999px; animation:spin .7s linear infinite"></span>در حال ورود...</span>
                </button>
            </form>
        </div>
    </div>
</div>
