import * as React from 'react';

export type Theme = 'light' | 'dark' | 'system';

type ThemeContextValue = {
    theme: Theme;
    resolvedTheme: 'light' | 'dark';
    setTheme: (theme: Theme) => void;
    toggleTheme: () => void;
};

const STORAGE_KEY = 'keen-admin-theme';

const ThemeContext = React.createContext<ThemeContextValue | undefined>(
    undefined,
);

function systemPrefersDark() {
    return (
        typeof window !== 'undefined' &&
        window.matchMedia('(prefers-color-scheme: dark)').matches
    );
}

function resolve(theme: Theme): 'light' | 'dark' {
    if (theme === 'system') return systemPrefersDark() ? 'dark' : 'light';
    return theme;
}

/**
 * Applies the active theme to <html data-theme="..."> so the CSS variables in
 * app.css (and Tailwind's `[data-theme="dark"]` selector) take effect.
 */
function applyTheme(theme: Theme) {
    const resolved = resolve(theme);
    document.documentElement.setAttribute('data-theme', resolved);
}

export function ThemeProvider({
    children,
    defaultTheme = 'system',
}: {
    children: React.ReactNode;
    defaultTheme?: Theme;
}) {
    const [theme, setThemeState] = React.useState<Theme>(() => {
        if (typeof window === 'undefined') return defaultTheme;
        return (
            (localStorage.getItem(STORAGE_KEY) as Theme | null) ?? defaultTheme
        );
    });

    React.useEffect(() => {
        applyTheme(theme);
    }, [theme]);

    const setTheme = React.useCallback((next: Theme) => {
        localStorage.setItem(STORAGE_KEY, next);
        setThemeState(next);
    }, []);

    const value = React.useMemo<ThemeContextValue>(
        () => ({
            theme,
            resolvedTheme: resolve(theme),
            setTheme,
            toggleTheme: () =>
                setTheme(resolve(theme) === 'dark' ? 'light' : 'dark'),
        }),
        [theme, setTheme],
    );

    return (
        <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>
    );
}

export function useTheme() {
    const ctx = React.useContext(ThemeContext);
    if (!ctx) {
        throw new Error('useTheme must be used within a ThemeProvider');
    }
    return ctx;
}
