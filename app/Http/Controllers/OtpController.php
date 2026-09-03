<?php

namespace App\Http\Controllers;

use App\Actions\RequestOtp;
use App\Actions\VerifyOtp;
use App\Support\NormalizeIranianPhone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class OtpController extends Controller
{
    public function store(Request $request, RequestOtp $requestOtp): Response
    {
        $phone = $request->validate(['phone' => ['required', 'string', 'max:20']])['phone'];

        return response()->json(['phone' => $requestOtp->handle($phone, $request->ip())]);
    }

    public function verify(Request $request, VerifyOtp $verifyOtp): Response
    {
        $request->merge([
            'phone' => app(NormalizeIranianPhone::class)->handle($request->string('phone')->toString()),
            'code' => strtr($request->string('code')->toString(), ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']),
        ]);

        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'code' => ['required', 'string', 'digits:4'],
        ]);

        $verifyOtp->handle($data['phone'], $data['code']);
        $request->session()->put('otp_verified_phone', $data['phone']);

        return response()->noContent()->withCookie(cookie()->forever('event_verified_phone', $data['phone']));
    }

    public function destroy(Request $request): Response
    {
        $request->session()->forget('otp_verified_phone');

        return response()->noContent()->withCookie(Cookie::forget('event_verified_phone'));
    }
}
