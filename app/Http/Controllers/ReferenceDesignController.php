<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Blogger;
use App\Models\Registration;
use App\Models\SmsTemplate;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Js;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReferenceDesignController extends Controller
{
    public function admin(Request $request): Response
    {
        $actor = auth('web')->user() ?? auth('blogger')->user();

        abort_unless($actor instanceof Authenticatable, 403);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'blogger' => ['nullable', 'string', 'max:10'],
            'status' => ['nullable', 'in:pending,calling,approved,canceled'],
            'grade' => ['nullable', 'in:10,11,12,alumni'],
            'field' => ['nullable', 'in:math,science'],
            'when' => ['nullable', 'in:today,yesterday,week'],
        ]);

        $scoped = Registration::query()->visibleTo($actor);
        $filtered = clone $scoped;

        $filtered
            ->when($filters['q'] ?? null, fn ($query, string $queryText) => $query->where(function ($query) use ($queryText): void {
                $query->where('full_name', 'like', "%{$queryText}%")
                    ->orWhere('ticket_code', 'like', "%{$queryText}%")
                    ->orWhere('phone', 'like', "%{$queryText}%");
            }))
            ->when($filters['blogger'] ?? null, fn ($query, string $blogger) => $query->whereHas('blogger', fn ($query) => $query->where('code', $blogger)))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['grade'] ?? null, fn ($query, string $grade) => $query->where('grade', $grade))
            ->when($filters['field'] ?? null, fn ($query, string $field) => $query->where('field', $field))
            ->when($filters['when'] ?? null, function ($query, string $when): void {
                $range = match ($when) {
                    'today' => [now()->startOfDay(), now()->endOfDay()],
                    'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
                    'week' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
                };

                $query->whereBetween('created_at', $range);
            });

        $registrations = $filtered
            ->with(['blogger:id,name,code', 'activityLogs.actor'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $isBlogger = $actor instanceof Blogger;
        $state = [
            'bloggers' => ($isBlogger ? collect([$actor]) : Blogger::query()->get())
                ->map(fn (Blogger $blogger): array => [
                    'code' => $blogger->code,
                    'name' => $blogger->name,
                    'phone' => $blogger->phone,
                    'slug' => $blogger->slug,
                    'avatar' => $blogger->avatar_path ? Storage::disk('public')->url($blogger->avatar_path) : null,
                    'active' => $blogger->is_active,
                ])->values(),
            'admins' => $isBlogger ? collect() : User::query()->where('is_active', true)->get()
                ->map(fn (User $user): array => [
                    'name' => $user->name,
                    'user' => $user->username,
                    'role' => $user->role->value,
                ])->values(),
            'templates' => $isBlogger ? collect() : SmsTemplate::query()->where('is_active', true)->get()
                ->map(fn (SmsTemplate $template): array => ['id' => (string) $template->id, 'name' => $template->name, 'text' => $template->body])->values(),
            'regs' => $registrations->getCollection()->map(fn (Registration $registration): array => [
                'code' => $registration->ticket_code,
                'blogger' => $registration->blogger?->code,
                'name' => $registration->full_name,
                'grade' => $registration->grade->label(),
                'field' => $registration->field->label(),
                'school' => $registration->school,
                'city' => $registration->city,
                'province' => $registration->province,
                'area' => $registration->area,
                'gpa' => $registration->gpa,
                'phone' => $registration->phone,
                'guardian' => $registration->guardian_name,
                'guardianPhone' => $registration->guardian_phone,
                'status' => $registration->status->value,
                'at' => $registration->created_at->getTimestampMs(),
                'logs' => $registration->activityLogs->map(fn ($log): array => [
                    'at' => $log->created_at->getTimestampMs(),
                    'by' => $log->actor?->name ?? 'سامانه',
                    'type' => $log->type->value,
                    'text' => $log->body,
                ])->values(),
            ])->values(),
            'activityLogs' => $isBlogger ? collect() : ActivityLog::query()
                ->with(['actor', 'registration'])
                ->latest()
                ->limit(100)
                ->get()
                ->map(fn (ActivityLog $log): array => [
                    'at' => $log->created_at->getTimestampMs(),
                    'who' => $log->actor?->name ?? 'سامانه',
                    'type' => $log->type->value,
                    'text' => $log->body,
                    'regCode' => $log->registration?->ticket_code ?? '—',
                    'regName' => $log->registration?->full_name ?? '—',
                ])->values(),
        ];

        $statistics = [
            'total' => (clone $scoped)->count(),
            'today' => (clone $scoped)->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])->count(),
            'yesterday' => (clone $scoped)->whereBetween('created_at', [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()])->count(),
            'pending' => (clone $scoped)->where('status', 'pending')->count(),
            'approved' => (clone $scoped)->where('status', 'approved')->count(),
        ];

        $identity = $isBlogger
            ? ['name' => $actor->name, 'role' => 'blogger', 'code' => $actor->code]
            : ['name' => $actor->name, 'role' => $actor->role->value, 'user' => $actor->username];

        return $this->adminDesign($state, $identity, $statistics);
    }

    public function landing(Blogger $blogger): Response
    {
        abort_unless($blogger->is_active, 404);

        $html = Cache::remember('landing:blogger:'.$blogger->getKey(), now()->addHour(), fn (): string => $this->design('Landing.dc.html', [
            'علی صبوری' => $blogger->name,
            'a10' => $blogger->code,
        ])->getContent());

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public function ticket(Registration $registration): Response
    {
        return $this->design('Ticket.dc.html', [
            'زهرا محمدی' => $registration->full_name,
            'رضا محمدی' => $registration->guardian_name,
            '09121234567' => $registration->guardian_phone,
            'فرزانگان' => $registration->school,
            'تهران' => $registration->city,
            '۱۸.۷۵' => (string) $registration->gpa,
            'سعادت‌آباد' => $registration->area,
            'پایه دوازدهم' => 'پایه '.$registration->grade->label(),
            'رشته تجربی' => 'رشته '.$registration->field->label(),
            'a10-1' => $registration->ticket_code,
        ]);
    }

    public function support(): Response
    {
        return response(File::get(base_path('design/support.js')), 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
        ]);
    }

    public function asset(string $asset): BinaryFileResponse
    {
        abort_unless(in_array($asset, ['logo-genavehei.png', 'ostad-portrait.png'], true), 404);

        return response()->file(base_path('design/assets/'.$asset));
    }

    /** @param array<string, string> $replacements */
    private function design(string $file, array $replacements = []): Response
    {
        $html = File::get(base_path('design/'.$file));

        foreach ($replacements as $search => $replacement) {
            $html = str_replace($search, e($replacement), $html);
        }

        $html = str_replace('./support.js', route('design.support'), $html);
        $html = str_replace('assets/', url('/design/assets/').'/', $html);
        $html = str_replace('</head>', '<script type="module" src="'.Vite::asset('resources/js/app.js').'"></script></head>', $html);

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /** @param array<string, mixed> $state @param array<string, string> $identity @param array<string, int> $statistics */
    private function adminDesign(array $state, array $identity, array $statistics): Response
    {
        $html = File::get(base_path('design/Admin.dc.html'));
        $bootstrap = 'const serverState = '.Js::from($state).';'
            .'this.write(\'genavehei.bloggers\', serverState.bloggers);'
            .'this.write(\'genavehei.registrations\', serverState.regs);'
            .'this.write(\'genavehei.admins\', serverState.admins);'
            .'this.write(\'genavehei.smsTemplates\', serverState.templates);'
            .'this.setState({serverLogs: serverState.activityLogs});';

        $html = str_replace('componentDidMount() {', 'componentDidMount() {'.$bootstrap, $html);
        $html = str_replace('authed: false, me: null, user:', 'authed: true, me: '.Js::from($identity).', user:', $html);
        $html = str_replace(['if (!bloggers || !bloggers.length)', 'if (!admins || !admins.length)', 'if (!templates || !templates.length)', 'if (!regs || !regs.length)'], 'if (false)', $html);
        $html = str_replace('doLogout: () => this.setState({ authed: false, me: null, user: \'\', pass: \'\', selected: null }),', 'doLogout: () => window.panelLogout(),', $html);
        $phaseSixActions = "createBlogger = () => { const name = this.state.newName.trim(); const code = this.state.newCode.trim().toLowerCase(); const slug = (this.state.newSlug.trim() || code).toLowerCase(); if (!name) return this.setState({ formError: 'نام بلاگر را وارد کنید.' }); if (!/^[a-z0-9]{4}$/.test(code)) return this.setState({ formError: 'کد اختصاصی باید دقیقاً ۴ حرف یا رقم انگلیسی باشد.' }); if (!/^[a-z0-9_-]{2,24}$/.test(slug)) return this.setState({ formError: 'آدرس کوتاه معتبر نیست.' }); window.panelBloggerAction('', '', { name, code, slug, phone: this.state.newPhone, password: this.state.newPass }, 'POST').catch(error => this.setState({ formError: error.message })); };\n"
            ."toggleBloggerBackend = () => { const b = this.state.bloggers.find(b => b.code === this.state.openBlogger); if (b) window.panelBloggerAction(b.code, '/toggle', {}, 'PATCH'); };\n"
            ."deleteBloggerBackend = () => { const b = this.state.bloggers.find(b => b.code === this.state.openBlogger); if (b) window.panelBloggerAction(b.code, '', {}, 'DELETE'); };\n"
            ."uploadBloggerAvatar = (e) => { const b = this.state.bloggers.find(b => b.code === this.state.openBlogger); const file = e.target.files[0]; if (b && file) window.panelBloggerAvatar(b.code, file); };\n\n";
        $html = str_replace('  renderVals() {', '  '.$phaseSixActions.'  renderVals() {', $html);
        $html = str_replace([
            'addBlogger: this.add',
            'bg_toggle: this.toggleBlogger',
            'bg_confirmDelete: this.deleteBlogger',
            'bg_pickAvatar: bg ? this.pickAvatar(bg.code) : (() => {})',
        ], [
            'addBlogger: this.createBlogger',
            'bg_toggle: this.toggleBloggerBackend',
            'bg_confirmDelete: this.deleteBloggerBackend',
            'bg_pickAvatar: this.uploadBloggerAvatar',
        ], $html);
        $html = str_replace('return this.shortBase() + (b.slug || b.code);', 'return location.origin + \'/s/\' + b.code;', $html);
        $phaseFiveActions = "saveCall = () => { const d = this.current(); if (!d) return; window.panelRegistrationAction(d.code, '/calls', { result: this.state.callResult, note: this.state.callNote }, 'POST'); };\n"
            ."saveStatus = (status) => () => { const d = this.current(); if (!d) return; window.panelRegistrationAction(d.code, '/status', { status }, 'PUT'); };\n\n";
        $phaseSevenActions = "createSmsTemplate = () => window.panelSmsTemplateAction('', { name: this.state.tplName, body: this.state.tplText }, 'POST');\n"
            ."deleteSmsTemplate = (id) => () => window.panelSmsTemplateAction(id, {}, 'DELETE');\n"
            ."sendRegistrationSms = (recipient) => () => { const d = this.current(); if (d) window.panelRegistrationAction(d.code, '/sms', { template_id: this.state.smsTpl, recipient }, 'POST'); };\n\n";
        $html = str_replace('  renderVals() {', '  '.$phaseSevenActions.'  renderVals() {', $html);
        $html = str_replace(['addTemplate: this.addTemplate', 'sendToStudent: this.sendSms(\'student\')', 'sendToGuardian: this.sendSms(\'guardian\')', 'remove: this.removeTemplate(t.id)'], ['addTemplate: this.createSmsTemplate', 'sendToStudent: this.sendRegistrationSms(\'student\')', 'sendToGuardian: this.sendRegistrationSms(\'guardian\')', 'remove: this.deleteSmsTemplate(t.id)'], $html);
        $phaseEightActions = "createAdmin = () => window.panelAdminAction('', { name: this.state.adminName, username: this.state.adminUser, password: this.state.adminPass, role: this.state.adminRole }, 'POST');\n"
            ."deleteAdminBackend = (username) => () => window.panelAdminAction(username, {}, 'DELETE');\n\n";
        $html = str_replace('  renderVals() {', '  '.$phaseEightActions.'  renderVals() {', $html);
        $html = str_replace([
            'remove: this.removeAdmin(a.user)',
            'addAdmin: this.addAdmin',
            'exportLogs: this.exportLogs',
        ], [
            'remove: this.deleteAdminBackend(a.user)',
            'addAdmin: this.createAdmin',
            'exportLogs: () => location.assign(window.panelActivityExport)',
        ], $html);
        $html = str_replace("  allLogsList() {\n    const out = [];\n    this.state.regs.forEach(r => (Array.isArray(r.logs) ? r.logs : []).forEach(l => {\n      if (!l) return;\n      out.push({ at: l.at || 0, who: l.by || 'مدیر', type: l.type || 'status', text: String(l.text || ''), regCode: r.code, regName: r.name });\n    }));\n    return out.sort((a, b) => b.at - a.at);\n  }", "  allLogsList() {\n    return (this.state.serverLogs || []).slice().sort((a, b) => b.at - a.at);\n  }", $html);
        $html = str_replace('  renderVals() {', '  '.$phaseFiveActions.'  renderVals() {', $html);
        $html = str_replace([
            'addCall: this.logCall',
            'approve: this.setStatus(\'approved\')',
            'markCalling: this.setStatus(\'calling\')',
            'cancelReg: this.setStatus(\'canceled\')',
        ], [
            'addCall: this.saveCall',
            'approve: this.saveStatus(\'approved\')',
            'markCalling: this.saveStatus(\'calling\')',
            'cancelReg: this.saveStatus(\'canceled\')',
        ], $html);
        $html = str_replace([
            'kpiTotal: fa(scopedRegs.length)',
            'kpiToday: fa(inRange(scopedRegs, t0, t0 + DAY))',
            'kpiYesterday: fa(inRange(scopedRegs, t0 - DAY, t0))',
            'kpiPending: fa(scopedRegs.filter(r => (r.status || \'pending\') === \'pending\').length)',
            'kpiApproved: fa(scopedRegs.filter(r => r.status === \'approved\').length)',
        ], [
            'kpiTotal: fa('.$statistics['total'].')',
            'kpiToday: fa('.$statistics['today'].')',
            'kpiYesterday: fa('.$statistics['yesterday'].')',
            'kpiPending: fa('.$statistics['pending'].')',
            'kpiApproved: fa('.$statistics['approved'].')',
        ], $html);
        $html = str_replace('./support.js', route('design.support'), $html);
        $html = str_replace('assets/', url('/design/assets/').'/', $html);

        $logout = '<script>window.panelError=async r=>{const b=await r.json().catch(()=>null);return Object.values((b&&b.errors)||{}).flat().join(" ")||"عملیات انجام نشد."};window.panelLogout=()=>fetch('.Js::from(route('panel.logout')).',{method:"POST",headers:{"X-CSRF-TOKEN":'.Js::from(csrf_token()).'}}).then(()=>location.assign('.Js::from(route('panel.login')).'));window.panelRegistrationAction=(code,suffix,data,method)=>fetch('.Js::from(url('/panel/r/')).'+encodeURIComponent(code)+suffix,{method,headers:{"Content-Type":"application/json","Accept":"application/json","X-CSRF-TOKEN":'.Js::from(csrf_token()).'},body:JSON.stringify(data)}).then(async r=>{if(!r.ok)throw new Error(await window.panelError(r));location.reload()});window.panelBloggerAction=(code,suffix,data,method)=>fetch('.Js::from(url('/panel/bloggers/')).'+(code?encodeURIComponent(code):\'\')+suffix,{method,headers:{"Content-Type":"application/json","Accept":"application/json","X-CSRF-TOKEN":'.Js::from(csrf_token()).'},body:JSON.stringify(data)}).then(async r=>{if(!r.ok)throw new Error(await window.panelError(r));location.reload()});window.panelBloggerAvatar=(code,file)=>{const body=new FormData();body.append("avatar",file);return fetch('.Js::from(url('/panel/bloggers/')).'+encodeURIComponent(code)+"/avatar",{method:"POST",headers:{"Accept":"application/json","X-CSRF-TOKEN":'.Js::from(csrf_token()).'},body}).then(async r=>{if(!r.ok)throw new Error(await window.panelError(r));location.reload()})};window.panelSmsTemplateAction=(id,data,method)=>fetch('.Js::from(url('/panel/sms-templates/')).'+(id?encodeURIComponent(id):\'\'),{method,headers:{"Content-Type":"application/json","Accept":"application/json","X-CSRF-TOKEN":'.Js::from(csrf_token()).'},body:JSON.stringify(data)}).then(async r=>{if(!r.ok)throw new Error(await window.panelError(r));location.reload()});window.panelAdminAction=(username,data,method)=>fetch('.Js::from(url('/panel/admins/')).'+(username?encodeURIComponent(username):\'\'),{method,headers:{"Content-Type":"application/json","Accept":"application/json","X-CSRF-TOKEN":'.Js::from(csrf_token()).'},body:JSON.stringify(data)}).then(async r=>{if(!r.ok)throw new Error(await window.panelError(r));location.reload()});window.panelActivityExport='.Js::from(route('panel.activity.export')).';</script>';
        $errorNotifier = '<script>window.panelNotifyError=message=>window.alert(message||"عملیات انجام نشد.");["panelRegistrationAction","panelBloggerAction","panelBloggerAvatar","panelSmsTemplateAction","panelAdminAction"].forEach(name=>{const action=window[name];window[name]=async(...args)=>{try{return await action(...args)}catch(error){window.panelNotifyError(error.message);throw error}}});window.addEventListener("unhandledrejection",event=>{if(event.reason instanceof Error){window.panelNotifyError(event.reason.message);event.preventDefault()}});</script>';
        $html = str_replace('</head>', $logout.$errorNotifier.'<script type="module" src="'.Vite::asset('resources/js/app.js').'"></script></head>', $html);

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
