import { useTheme } from '@/Components/ThemeProvider';
import YinYang from '@/Components/YinYang';

export default function ThemeToggle() {
    const { toggleTheme } = useTheme();

    return (
        <button
            type="button"
            onClick={toggleTheme}
            className="rounded-full p-2 text-muted-foreground hover:bg-accent hover:text-accent-foreground"
            title="Toggle yin / yang"
            aria-label="Toggle light and dark theme"
        >
            <YinYang className="h-5 w-5" />
        </button>
    );
}
