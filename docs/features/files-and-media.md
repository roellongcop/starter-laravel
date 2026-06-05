# Files & media

> Uploads, on-demand image resizing, gated downloads, and the in-app file viewer.

## Purpose

One path for every uploaded file: a `File` row (backed by spatie/medialibrary) on the private
`uploads` disk, addressed by its public `token`, served only through gated controller actions.
Images get cheap, cached, on-demand resizes; documents get an in-browser preview. The medialibrary
wiring lives in exactly one place so callers (file manager, avatars, brand assets, user documents)
never touch it directly.

## Key files

- `app/Actions/StoreUploadedFile` — the single write path: creates a `File`, attaches the upload to
  medialibrary, and denormalizes the resulting Media's metadata (extension/mime/size/disk/path)
  back onto the `File` columns.
- `App\Support\MediaPathGenerator` — stores media under `YYYY/MM/` (taken from the media's
  `created_at`, so the path is stable across reads).
- `App\Support\ImageStreamer` / `App\Support\ImageParams` — league/glide on-demand resize, cached on
  the local `image-cache` disk; widths snap to a preset ladder.
- `resources/js/Components/ImagePicker.tsx` — upload / pick-existing / camera + Cropper.js editor,
  always resolving to an uploaded File `{ token, url }`.
- `resources/js/Components/FileDropzone.tsx` — generic drag-and-drop multipart uploader.
- `resources/js/Components/FileViewer.tsx` — in-app preview (image/pdf native; csv/xls/xlsx + docx
  parsed client-side).
- `resources/js/Components/Avatar.tsx` — initials fallback + retina-aware sized image URLs.

## How it works

1. **Upload.** A multipart POST hits a controller that calls `StoreUploadedFile`. It creates the
   `File` row first, then `addMedia(...)->usingFileName(Str::random(40).'.'+ext)` onto the private
   `uploads` disk (never the medialibrary default). The random filename guarantees uniqueness;
   `MediaPathGenerator` nests it under `YYYY/MM/`. Media metadata is copied onto the `File`.
2. **Resize on demand.** `ImageStreamer::stream()` runs the stored image through Glide and caches the
   derivative on the `image-cache` disk; later requests for the same `(file, params)` serve the cached
   file. The cache is namespaced per source disk (`cache_path_prefix`) so identical relative paths on
   different disks can't collide. `ImageParams::sanitize()` snaps `?w=` to the preset ladder
   (`[32, 64, 128, 200, 400, 800]`) and whitelists `fit` — so the cache key space is bounded and the
   cache can't be flooded with arbitrary sizes.
3. **Display.** `<Avatar>` (and other internal image consumers) append `?w=<size×DPR>` to the URL so
   each render fetches an appropriately small, server-cached size rather than the full image; external
   `http…` URLs and URLs that already carry `w=` are left untouched.
4. **Preview.** `<FileViewer>` renders images/PDFs natively; csv/xls/xlsx via SheetJS and docx via
   mammoth, both **lazily imported** so the heavy parsers are only fetched when actually opened.

## Decisions & why

- **Downloads are always streamed through gated controller actions, never public URLs.** Disks are
  private; the `token` is the public handle, authorization is enforced per request.
- **Glide derivatives are immutable.** A `File`'s stored path is random and never overwritten and the
  params are part of the cache key, so streamed resizes carry
  `Cache-Control: private, max-age=31536000, immutable`.
- **Brand images** (logo, favicon, …) are exposed to the frontend as URLs that embed a short
  token-based cache-buster, so an updated asset busts the browser cache without a public path.
- **`ImagePicker` exports PNG as PNG, everything else as JPEG** — see the inline note in the component;
  PNG transparency would be flattened to white by JPEG.

## Gotchas

- The single write path is `StoreUploadedFile` — don't call medialibrary directly; reuse it so the
  `File` columns stay denormalized and consistent.
- New widths must be added to `ImageParams::WIDTHS`, or a request for an off-ladder size snaps to the
  nearest preset (by design).
- `FileViewer`'s sheet/docx parsers are dynamically imported — keep them lazy; eager imports bloat the
  initial bundle.

## Related

- [Services & stack](../infrastructure/services-and-stack.md) — disks (`uploads`/`exports`/… local↔s3),
  `dated_path()`, gated downloads.
- [Theming](theming.md) — brand assets are configured via settings.
- `CLAUDE.md` § "Files & images"
