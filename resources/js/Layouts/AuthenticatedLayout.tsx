import Avatar from '@/Components/Avatar';
import Bell from '@/Components/Bell';
import Dropdown from '@/Components/Dropdown';
import GlobalSearch from '@/Components/GlobalSearch';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import Sidebar from '@/Components/Sidebar';
import ThemeStyle from '@/Components/ThemeStyle';
import ThemeToggle from '@/Components/ThemeToggle';
import YinYang from '@/Components/YinYang';
import { useIdleLogout } from '@/hooks/use-idle-logout';
import { Head, Link, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode, useEffect, useState } from 'react';

export default function Authenticated({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const { props } = usePage();
    const user = props.auth.user;
    const appName = props.settings.system.app_name;
    const brand = props.brand;

    useIdleLogout();

    const [showingNavigationDropdown, setShowingNavigationDropdown] =
        useState(false);

    // Lock background scroll while the mobile menu overlay is open so the page
    // behind it doesn't scroll instead of the menu.
    useEffect(() => {
        if (!showingNavigationDropdown) {
            return;
        }

        const original = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        return () => {
            document.body.style.overflow = original;
        };
    }, [showingNavigationDropdown]);

    return (
        <div className="min-h-screen bg-background text-foreground">
            {brand.favicon_url && (
                <Head>
                    <link rel="icon" href={brand.favicon_url} />
                </Head>
            )}
            <ThemeStyle />
            <nav className="sticky top-0 z-40 border-b border-border bg-background/80 backdrop-blur">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between gap-2">
                        <div className="flex min-w-0 items-center">
                            <Link
                                href="/"
                                className="flex min-w-0 items-center gap-2"
                            >
                                <YinYang className="h-8 w-8 shrink-0" />
                                <span className="truncate text-lg font-bold tracking-tight text-foreground">
                                    {appName}
                                </span>
                            </Link>
                        </div>

                        <div className="flex items-center gap-1 sm:ms-6 sm:gap-2">
                            <GlobalSearch />
                            <ThemeToggle />
                            <Bell />
                            <div className="relative ms-3 hidden sm:block">
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <span className="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                className="inline-flex items-center gap-2 rounded-md border border-transparent bg-transparent px-3 py-2 text-sm font-medium leading-4 text-muted-foreground transition duration-150 ease-in-out hover:text-foreground focus:outline-none"
                                            >
                                                <Avatar
                                                    name={user.name}
                                                    src={user.avatar_url}
                                                    size={28}
                                                />
                                                {user.name}

                                                <svg
                                                    className="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fillRule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clipRule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </Dropdown.Trigger>

                                    <Dropdown.Content>
                                        <Dropdown.Link
                                            href={route('profile.edit')}
                                        >
                                            Profile
                                        </Dropdown.Link>
                                        <Dropdown.Link
                                            href={route('logout')}
                                            method="post"
                                            as="button"
                                        >
                                            Log Out
                                        </Dropdown.Link>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>

                            <div className="-me-2 flex items-center md:hidden">
                                <button
                                    onClick={() =>
                                        setShowingNavigationDropdown(
                                            (previousState) => !previousState,
                                        )
                                    }
                                    title="Toggle navigation"
                                    aria-label="Toggle navigation"
                                    aria-expanded={showingNavigationDropdown}
                                    className="inline-flex items-center justify-center rounded-md p-2 text-muted-foreground transition duration-150 ease-in-out hover:bg-accent hover:text-foreground focus:bg-accent focus:outline-none"
                                >
                                    <svg
                                        className="h-6 w-6"
                                        stroke="currentColor"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            className={
                                                !showingNavigationDropdown
                                                    ? 'inline-flex'
                                                    : 'hidden'
                                            }
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth="2"
                                            d="M4 6h16M4 12h16M4 18h16"
                                        />
                                        <path
                                            className={
                                                showingNavigationDropdown
                                                    ? 'inline-flex'
                                                    : 'hidden'
                                            }
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth="2"
                                            d="M6 18L18 6M6 6l12 12"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            {showingNavigationDropdown && (
                <div className="fixed inset-x-0 bottom-0 top-16 z-30 overflow-y-auto overscroll-contain border-t border-border bg-background md:hidden">
                    <div className="px-2 pb-3 pt-2">
                        <Sidebar />
                    </div>

                    <div className="border-t border-border pb-1 pt-4">
                        <div className="px-4">
                            <div className="text-base font-medium text-foreground">
                                {user.name}
                            </div>
                            <div className="text-sm font-medium text-muted-foreground">
                                {user.email}
                            </div>
                        </div>

                        <div className="mt-3 space-y-1">
                            <ResponsiveNavLink href={route('profile.edit')}>
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                method="post"
                                href={route('logout')}
                                as="button"
                            >
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            )}

            <div className="mx-auto flex w-full max-w-7xl gap-6 px-4 sm:px-6 lg:px-8">
                <aside className="hidden w-60 shrink-0 py-6 md:block">
                    <Sidebar />
                </aside>

                <div className="min-w-0 flex-1">
                    {header && (
                        <header className="py-6">
                            <div className="text-xl font-semibold text-foreground">
                                {header}
                            </div>
                        </header>
                    )}

                    <main className="py-6">{children}</main>
                </div>
            </div>
        </div>
    );
}
