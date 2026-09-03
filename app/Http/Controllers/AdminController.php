<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdminRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

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

    public function updateCredentials(User $user): JsonResponse
    {
        abort_if($user->is(auth('web')->user()), 422, 'برای تغییر اطلاعات حساب فعلی از صفحهٔ ورود استفاده کنید.');

        $data = request()->validate([
            'username' => ['required', 'string', 'regex:/^[a-zA-Z0-9._-]{3,20}$/', Rule::unique('users', 'username')->ignore($user->id)],
            'password' => ['required', 'string', 'min:4', 'max:255'],
        ]);

        $user->update($data);

        return response()->json(['username' => $user->username]);
    }

    public function toggle(User $user): JsonResponse
    {
        abort_if($user->is(auth('web')->user()), 422, 'امکان غیرفعال‌سازی حساب فعلی وجود ندارد.');

        $user->update(['is_active' => ! $user->is_active]);

        return response()->json(['is_active' => $user->is_active]);
    }
}
