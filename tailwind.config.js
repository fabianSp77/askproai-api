import defaultTheme from 'tailwindcss/defaultTheme';
import plugin from 'tailwindcss/plugin';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        './node_modules/flowbite/**/*.js',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Brand/Accent Colors (customize to your brand)
                primary: {
                    50: '#f0f9ff',
                    100: '#e0f2fe',
                    200: '#bae6fd',
                    300: '#7dd3fc',
                    400: '#38bdf8',
                    500: '#0ea5e9',
                    600: '#0284c7',
                    700: '#0369a1',
                    800: '#075985',
                    900: '#0c3d66',
                },
                accent: {
                    50: '#f5f3ff',
                    100: '#ede9fe',
                    200: '#ddd6fe',
                    300: '#c4b5fd',
                    400: '#a78bfa',
                    500: '#8b5cf6',
                    600: '#7c3aed',
                    700: '#6d28d9',
                    800: '#5b21b6',
                    900: '#4c1d95',
                },
                // Calendar colors
                calendar: {
                    'available': '#10b981',
                    'booked': '#9ca3af',
                    'selected': '#8b5cf6',
                    'unavailable': '#f3f4f6',
                },
            },
            // Custom CSS Variables for theme switching
            cssVariables: true,
        },
    },
    plugins: [
        require('flowbite/plugin'),
        plugin(function({ addBase, matchVariant }) {
            // CSS Variables for light mode
            addBase({
                ':root': {
                    '--calendar-bg': '#ffffff',
                    '--calendar-surface': '#f9fafb',
                    '--calendar-border': '#e5e7eb',
                    '--calendar-text': '#111827',
                    '--calendar-text-secondary': '#6b7280',
                    '--calendar-primary': '#0ea5e9',
                    '--calendar-available': '#10b981',
                    '--calendar-booked': '#9ca3af',
                    '--calendar-selected': '#8b5cf6',
                    '--calendar-hover': '#f3f4f6',
                },
                '[data-theme="dark"]': {
                    '--calendar-bg': '#111827',
                    '--calendar-surface': '#1f2937',
                    '--calendar-border': '#374151',
                    '--calendar-text': '#f9fafb',
                    '--calendar-text-secondary': '#d1d5db',
                    '--calendar-primary': '#38bdf8',
                    '--calendar-available': '#34d399',
                    '--calendar-booked': '#6b7280',
                    '--calendar-selected': '#a78bfa',
                    '--calendar-hover': '#374151',
                },
            });
        }),
    ],
};
