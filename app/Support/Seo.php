<?php

namespace App\Support;

use App\Models\File;
use App\Settings\ImageSettings;
use App\Settings\SystemSettings;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Complete SEO metadata for one page, passed as the `seo` prop and rendered by
 * <Seo>. See docs/features/seo-and-ssr.md.
 *
 * @implements Arrayable<string, mixed>
 */
final class Seo implements Arrayable, JsonSerializable
{
    /**
     * @param  array<string, mixed>|null  $jsonLd  schema.org JSON-LD (rendered as application/ld+json)
     */
    public function __construct(
        public string $title,
        public string $description,
        public string $canonical,
        public ?string $image,
        public string $type,
        public string $robots,
        public ?string $keywords,
        public string $locale,
        public string $siteName,
        public string $twitterCard,
        public ?string $twitterSite,
        public ?array $jsonLd,
    ) {}

    /**
     * Build metadata, filling omitted fields from config/seo.php.
     *
     * @param  array<string, mixed>|null  $jsonLd
     */
    public static function make(
        ?string $title = null,
        ?string $description = null,
        ?string $canonical = null,
        ?string $image = null,
        string $type = 'website',
        ?string $robots = null,
        ?string $keywords = null,
        ?array $jsonLd = null,
    ): self {
        return new self(
            title: self::withSuffix($title ?? (string) config('seo.default_title')),
            description: $description ?? (string) config('seo.default_description'),
            canonical: $canonical ?? request()->url(),
            image: $image !== null ? self::absoluteUrl($image) : self::defaultImage(),
            type: $type,
            robots: $robots ?? 'index,follow',
            keywords: $keywords,
            locale: (string) config('seo.locale', 'en_US'),
            siteName: self::siteName(),
            twitterCard: (string) config('seo.twitter_card', 'summary_large_image'),
            twitterSite: config('seo.twitter_site') ? (string) config('seo.twitter_site') : null,
            jsonLd: $jsonLd,
        );
    }

    /**
     * Build metadata from a model exposing `meta_title`/`meta_description`/
     * `og_image` (the entry point for DB-driven SEO pages).
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function fromModel(object $model, array $overrides = []): self
    {
        return self::make(
            title: $overrides['title'] ?? $model->meta_title ?? ($model->title ?? null),
            description: $overrides['description'] ?? $model->meta_description ?? null,
            canonical: $overrides['canonical'] ?? null,
            image: $overrides['image'] ?? $model->og_image ?? null,
            type: $overrides['type'] ?? 'article',
            jsonLd: $overrides['jsonLd'] ?? null,
        );
    }

    /**
     * A schema.org Person object for a portfolio/profile page.
     *
     * @param  list<string>  $sameAs  profile URLs (LinkedIn, GitHub, …)
     * @return array<string, mixed>
     */
    public static function personSchema(string $name, string $jobTitle, array $sameAs = []): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $name,
            'jobTitle' => $jobTitle,
            'url' => url('/'),
            'sameAs' => $sameAs ?: null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'canonical' => $this->canonical,
            'image' => $this->image,
            'type' => $this->type,
            'robots' => $this->robots,
            'keywords' => $this->keywords,
            'locale' => $this->locale,
            'site_name' => $this->siteName,
            'twitter_card' => $this->twitterCard,
            'twitter_site' => $this->twitterSite,
            'json_ld' => $this->jsonLd,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /** Append the configured " · suffix" unless absent or already present. */
    private static function withSuffix(string $title): string
    {
        $suffix = config('seo.title_suffix');

        if (blank($suffix) || str_ends_with($title, (string) $suffix)) {
            return $title;
        }

        return $title.' · '.$suffix;
    }

    /** SystemSettings app name if available, else the configured site name. */
    private static function siteName(): string
    {
        try {
            return app(SystemSettings::class)->app_name;
        } catch (\Throwable) {
            return (string) config('seo.site_name', config('app.name'));
        }
    }

    /** Configured default image, else the brand logo, as an absolute URL or null. */
    private static function defaultImage(): ?string
    {
        $configured = config('seo.default_image');

        if (filled($configured)) {
            return self::absoluteUrl((string) $configured);
        }

        return self::brandImage();
    }

    /**
     * Absolute URL of the landscape (preferred) or square brand logo, with a
     * cache-buster, mirroring HandleInertiaRequests::brandUrl(). Null when unset.
     */
    private static function brandImage(): ?string
    {
        try {
            $image = app(ImageSettings::class);

            foreach (['landscape-logo' => $image->landscape_logo_token, 'square-logo' => $image->square_logo_token] as $slot => $token) {
                if (filled($token) && File::where('token', $token)->exists()) {
                    return route('brand.show', ['slot' => $slot, 'v' => substr($token, 0, 12)], absolute: true);
                }
            }
        } catch (\Throwable) {
            // Settings/table unavailable (e.g. early migrations) — no image.
        }

        return null;
    }

    /** Leave absolute URLs untouched; resolve app-relative paths against the app URL. */
    private static function absoluteUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url($path);
    }
}
