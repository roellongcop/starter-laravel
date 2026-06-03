<?php

namespace App\Http\Controllers;

use App\Actions\StoreUploadedFile;
use App\Http\Requests\StoreMediaRequest;
use App\Models\File;
use App\Support\ImageStreamer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Generic image upload + on-demand resized delivery. Reusable by any page (not
 * just avatars): the <ImagePicker> uploads a cropped image here and references
 * the returned id, then displays it through the resize endpoint.
 */
class MediaController extends Controller
{
    /** Store an uploaded image (owned by the caller) and return its id + urls. */
    public function store(StoreMediaRequest $request, StoreUploadedFile $store): JsonResponse
    {
        $file = $store($request->file('file'), $request->user()->id, $request->input('tag'));

        return response()->json([
            'id' => $file->id,
            'original_name' => $file->original_name,
            'url' => route('media.img', ['file' => $file->id, 'w' => 400]),
            'thumb_url' => route('media.img', ['file' => $file->id, 'w' => 160]),
        ]);
    }

    /** Stream a resized, cached copy — owner or anyone with files.view. */
    public function img(Request $request, File $file, ImageStreamer $streamer): StreamedResponse
    {
        abort_unless(
            $file->owner_id === $request->user()->id || $request->user()->can('files.view'),
            403,
        );

        return $streamer->stream($file, $request->query());
    }
}
