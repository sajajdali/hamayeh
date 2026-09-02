<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBloggerAvatarRequest;
use App\Http\Requests\StoreBloggerRequest;
use App\Models\Blogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BloggerController extends Controller
{
    public function store(StoreBloggerRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['code'] = mb_strtolower($data['code']);
        $data['slug'] = mb_strtolower($data['slug']);
        $data['password'] = $data['password'] ?: $data['code'];

        $blogger = Blogger::query()->create($data);

        return response()->json(['id' => $blogger->id], 201);
    }

    public function toggle(Blogger $blogger): JsonResponse
    {
        $blogger->update(['is_active' => ! $blogger->is_active]);

        return response()->json(['is_active' => $blogger->is_active]);
    }

    public function destroy(Blogger $blogger): JsonResponse
    {
        DB::transaction(function () use ($blogger): void {
            $blogger->registrations()->update(['blogger_id' => null]);
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
