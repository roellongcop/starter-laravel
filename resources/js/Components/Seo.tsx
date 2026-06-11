import { Head } from '@inertiajs/react';

/** Mirrors the array shape from App\Support\Seo::toArray(). */
export interface SeoData {
    title: string;
    description: string;
    canonical: string;
    image: string | null;
    type: string;
    robots: string;
    keywords: string | null;
    locale: string;
    site_name: string;
    twitter_card: string;
    twitter_site: string | null;
    json_ld: Record<string, unknown> | null;
}

/** Renders the `seo` prop into <head>. See docs/features/seo-and-ssr.md. */
export default function Seo({ seo }: { seo: SeoData }) {
    return (
        <Head title={seo.title}>
            <meta
                name="description"
                content={seo.description}
                head-key="description"
            />
            <meta name="robots" content={seo.robots} head-key="robots" />
            {seo.keywords && (
                <meta
                    name="keywords"
                    content={seo.keywords}
                    head-key="keywords"
                />
            )}
            <link rel="canonical" href={seo.canonical} head-key="canonical" />

            <meta property="og:type" content={seo.type} head-key="og:type" />
            <meta property="og:title" content={seo.title} head-key="og:title" />
            <meta
                property="og:description"
                content={seo.description}
                head-key="og:description"
            />
            <meta property="og:url" content={seo.canonical} head-key="og:url" />
            <meta
                property="og:site_name"
                content={seo.site_name}
                head-key="og:site_name"
            />
            <meta
                property="og:locale"
                content={seo.locale}
                head-key="og:locale"
            />
            {seo.image && (
                <meta
                    property="og:image"
                    content={seo.image}
                    head-key="og:image"
                />
            )}

            <meta
                name="twitter:card"
                content={seo.twitter_card}
                head-key="twitter:card"
            />
            <meta
                name="twitter:title"
                content={seo.title}
                head-key="twitter:title"
            />
            <meta
                name="twitter:description"
                content={seo.description}
                head-key="twitter:description"
            />
            {seo.twitter_site && (
                <meta
                    name="twitter:site"
                    content={seo.twitter_site}
                    head-key="twitter:site"
                />
            )}
            {seo.image && (
                <meta
                    name="twitter:image"
                    content={seo.image}
                    head-key="twitter:image"
                />
            )}

            {seo.json_ld && (
                <script
                    type="application/ld+json"
                    head-key="json-ld"
                    // eslint-disable-next-line react/no-danger
                    dangerouslySetInnerHTML={{
                        __html: JSON.stringify(seo.json_ld),
                    }}
                />
            )}
        </Head>
    );
}
