import { Monitor, Moon, Sun } from 'lucide-react';

import { type Theme, useTheme } from '@/Components/ThemeProvider';

const NEXT: Record<Theme, Theme> = {
    light: 'dark',
    dark: 'system',
    system: 'light',
};

export default function ThemeToggle() {
    const { theme, setTheme } = useTheme();

    const Icon = theme === 'light' ? Sun : theme === 'dark' ? Moon : Monitor;

    return (
        <button
            type="button"
            onClick={() => setTheme(NEXT[theme])}
            className="rounded-full p-2 text-muted-foreground hover:bg-accent hover:text-accent-foreground"
            title={`Theme: ${theme} (click to change)`}
            aria-label="Toggle theme"
        >
            <Icon className="h-5 w-5" />
        </button>
    );
}
