import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', '"Segoe UI"', 'Roboto', ...defaultTheme.fontFamily.sans],
                display: ['Inter', '-apple-system', 'BlinkMacSystemFont', '"Segoe UI"', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', 'ui-monospace', 'SFMono-Regular', 'Menlo', 'Monaco', 'Consolas', 'monospace'],
            },
            colors: {
                brand: {
                    50: '#eef2ff',
                    100: '#e0e7ff',
                    200: '#c7d2fe',
                    300: '#a5b4fc',
                    400: '#818cf8',
                    500: '#6366f1',
                    600: '#4f46e5',
                    700: '#4338ca',
                    800: '#3730a3',
                    900: '#312e81',
                    950: '#1e1b4b',
                },
                luxury: {
                    darkest: '#070A0F',
                    dark: '#0B0F17',
                    card: '#111827',
                    cardSecondary: '#1E293B',
                    border: '#1E293B',
                    borderLight: '#334155',
                    gold: '#F59E0B',
                    goldLight: '#FDE68A',
                    emerald: '#10B981',
                    rose: '#F43F5E',
                }
            },
            boxShadow: {
                'luxury': '0 10px 30px -10px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05)',
                'luxury-hover': '0 20px 40px -15px rgba(99, 102, 241, 0.2), 0 0 0 1px rgba(99, 102, 241, 0.3)',
                'glow-indigo': '0 0 25px -5px rgba(99, 102, 241, 0.4)',
                'glow-emerald': '0 0 25px -5px rgba(16, 185, 129, 0.4)',
                'glow-gold': '0 0 25px -5px rgba(245, 158, 11, 0.4)',
            }
        },
    },

    plugins: [forms],
};
