<?php

use Livewire\Component;

new class extends Component
{
    public string $username = '';
    public string $password = '';

    public function login(): void
    {
        $credentials = $this->validate(['username' => ['required', 'string'], 'password' => ['required', 'string']]);

        if (auth('web')->attempt([...$credentials, 'is_active' => true])) {
            session()->regenerate();
            $this->redirectRoute('panel.registrations');

            return;
        }

        if (! auth('blogger')->attempt(['slug' => $credentials['username'], 'password' => $credentials['password'], 'is_active' => true])) {
            $this->addError('username', 'نام کاربری یا رمز عبور صحیح نیست.');

            return;
        }

        session()->regenerate();
        $this->redirectRoute('panel.registrations');
    }
};
?>

<div style="min-height:100vh; background:radial-gradient(760px 460px at 82% -12%, rgba(216,31,42,.2), transparent 62%), #060d20; color:#eaf0ff" dir="rtl">
    <style>html,body{margin:0;padding:0;background:#060d20}body{font-family:'Vazirmatn',system-ui,sans-serif;direction:rtl;-webkit-font-smoothing:antialiased}*{box-sizing:border-box}input,button{font-family:inherit}input::placeholder{color:#66739a}@keyframes riseIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}</style>
    <div style="min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px">
        <div style="width:100%; max-width:400px; background:linear-gradient(180deg, rgba(255,255,255,.09), rgba(255,255,255,.04)); border:1px solid rgba(255,255,255,.15); border-radius:24px; padding:28px; box-shadow:0 40px 80px -50px rgba(0,0,0,.95); animation:riseIn .4s ease both">
            <div style="display:flex; align-items:center; gap:11px; margin-bottom:24px"><img src="{{ asset('images/logo-genavehei.png') }}" alt="" style="width:46px; height:46px; object-fit:contain; background:#fff; border-radius:13px; padding:3px"><div style="line-height:1.4"><div style="font-weight:900; font-size:15px; color:#fff">پنل مدیریت گردهمایی</div><div style="font-size:11.5px; color:#8b98ba">گروه آموزشی استاد محسن گناوه‌ای</div></div></div>
            <form wire:submit="login"><label style="display:flex; flex-direction:column; gap:7px; margin-bottom:12px"><span style="font-size:12.5px; font-weight:700; color:#cdd7f0">نام کاربری</span><input wire:model="username" placeholder="admin" style="direction:ltr; background:rgba(6,14,36,.75); border:1.5px solid rgba(255,255,255,.16); border-radius:13px; padding:13px 14px; color:#fff; font-size:15px; outline:none"></label><label style="display:flex; flex-direction:column; gap:7px; margin-bottom:16px"><span style="font-size:12.5px; font-weight:700; color:#cdd7f0">رمز عبور</span><input wire:model="password" type="password" placeholder="••••" style="direction:ltr; background:rgba(6,14,36,.75); border:1.5px solid rgba(255,255,255,.16); border-radius:13px; padding:13px 14px; color:#fff; font-size:15px; outline:none"></label>@error('username')<div style="margin-bottom:12px; font-size:13px; font-weight:600; color:#ff8d96">{{ $message }}</div>@enderror<button style="width:100%; background:#d81f2a; color:#fff; font-weight:800; font-size:15.5px; border:none; border-radius:13px; padding:14px; cursor:pointer; box-shadow:0 14px 32px -14px rgba(216,31,42,.9)">ورود به پنل</button></form>
        </div>
    </div>
</div>
