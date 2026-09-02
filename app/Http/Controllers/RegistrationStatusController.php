<?php

namespace App\Http\Controllers;

use App\Actions\ChangeRegistrationStatus;
use App\Enums\RegistrationStatus;
use App\Http\Requests\UpdateRegistrationStatusRequest;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class RegistrationStatusController extends Controller
{
    public function __invoke(UpdateRegistrationStatusRequest $request, Registration $registration, ChangeRegistrationStatus $changeStatus): JsonResponse
    {
        $actor = auth('web')->user();

        abort_unless($actor instanceof User, 403);

        $changeStatus->handle($registration, $actor, RegistrationStatus::from($request->string('status')->toString()));

        return response()->json(['ok' => true]);
    }
}
