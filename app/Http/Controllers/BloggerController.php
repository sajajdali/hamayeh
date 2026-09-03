<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBloggerAvatarRequest;
use App\Http\Requests\StoreBloggerRequest;
use App\Models\Blogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BloggerController extends Controller
{
    public function store(StoreBloggerRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['code'] = mb_strtolower($data['code']);
        $data['slug'] = mb_strtolower($data['slug']);
        $data['password'] = $data['password'] ?? $data['code'];

        $blogger = Blogger::query()->create($data);

        return response()->json(['id' => $blogger->id], 201);
    }

    public function toggle(Blogger $blogger): JsonResponse
    {
        abort_if($blogger->code === 'a0', 422, 'بلاگر پیش‌فرض قابل غیرفعال‌سازی نیست.');

        $blogger->update(['is_active' => ! $blogger->is_active]);

        return response()->json(['is_active' => $blogger->is_active]);
    }

    public function updateCredentials(Blogger $blogger): JsonResponse
    {
        $data = request()->validate([
            'username' => ['required', 'string', 'regex:/^[a-z0-9_-]{2,24}$/', Rule::unique('bloggers', 'slug')->ignore($blogger->id)],
            'password' => ['required', 'string', 'min:4', 'max:255'],
        ]);

        $blogger->update(['slug' => mb_strtolower($data['username']), 'password' => $data['password']]);

        return response()->json(['slug' => $blogger->slug]);
    }

    public function destroy(Blogger $blogger): JsonResponse
    {
        abort_if($blogger->code === 'a0', 422, 'بلاگر پیش‌فرض قابل حذف نیست.');

        request()->validate([
            'confirm_name' => ['required', 'string', Rule::in([$blogger->name])],
        ], [
            'confirm_name.in' => 'برای حذف، نام بلاگر را دقیقاً وارد کنید.',
        ]);

        DB::transaction(function () use ($blogger): void {
            $blogger->registrations()->delete();
            $blogger->delete();
        });

        return response()->json(status: 204);
    }

    public function avatar(StoreBloggerAvatarRequest $request, Blogger $blogger): JsonResponse
    {
        if ($blogger->avatar_path) {
            Storage::disk('public')->delete($blogger->avatar_path);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $blogger->update(['avatar_path' => $path]);

        return response()->json(['path' => $path]);
    }
}
