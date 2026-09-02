<?php

namespace App\Http\Controllers;

use App\Models\Blogger;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
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
            'bloggers' => ($isBlogger ? collect([$actor]) : Blogger::query()->where('is_active', true)->get())
                ->map(fn (Blogger $blogger): array => [
                    'code' => $blogger->code,
                    'name' => $blogger->name,
                    'phone' => $blogger->phone,
                    'active' => $blogger->is_active,
                ])->values(),
            'admins' => $isBlogger ? collect() : User::query()->where('is_active', true)->get()
                ->map(fn (User $user): array => [
                    'name' => $user->name,
                    'user' => $user->username,
                    'role' => $user->role->value,
                ])->values(),
            'templates' => [],
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

        return $this->design('Landing.dc.html', [
            'علی صبوری' => $blogger->name,
            'a10' => $blogger->code,
        ]);
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
            .'this.write(\'genavehei.smsTemplates\', serverState.templates);';

        $html = str_replace('componentDidMount() {', 'componentDidMount() {'.$bootstrap, $html);
        $html = str_replace('authed: false, me: null, user:', 'authed: true, me: '.Js::from($identity).', user:', $html);
        $html = str_replace(['if (!bloggers || !bloggers.length)', 'if (!admins || !admins.length)', 'if (!templates || !templates.length)', 'if (!regs || !regs.length)'], 'if (false)', $html);
        $html = str_replace('doLogout: () => this.setState({ authed: false, me: null, user: \'\', pass: \'\', selected: null }),', 'doLogout: () => window.panelLogout(),', $html);
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

        $logout = '<script>window.panelLogout=()=>fetch('.Js::from(route('panel.logout')).',{method:"POST",headers:{"X-CSRF-TOKEN":'.Js::from(csrf_token()).'}}).then(()=>location.assign('.Js::from(route('panel.login')).'));</script>';
        $html = str_replace('</head>', $logout.'<script type="module" src="'.Vite::asset('resources/js/app.js').'"></script></head>', $html);

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
