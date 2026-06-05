import ApplicationLogo from '@/Components/ApplicationLogo';
import ThemeStyle from '@/Components/ThemeStyle';
import { Head, Link, usePage } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function Guest({ children }: PropsWithChildren) {
    const brand = usePage().props.brand;
    // Prefer the wide logo here, then the square one, else the bundled SVG.
    const logoUrl = brand.landscape_logo_url ?? brand.square_logo_url;

    return (
        <div className="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0 dark:bg-gray-900">
            {brand.favicon_url && (
                <Head>
                    <link rel="icon" href={brand.favicon_url} />
                </Head>
            )}
            <ThemeStyle />
            <div>
                <Link href="/">
                    {logoUrl ? (
                        <img
                            src={logoUrl}
                            alt="Logo"
                            className="h-20 w-auto object-contain"
                        />
                    ) : (
                        <ApplicationLogo className="h-20 w-20 fill-current text-gray-500" />
                    )}
                </Link>
            </div>

            <div className="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg dark:bg-gray-800">
                {children}
            </div>
        </div>
    );
}
