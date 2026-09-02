<?php

namespace App\Http\Controllers;

use App\Actions\RecordRegistrationCall;
use App\Http\Requests\StoreRegistrationCallRequest;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class RegistrationCallController extends Controller
{
    public function __invoke(StoreRegistrationCallRequest $request, Registration $registration, RecordRegistrationCall $recordCall): JsonResponse
    {
        $actor = auth('web')->user();

        abort_unless($actor instanceof User, 403);

        $recordCall->handle($registration, $actor, $request->string('result')->toString(), $request->string('note')->trim()->toString() ?: null);

        return response()->json(['ok' => true]);
    }
}
