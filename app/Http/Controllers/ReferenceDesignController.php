<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Blogger;
use App\Models\Registration;
use App\Models\SmsTemplate;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Js;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReferenceDesignController extends Controller
{
    private const EventStartsAtKey = 'event_starts_at';

    private const LandingAgendaKey = 'landing_agenda';

    private const LandingBenefitsKey = 'landing_benefits';

    private const LandingContentKey = 'landing_content';

    private const LandingTeachersKey = 'landing_teachers';

    private const SeoKey = 'site_seo';

    private const PublicSettingsCacheKey = 'public-landing:settings';

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

        $eventStartsAt = $this->eventStartsAt();
        $benefits = $this->landingBenefits();
        $agenda = $this->landingAgenda();
        $teachers = $this->landingTeachers();
        $landing = $this->landingContent();

        return $this->design('Landing.dc.html', [
            'علی صبوری' => $blogger->name,
            'a10' => $blogger->code,
            '۲۵ شهریور ۱۴۰۷' => $this->formatPersianDate($eventStartsAt),
            '{{ eventStartsAt }}' => (string) ($eventStartsAt->getTimestamp() * 1000),
            '{{ landingContent }}' => base64_encode(json_encode($landing, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)),
            '{{ provinceCities }}' => base64_encode(json_encode(config('province_cities'), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)),
            '{{ otpRequestUrl }}' => route('otp.store'),
            '{{ otpVerifyUrl }}' => route('otp.verify'),
            '{{ otpLogoutUrl }}' => route('otp.logout'),
            '{{ registrationStateUrl }}' => route('landing.registration-state', $blogger),
            '{{ registrationStoreUrl }}' => route('landing.registrations.store', $blogger),
            '{{ myTicketUrl }}' => route('landing.ticket', $blogger),
            '{{ csrfToken }}' => csrf_token(),
            '{{ landingBenefitsEyebrow }}' => $benefits['eyebrow'],
            '{{ landingBenefitsTitle }}' => $benefits['title'],
            '{{ landingBenefit0Title }}' => $benefits['items'][0]['title'],
            '{{ landingBenefit0Description }}' => $benefits['items'][0]['description'],
            '{{ landingBenefit1Title }}' => $benefits['items'][1]['title'],
            '{{ landingBenefit1Description }}' => $benefits['items'][1]['description'],
            '{{ landingBenefit2Title }}' => $benefits['items'][2]['title'],
            '{{ landingBenefit2Description }}' => $benefits['items'][2]['description'],
            '{{ landingBenefit3Title }}' => $benefits['items'][3]['title'],
            '{{ landingBenefit3Description }}' => $benefits['items'][3]['description'],
            '{{ landingAgendaEyebrow }}' => $agenda['eyebrow'],
            '{{ landingAgendaTitle }}' => $agenda['title'],
            '{{ landingAgendaItems }}' => base64_encode(json_encode($agenda['items'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)),
            '{{ landingTeachersContent }}' => base64_encode(json_encode($teachers, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)),
        ]);
    }

    public function ticket(Request $request, Registration $registration): Response
    {
        return $this->design('Ticket.dc.html', [
            'زهرا محمدی' => $registration->full_name,
            'رضا محمدی' => $registration->guardian_name,
            '09121234567' => $registration->guardian_phone,
            'فرزانگان' => $registration->school,
            'تهران' => $registration->city,
            '۱۸.۷۵' => (string) $registration->gpa,
            'سعادت‌آباد' => $registration->area,
            '۲۵ شهریور ۱۴۰۷' => $this->formatPersianDate($this->eventStartsAt()),
            'پایه دوازدهم' => 'پایه '.$registration->grade->label(),
            'رشته تجربی' => 'رشته '.$registration->field->label(),
            'a10-1' => $registration->ticket_code,
            '{{ landingUrl }}' => $registration->blogger ? route('landing', $registration->blogger) : route('home'),
            '{{ ticketActionsDisplay }}' => $request->boolean('render') ? 'none' : 'flex',
            '{{ ticketCode }}' => $registration->ticket_code,
        ]);
    }

    public function support(): Response
    {
        return response(File::get(base_path('design/support.js')), 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function asset(string $asset): BinaryFileResponse
    {
        abort_unless(in_array($asset, ['logo-genavehei.png', 'ostad-portrait.png'], true), 404);

        return response()->file(base_path('design/assets/'.$asset), [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    public function teacherImage(string $image): BinaryFileResponse
    {
        abort_unless(preg_match('/^[A-Za-z0-9_-]+\.(?:jpg|jpeg|png|webp)$/', $image) === 1, 404);

        $path = 'teachers/'.$image;

        abort_unless(Storage::disk('public')->exists($path), 404);

        return response()->file(Storage::disk('public')->path($path), [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    public function seoImage(string $image): BinaryFileResponse
    {
        abort_unless(preg_match('/^[A-Za-z0-9_-]+\.(?:jpg|jpeg|png|webp)$/', $image) === 1, 404);

        $path = 'seo/'.$image;

        abort_unless(Storage::disk('public')->exists($path), 404);

        return response()->file(Storage::disk('public')->path($path), [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    /** @param array<string, string> $replacements */
    private function design(string $file, array $replacements = []): Response
    {
        $html = File::get(base_path('design/'.$file));

        foreach ($replacements as $search => $replacement) {
            $html = str_replace($search, e($replacement), $html);
        }

        $supportUrl = route('design.support');
        $appScript = '<script src="'.Vite::asset('resources/js/app.js').'"></script>';
        $html = str_replace('./support.js', $supportUrl, $html);
        $html = str_replace('<script src="'.$supportUrl.'"></script>', '__APP_SCRIPT__<script src="'.$supportUrl.'"></script>', $html);
        $html = str_replace('assets/', url('/design/assets/').'/', $html);
        $html = str_replace('__APP_SCRIPT__', $appScript, $html);
        $html = str_replace('</head>', $this->seoMeta().'</head>', $html);

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    private function seoMeta(): string
    {
        $seo = json_decode((string) ($this->publicSettings()[self::SeoKey] ?? ''), true);
        $seo = is_array($seo) ? array_replace(EventSettingsController::defaultSeo(), $seo) : EventSettingsController::defaultSeo();
        $image = ! empty($seo['image_path'])
            ? route('design.seo-image', ['image' => basename($seo['image_path'])])
            : url('/design/assets/logo-genavehei.png');

        return '<title>'.e($seo['site_title']).'</title>'
            .'<meta name="description" content="'.e($seo['description']).'">'
            .'<meta property="og:type" content="website">'
            .'<meta property="og:title" content="'.e($seo['share_title']).'">'
            .'<meta property="og:description" content="'.e($seo['share_description']).'">'
            .'<meta property="og:image" content="'.e($image).'">'
            .'<meta name="twitter:card" content="summary_large_image">'
            .'<meta name="twitter:title" content="'.e($seo['share_title']).'">'
            .'<meta name="twitter:description" content="'.e($seo['share_description']).'">'
            .'<meta name="twitter:image" content="'.e($image).'">';
    }

    private function eventStartsAt(): CarbonImmutable
    {
        $timestamp = $this->publicSettings()[self::EventStartsAtKey] ?? null;

        return $timestamp
            ? CarbonImmutable::createFromTimestamp((int) $timestamp, config('app.timezone'))
            : CarbonImmutable::create(2028, 9, 15, 9, 0, 0, config('app.timezone'));
    }

    /** @return array{eyebrow: string, title: string, items: array<int, array{title: string, description: string}>} */
    private function landingBenefits(): array
    {
        $benefits = json_decode((string) ($this->publicSettings()[self::LandingBenefitsKey] ?? ''), true);

        return is_array($benefits) && count($benefits['items'] ?? []) === 4
            ? $benefits
            : [
                'eyebrow' => 'چرا باید در این گردهمایی باشی؟',
                'title' => 'چهار چیزی که با خودت از سالن بیرون می‌بری',
                'items' => [
                    ['title' => 'برنامه‌ریزی واقعی', 'description' => 'یک برنامه هفتگی که با مدرسه، آزمون و زندگی واقعی‌ات جور دربیاید — نه جدول‌های آرمانی.'],
                    ['title' => 'تکنیک تست‌زنی', 'description' => 'روش‌های مدیریت زمان و انتخاب تست در کنکور ریاضی و تجربی، با حل زنده روی صحنه.'],
                    ['title' => 'امتحانات نهایی', 'description' => 'تأثیر معدل، نقشه مطالعه نهایی‌ها و اینکه کجا باید بین نهایی و کنکور تعادل بسازی.'],
                    ['title' => 'انگیزه‌ی ماندگار', 'description' => 'هم‌مسیر شدن با هزاران داوطلب دیگر؛ همان چیزی که تنهایی مطالعه‌کردن از تو گرفته است.'],
                ],
            ];
    }

    /** @return array{eyebrow: string, title: string, items: array<int, array{time: string, title: string}>} */
    private function landingAgenda(): array
    {
        $agenda = json_decode((string) ($this->publicSettings()[self::LandingAgendaKey] ?? ''), true);

        return is_array($agenda) && count($agenda['items'] ?? []) >= 1 && count($agenda['items'] ?? []) <= 20
            ? $agenda
            : [
                'eyebrow' => 'سرفصل‌های روز سمینار',
                'title' => 'از صبح تا عصر، بدون حرف تکراری',
                'items' => [
                    ['time' => '۰۹:۰۰', 'title' => 'افتتاحیه و نقشه راه سال تحصیلی'],
                    ['time' => '۱۰:۳۰', 'title' => 'تکنیک‌های مطالعه و تست‌زنی — ریاضی و تجربی'],
                    ['time' => '۱۳:۰۰', 'title' => 'مدیریت استرس، خواب و تمرکز'],
                    ['time' => '۱۵:۰۰', 'title' => 'پرسش و پاسخ زنده با استاد گناوه‌ای'],
                ],
            ];
    }

    /** @return array{eyebrow: string, title: string, description: string, items: array<int, array{name: string, subject: string, photo: string}>} */
    private function landingTeachers(): array
    {
        $teachers = json_decode((string) ($this->publicSettings()[self::LandingTeachersKey] ?? ''), true);
        $teachers = is_array($teachers) && count($teachers['items'] ?? []) >= 1 && count($teachers['items'] ?? []) <= 20
            ? $teachers
            : EventSettingsController::defaultTeachers();

        return [
            'eyebrow' => $teachers['eyebrow'],
            'title' => $teachers['title'],
            'description' => $teachers['description'],
            'items' => collect($teachers['items'])->map(fn (array $teacher): array => [
                'name' => $teacher['name'],
                'subject' => $teacher['subject'],
                'photo' => ! empty($teacher['photo_path'])
                    ? route('design.teacher-image', ['image' => basename($teacher['photo_path'])])
                    : url('/design/assets/ostad-portrait.png'),
            ])->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function landingContent(): array
    {
        $landing = json_decode((string) ($this->publicSettings()[self::LandingContentKey] ?? ''), true);

        return is_array($landing) ? array_replace(EventSettingsController::defaultLanding(), $landing) : EventSettingsController::defaultLanding();
    }

    /** @return array<string, string> */
    private function publicSettings(): array
    {
        return Cache::remember(self::PublicSettingsCacheKey, now()->addHour(), fn (): array => DB::table('settings')
            ->whereIn('key', [
                self::EventStartsAtKey,
                self::LandingAgendaKey,
                self::LandingBenefitsKey,
                self::LandingContentKey,
                self::LandingTeachersKey,
                self::SeoKey,
            ])
            ->pluck('value', 'key')
            ->all());
    }

    private function formatPersianDate(CarbonInterface $date): string
    {
        $formatter = new \IntlDateFormatter(
            'fa_IR@calendar=persian',
            \IntlDateFormatter::LONG,
            \IntlDateFormatter::NONE,
            config('app.timezone'),
            \IntlDateFormatter::TRADITIONAL,
            'd MMMM y',
        );

        return $formatter->format($date) ?: $date->locale('fa')->isoFormat('D MMMM YYYY');
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
        $supportUrl = route('design.support');
        $appScript = '<script src="'.Vite::asset('resources/js/app.js').'"></script>';
        $html = str_replace('./support.js', $supportUrl, $html);
        $html = str_replace('<script src="'.$supportUrl.'"></script>', '__APP_SCRIPT__<script src="'.$supportUrl.'"></script>', $html);
        $html = str_replace('assets/', url('/design/assets/').'/', $html);
        $html = str_replace('__APP_SCRIPT__', $appScript, $html);

        $notifications = <<<'HTML'
<style>#panel-notification-stack{position:fixed;top:18px;right:18px;z-index:2147483647;display:flex;max-width:min(420px,calc(100vw - 36px));flex-direction:column;gap:10px;direction:rtl}.panel-notification{border:1px solid rgba(255,255,255,.2);border-radius:14px;padding:13px 16px;color:#fff;font:700 13px/1.8 system-ui,sans-serif;box-shadow:0 18px 42px rgba(0,0,0,.35);transition:opacity .25s ease,transform .25s ease}.panel-notification--success{background:#07683e}.panel-notification--error{background:#9d1d29}.panel-notification--leaving{opacity:0;transform:translateY(-8px)}</style><script>window.panelNotify=(message,type="success")=>{let stack=document.getElementById("panel-notification-stack");if(!stack){stack=document.createElement("div");stack.id="panel-notification-stack";stack.setAttribute("aria-live","polite");document.body.append(stack)}const notification=document.createElement("div");notification.className="panel-notification panel-notification--"+(type==="error"?"error":"success");notification.textContent=message||"عملیات با موفقیت انجام شد.";stack.append(notification);window.setTimeout(()=>{notification.classList.add("panel-notification--leaving");window.setTimeout(()=>notification.remove(),250)},5000)};window.panelActionSuccess=()=>{window.panelNotify("تغییرات با موفقیت ذخیره شد.");window.setTimeout(()=>location.reload(),700)};</script>
HTML;
        $logout = '<script>window.panelError=async r=>{const b=await r.json().catch(()=>null);return Object.values((b&&b.errors)||{}).flat().join(" ")||"عملیات انجام نشد."};window.panelLogout=()=>fetch('.Js::from(route('panel.logout')).',{method:"POST",headers:{"X-CSRF-TOKEN":'.Js::from(csrf_token()).'}}).then(()=>location.assign('.Js::from(route('panel.login')).'));window.panelRegistrationAction=(code,suffix,data,method)=>fetch('.Js::from(url('/panel/r').'/').'+encodeURIComponent(code)+suffix,{method,headers:{"Content-Type":"application/json","Accept":"application/json","X-CSRF-TOKEN":'.Js::from(csrf_token()).'},body:JSON.stringify(data)}).then(async r=>{if(!r.ok)throw new Error(await window.panelError(r));window.panelActionSuccess()});window.panelBloggerAction=(code,suffix,data,method)=>fetch('.Js::from(url('/panel/bloggers/')).'+(code?encodeURIComponent(code):\'\')+suffix,{method,headers:{"Content-Type":"application/json","Accept":"application/json","X-CSRF-TOKEN":'.Js::from(csrf_token()).'},body:JSON.stringify(data)}).then(async r=>{if(!r.ok)throw new Error(await window.panelError(r));window.panelActionSuccess()});window.panelBloggerAvatar=(code,file)=>{const body=new FormData();body.append("avatar",file);return fetch('.Js::from(url('/panel/bloggers/')).'+encodeURIComponent(code)+"/avatar",{method:"POST",headers:{"Accept":"application/json","X-CSRF-TOKEN":'.Js::from(csrf_token()).'},body}).then(async r=>{if(!r.ok)throw new Error(await window.panelError(r));window.panelActionSuccess()})};window.panelSmsTemplateAction=(id,data,method)=>fetch('.Js::from(url('/panel/sms-templates/')).'+(id?encodeURIComponent(id):\'\'),{method,headers:{"Content-Type":"application/json","Accept":"application/json","X-CSRF-TOKEN":'.Js::from(csrf_token()).'},body:JSON.stringify(data)}).then(async r=>{if(!r.ok)throw new Error(await window.panelError(r));window.panelActionSuccess()});window.panelAdminAction=(username,data,method)=>fetch('.Js::from(url('/panel/admins/')).'+(username?encodeURIComponent(username):\'\'),{method,headers:{"Content-Type":"application/json","Accept":"application/json","X-CSRF-TOKEN":'.Js::from(csrf_token()).'},body:JSON.stringify(data)}).then(async r=>{if(!r.ok)throw new Error(await window.panelError(r));window.panelActionSuccess()});window.panelActivityExport='.Js::from(route('panel.activity.export')).';</script>';
        $errorNotifier = '<script>window.panelNotifyError=message=>window.panelNotify(message||"عملیات انجام نشد.","error");["panelRegistrationAction","panelBloggerAction","panelBloggerAvatar","panelSmsTemplateAction","panelAdminAction"].forEach(name=>{const action=window[name];window[name]=async(...args)=>{try{return await action(...args)}catch(error){window.panelNotifyError(error.message);throw error}}});window.addEventListener("unhandledrejection",event=>{if(event.reason instanceof Error){window.panelNotifyError(event.reason.message);event.preventDefault()}});</script>';
        $html = str_replace('</head>', $notifications.$logout.$errorNotifier.'</head>', $html);

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
