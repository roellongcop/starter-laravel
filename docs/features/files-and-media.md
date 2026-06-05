# Files & media

> Uploads, on-demand image resizing, gated downloads, and the in-app file viewer.

> **TODO** — stub. Fill in when next touching this area, following [`TEMPLATE.md`](../TEMPLATE.md).

## Purpose

_TODO._

## Key files

- `app/Actions/StoreUploadedFile` — creates a `File` (medialibrary) on the private `uploads` disk.
- `App\Support\MediaPathGenerator` — stores at `YYYY/MM/<random>.ext`.
- `App\Support\ImageStreamer` / `App\Support\ImageParams` — league/glide on-demand resize + width ladder.
- `resources/js/Components/{ImagePicker,FileDropzone,FileViewer,Avatar}.tsx`.

## How it works

_TODO — upload path, glide caching on the `image-cache` disk, `?w=/?h=/fit=` cache key,
gated streaming downloads._

## Decisions & why

_TODO — downloads always via gated controller actions, never public URLs._

## Gotchas

_TODO._

## Related

- [Services & stack](../infrastructure/services-and-stack.md)
- `CLAUDE.md` § "Files & images"
