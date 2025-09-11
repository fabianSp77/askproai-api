import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import flowbite from 'flowbite/plugin';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './vendor/filament/**/*.blade.php',
        './node_modules/flowbite/**/*.js',
    ],
    safelist: [
        // Dynamically generated classes that PurgeCSS might miss
        'bg-green-100',
        'bg-yellow-100',
        'bg-red-100',
        'text-green-800',
        'text-yellow-800',
        'text-red-800',
        'border-green-400',
        'border-yellow-400',
        'border-red-400',
        // Dark mode variants
        'dark:bg-green-900',
        'dark:bg-yellow-900',
        'dark:bg-red-900',
        'dark:text-green-400',
        'dark:text-yellow-400',
        'dark:text-red-400',
        // Grid classes
        'grid-cols-1',
        'grid-cols-2',
        'grid-cols-3',
        'grid-cols-4',
        'md:grid-cols-2',
        'md:grid-cols-3',
        'md:grid-cols-4',
        'lg:grid-cols-3',
        'lg:grid-cols-4',
        // Status colors
        'bg-blue-500',
        'bg-green-500',
        'bg-red-500',
        'bg-yellow-500',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    DEFAULT: '#0ea5e9',
                    50:  '#f0f9ff',
                    100: '#e0f2fe',
                    200: '#bae6fd',
                    300: '#7dd3fc',
                    400: '#38bdf8',
                    500: '#0ea5e9',
                    600: '#0284c7',
                    700: '#0369a1',
                    800: '#075985',
                    900: '#0c4a6e',
                },
            },
            borderRadius: {
                '2xl': '1rem',
            },
        },
    },
    plugins: [forms, flowbite],
};