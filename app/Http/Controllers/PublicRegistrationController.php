<?php

namespace App\Http\Controllers;

use App\Actions\IssueRegistration;
use App\Actions\QueueRegistrationConfirmationSms;
use App\Models\Blogger;
use App\Models\Registration;
use App\Support\NormalizeIranianPhone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PublicRegistrationController extends Controller
{
    public function show(Request $request, Blogger $blogger): JsonResponse
    {
        $phone = $this->verifiedPhone($request);
        $registration = $phone ? Registration::query()->where('phone', $phone)->latest()->first() : null;

        return response()->json([
            'phone' => $phone,
            'registered' => $registration !== null,
            'ticket_code' => $registration?->ticket_code,
            'ticket_url' => $registration ? route('landing.ticket', $blogger) : null,
        ]);
    }

    public function store(Request $request, Blogger $blogger, IssueRegistration $issueRegistration, QueueRegistrationConfirmationSms $queueRegistrationConfirmationSms): JsonResponse
    {
        $phone = $this->verifiedPhone($request);
        abort_unless($phone, 403);

        $request->merge([
            'guardian_phone' => app(NormalizeIranianPhone::class)->handle($request->string('guardian_phone')->toString()),
        ]);

        $existingRegistration = Registration::query()->where('phone', $phone)->latest()->first();
        if ($existingRegistration) {
            return response()->json([
                'message' => 'ثبت‌نام شما قبلاً تکمیل شده است.',
                'ticket_url' => route('landing.ticket', $blogger),
            ], 409);
        }

        $data = $request->validate(
            [
                'full_name' => ['required', 'string', 'max:255'],
                'grade' => ['required', 'in:10,11,12'],
                'field' => ['required', 'in:math,science'],
                'school' => ['required', 'string', 'max:255'],
                'gpa' => ['required', 'numeric', 'between:0,20'],
                'study_city' => ['required', 'string', 'max:255'],
                'father_job' => ['required', 'string', 'max:255'],
                'province' => ['required', 'string', 'max:255'],
                'city' => ['required', 'string', 'max:255'],
                'area' => ['required', 'string', 'max:255'],
                'guardian_name' => ['required', 'string', 'max:255'],
                'guardian_phone' => ['required', 'regex:/^09\d{9}$/'],
            ],
            [
                'required' => 'وارد کردن :attribute الزامی است.',
                'numeric' => ':attribute باید به‌صورت عدد وارد شود.',
                'between' => ':attribute باید بین :min تا :max باشد.',
                'in' => 'گزینهٔ انتخاب‌شده برای :attribute معتبر نیست.',
                'regex' => 'فرمت :attribute صحیح نیست.',
            ],
            [
                'full_name' => 'نام و نام خانوادگی',
                'grade' => 'پایه',
                'field' => 'رشته',
                'school' => 'مدرسه',
                'gpa' => 'معدل',
                'study_city' => 'شهر محل تحصیل',
                'father_job' => 'شغل پدر',
                'province' => 'استان',
                'city' => 'شهر',
                'area' => 'محله',
                'guardian_name' => 'نام همراه',
                'guardian_phone' => 'شماره همراه والدین',
            ],
        );

        $registration = $issueRegistration->handle($blogger, [...$data, 'phone' => $phone]);
        $queueRegistrationConfirmationSms->handle($registration);

        return response()->json([
            'ticket_code' => $registration->ticket_code,
            'ticket_url' => route('landing.ticket', $blogger),
        ], 201);
    }

    public function ticket(Request $request, Blogger $blogger): Response
    {
        $phone = $this->verifiedPhone($request);
        abort_unless($phone, 403);

        $registration = Registration::query()->where('phone', $phone)->latest()->firstOrFail();

        return app(ReferenceDesignController::class)->ticket($request, $registration);
    }

    private function verifiedPhone(Request $request): ?string
    {
        return $request->session()->get('otp_verified_phone') ?: $request->cookie('event_verified_phone');
    }
}
