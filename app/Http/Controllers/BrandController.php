<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Settings\ImageSettings;
use App\Support\ImageStreamer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Publicly streams the configured brand images (favicon, square + landscape
 * logos) so they render before login and on guest pages — unlike the gated
 * media route. The image itself is still the private uploaded File, served
 * on-demand via the same resizing/caching streamer.
 */
class BrandController extends Controller
{
    /** slot segment => ImageSettings token property */
    protected const SLOTS = [
        'favicon' => 'favicon_token',
        'square-logo' => 'square_logo_token',
        'landscape-logo' => 'landscape_logo_token',
    ];

    public function show(string $slot, Request $request, ImageStreamer $streamer): StreamedResponse
    {
        $property = self::SLOTS[$slot] ?? abort(404);

        $token = app(ImageSettings::class)->{$property};
        abort_if(blank($token), 404);

        $file = File::where('token', $token)->first() ?? abort(404);

        return $streamer->stream($file, $request->query());
    }
}
