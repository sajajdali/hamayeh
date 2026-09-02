<?php

namespace App\Http\Controllers;

use App\Actions\QueueRegistrationSms;
use App\Http\Requests\SendRegistrationSmsRequest;
use App\Models\Registration;
use App\Models\SmsTemplate;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class RegistrationSmsController extends Controller
{
    public function __invoke(SendRegistrationSmsRequest $request, Registration $registration, QueueRegistrationSms $queueSms): JsonResponse
    {
        $actor = auth('web')->user();

        abort_unless($actor instanceof User, 403);

        $template = SmsTemplate::query()->findOrFail($request->integer('template_id'));
        $message = $queueSms->handle($registration, $template, $actor, $request->string('recipient')->toString());

        return response()->json(['id' => $message->id], 202);
    }
}
