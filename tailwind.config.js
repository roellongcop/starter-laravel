import forms from '@tailwindcss/forms';
import animate from 'tailwindcss-animate';
import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    // Dark mode is driven by a `data-theme="dark"` attribute on <html>,
    // toggled by the ThemeProvider. (shadcn tokens live in app.css.)
    darkMode: ['selector', '[data-theme="dark"]'],

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.tsx',
    ],

    theme: {
        container: {
            center: true,
            padding: '2rem',
            screens: { '2xl': '1400px' },
        },
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                border: 'hsl(var(--border))',
                input: 'hsl(var(--input))',
                ring: 'hsl(var(--ring))',
                background: 'hsl(var(--background))',
                foreground: 'hsl(var(--foreground))',
                primary: {
                    DEFAULT: 'hsl(var(--primary))',
                    foreground: 'hsl(var(--primary-foreground))',
                },
                secondary: {
                    DEFAULT: 'hsl(var(--secondary))',
                    foreground: 'hsl(var(--secondary-foreground))',
                },
                destructive: {
                    DEFAULT: 'hsl(var(--destructive))',
                    foreground: 'hsl(var(--destructive-foreground))',
                },
                muted: {
                    DEFAULT: 'hsl(var(--muted))',
                    foreground: 'hsl(var(--muted-foreground))',
                },
                accent: {
                    DEFAULT: 'hsl(var(--accent))',
                    foreground: 'hsl(var(--accent-foreground))',
                },
                popover: {
                    DEFAULT: 'hsl(var(--popover))',
                    foreground: 'hsl(var(--popover-foreground))',
                },
                card: {
                    DEFAULT: 'hsl(var(--card))',
                    foreground: 'hsl(var(--card-foreground))',
                },
            },
            borderRadius: {
                lg: 'var(--radius)',
                md: 'calc(var(--radius) - 2px)',
                sm: 'calc(var(--radius) - 4px)',
            },
            keyframes: {
                'accordion-down': {
                    from: { height: '0' },
                    to: { height: 'var(--radix-accordion-content-height)' },
                },
                'accordion-up': {
                    from: { height: 'var(--radix-accordion-content-height)' },
                    to: { height: '0' },
                },
                // Slow ambient drift for futuristic background blobs.
                'float-slow': {
                    '0%, 100%': { transform: 'translate3d(0, 0, 0) scale(1)' },
                    '50%': {
                        transform: 'translate3d(4%, -6%, 0) scale(1.12)',
                    },
                },
                'float-slower': {
                    '0%, 100%': { transform: 'translate3d(0, 0, 0) scale(1)' },
                    '50%': {
                        transform: 'translate3d(-5%, 5%, 0) scale(1.08)',
                    },
                },
                // Breathing accent glow behind the hero mark.
                'glow-pulse': {
                    '0%, 100%': { opacity: '0.35', transform: 'scale(1)' },
                    '50%': { opacity: '0.6', transform: 'scale(1.06)' },
                },
                // Slow light sweeping across the headline.
                shimmer: {
                    '0%': { backgroundPosition: '200% 0' },
                    '100%': { backgroundPosition: '-200% 0' },
                },
                // Restless film grain for an atmospheric, alive texture.
                grain: {
                    '0%, 100%': { transform: 'translate(0, 0)' },
                    '25%': { transform: 'translate(-5px, 4px)' },
                    '50%': { transform: 'translate(4px, -3px)' },
                    '75%': { transform: 'translate(-3px, -5px)' },
                },
            },
            animation: {
                'accordion-down': 'accordion-down 0.2s ease-out',
                'accordion-up': 'accordion-up 0.2s ease-out',
                'float-slow': 'float-slow 18s ease-in-out infinite',
                'float-slower': 'float-slower 24s ease-in-out infinite',
                'glow-pulse': 'glow-pulse 6s ease-in-out infinite',
                shimmer: 'shimmer 7s linear infinite',
                grain: 'grain 1.4s steps(1) infinite',
            },
        },
    },

    plugins: [forms, animate],
};
