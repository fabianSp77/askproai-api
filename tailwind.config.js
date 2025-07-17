import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import colors from 'tailwindcss/colors';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './resources/js/**/*.{js,jsx,ts,tsx}',
        './resources/js/components/**/*.{js,jsx,ts,tsx}',
    ],
    
    safelist: [
        // Background colors for sentiment and status
        'bg-emerald-100', 'dark:bg-emerald-900/30',
        'bg-rose-100', 'dark:bg-rose-900/30',
        'bg-amber-100', 'dark:bg-amber-900/30',
        'bg-slate-100', 'dark:bg-slate-900/30',
        'bg-blue-100', 'dark:bg-blue-900/30',
        'bg-purple-100', 'dark:bg-purple-900/30',
        
        // Text colors
        'text-emerald-700', 'dark:text-emerald-400',
        'text-emerald-600', 'dark:text-emerald-400',
        'text-rose-700', 'dark:text-rose-400',
        'text-rose-600', 'dark:text-rose-400',
        'text-amber-700', 'dark:text-amber-400',
        'text-amber-600', 'dark:text-amber-400',
        'text-slate-700', 'dark:text-slate-400',
        'text-slate-600', 'dark:text-slate-400',
        'text-blue-600', 'dark:text-blue-400',
        'text-purple-600', 'dark:text-purple-400',
        
        // Gradient backgrounds
        'from-emerald-50', 'to-emerald-100', 'dark:from-emerald-900/20', 'dark:to-emerald-800/20',
        'from-gray-50', 'to-gray-100', 'dark:from-gray-900/20', 'dark:to-gray-800/20',
        'from-red-50', 'to-red-100', 'dark:from-red-900/20', 'dark:to-red-800/20',
        'from-blue-50', 'to-blue-100', 'dark:from-blue-900/20', 'dark:to-blue-800/20',
        
        // Other dynamic classes
        'bg-emerald-200', 'dark:bg-emerald-700',
        'bg-emerald-500',
        'bg-gray-200', 'dark:bg-gray-700',
        'bg-gray-500',
        'text-green-600', 'dark:text-green-400',
        'text-red-600', 'dark:text-red-400',
        
        // Customer timeline classes
        'bg-green-100', 'dark:bg-green-900/30',
        'text-green-700', 'dark:text-green-300',
        'bg-red-50', 'dark:bg-red-900/20',
        'bg-amber-50', 'dark:bg-amber-900/20',
        'text-gray-700', 'dark:text-gray-300',
        'bg-gray-50', 'dark:bg-gray-700',
        'bg-gray-100', 'dark:bg-gray-700',
        'bg-blue-50', 'dark:bg-blue-900/20',
        'text-blue-600', 'dark:text-blue-400',
    ],

    darkMode: ["class"],
    theme: {
        container: {
            center: true,
            padding: "2rem",
            screens: {
                "2xl": "1400px",
            },
        },
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                danger: colors.rose,
                primary: colors.amber,
                success: colors.green,
                warning: colors.yellow,
                gray: colors.gray,
                border: "hsl(var(--border))",
                input: "hsl(var(--input))",
                ring: "hsl(var(--ring))",
                background: "hsl(var(--background))",
                foreground: "hsl(var(--foreground))",
                primary: {
                    DEFAULT: "hsl(var(--primary))",
                    foreground: "hsl(var(--primary-foreground))",
                },
                secondary: {
                    DEFAULT: "hsl(var(--secondary))",
                    foreground: "hsl(var(--secondary-foreground))",
                },
                destructive: {
                    DEFAULT: "hsl(var(--destructive))",
                    foreground: "hsl(var(--destructive-foreground))",
                },
                muted: {
                    DEFAULT: "hsl(var(--muted))",
                    foreground: "hsl(var(--muted-foreground))",
                },
                accent: {
                    DEFAULT: "hsl(var(--accent))",
                    foreground: "hsl(var(--accent-foreground))",
                },
                popover: {
                    DEFAULT: "hsl(var(--popover))",
                    foreground: "hsl(var(--popover-foreground))",
                },
                card: {
                    DEFAULT: "hsl(var(--card))",
                    foreground: "hsl(var(--card-foreground))",
                },
            },
            borderRadius: {
                lg: "var(--radius)",
                md: "calc(var(--radius) - 2px)",
                sm: "calc(var(--radius) - 4px)",
            },
            keyframes: {
                "accordion-down": {
                    from: { height: "0" },
                    to: { height: "var(--radix-accordion-content-height)" },
                },
                "accordion-up": {
                    from: { height: "var(--radix-accordion-content-height)" },
                    to: { height: "0" },
                },
            },
            animation: {
                "accordion-down": "accordion-down 0.2s ease-out",
                "accordion-up": "accordion-up 0.2s ease-out",
            },
        },
    },

    plugins: [forms],
};
