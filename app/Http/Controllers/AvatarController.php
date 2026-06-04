<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAvatarRequest;
use App\Models\File;
use App\Models\User;
use App\Support\ImageStreamer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Profile-photo management. Avatars are ordinary File records (uploaded via the
 * generic /media endpoint) referenced by users.avatar_file_id. The image is
 * streamed resized to any authenticated user — deliberately broader than the
 * files.view policy so avatars render in nav and lists for everyone.
 */
class AvatarController extends Controller
{
    /** Stream a user's avatar, resized (?w=…), to any authenticated user. */
    public function show(User $user, Request $request, ImageStreamer $streamer): StreamedResponse
    {
        abort_if($user->avatar_file_id === null, 404);

        $file = $user->avatarFile;
        abort_if($file === null, 404);

        return $streamer->stream($file, $request->query());
    }

    /** JSON list of the caller's own previously uploaded images for the picker. */
    public function photos(Request $request): JsonResponse
    {
        $photos = File::query()
            ->images()
            ->ownedBy($request->user()->id)
            ->keyset()
            ->cursorPaginate(config('keen.pagination_size'));

        return response()->json(cursorResponse($photos, fn (File $f): array => [
            'id' => $f->id,
            'name' => $f->original_name,
            'mime' => $f->mime,
            'v' => $f->cacheVersion(),
            'url' => $f->imageUrl(200),
            'created_at' => $f->created_at?->toIso8601String(),
        ]));
    }

    /** Set the caller's avatar to one of their existing image files. */
    public function store(StoreAvatarRequest $request): RedirectResponse
    {
        $request->user()->update(['avatar_file_id' => $request->integer('file_id')]);

        return back()->with('success', 'Profile photo updated.');
    }

    /** Clear the caller's avatar. */
    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->update(['avatar_file_id' => null]);

        return back()->with('success', 'Profile photo removed.');
    }
}
