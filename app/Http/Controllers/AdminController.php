<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdminRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    public function store(StoreAdminRequest $request): JsonResponse
    {
        $user = User::query()->create($request->validated());

        return response()->json(['id' => $user->id], 201);
    }

    public function destroy(User $user): JsonResponse
    {
        abort_if($user->is(auth('web')->user()), 422, 'امکان حذف حساب فعلی وجود ندارد.');
        $user->delete();

        return response()->json(status: 204);
    }

    public function toggle(User $user): JsonResponse
    {
        abort_if($user->is(auth('web')->user()), 422, 'امکان غیرفعال‌سازی حساب فعلی وجود ندارد.');

        $user->update(['is_active' => ! $user->is_active]);

        return response()->json(['is_active' => $user->is_active]);
    }
}
