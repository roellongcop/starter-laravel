import { Head, Link, usePage } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

import { useTheme } from '@/Components/ThemeProvider';
import ThemeStyle from '@/Components/ThemeStyle';
import YinYang from '@/Components/YinYang';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useIdleLogout } from '@/hooks/use-idle-logout';

export default function Guest({ children }: PropsWithChildren) {
    const brand = usePage().props.brand;
    const { toggleTheme } = useTheme();

    // A logged-in user can still browse guest pages (e.g. /contact); enforce
    // idle logout here too. No-ops for actual guests.
    useIdleLogout();

    return (
        <div className="flex min-h-screen flex-col items-center justify-center bg-background px-4 py-10 text-foreground">
            {brand.favicon_url && (
                <Head>
                    <link rel="icon" href={brand.favicon_url} />
                </Head>
            )}
            <ThemeStyle />

            <div className="absolute right-4 top-4">
                <Button
                    variant="ghost"
                    size="icon"
                    onClick={toggleTheme}
                    title="Toggle yin / yang"
                    aria-label="Toggle light and dark theme"
                >
                    <YinYang className="h-5 w-5" />
                </Button>
            </div>

            <Link
                href="/"
                className="flex items-center gap-2 text-lg font-bold tracking-tight"
            >
                <YinYang className="h-10 w-10" />
                <span>RL</span>
            </Link>

            <Card className="mt-6 w-full sm:max-w-md">
                <CardContent className="pt-6">{children}</CardContent>
            </Card>

            <Link
                href="/"
                className="mt-6 text-sm text-muted-foreground transition-colors hover:text-foreground"
            >
                ← Back to portfolio
            </Link>
        </div>
    );
}
