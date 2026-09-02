<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSmsTemplateRequest;
use App\Models\SmsTemplate;
use Illuminate\Http\JsonResponse;

class SmsTemplateController extends Controller
{
    public function store(StoreSmsTemplateRequest $request): JsonResponse
    {
        $template = SmsTemplate::query()->create($request->validated());

        return response()->json(['id' => $template->id], 201);
    }

    public function destroy(SmsTemplate $smsTemplate): JsonResponse
    {
        $smsTemplate->delete();

        return response()->json(status: 204);
    }
}
