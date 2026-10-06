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
                    peach: '#F29F67',
                    'peach-hover': '#E08A50',
                    'peach-light': '#FEF5EE',
                    navy: '#1E1E2C',
                    'navy-dark': '#161622',
                    'navy-card': '#262638',
                    'navy-subtle': '#2C2C40',
                    'navy-border': '#36364E',
                    azure: '#3B8FF3',
                    'azure-hover': '#2575DC',
                    teal: '#34B1AA',
                    'teal-hover': '#2B9B95',
                    gold: '#E0B50F',
                    'gold-hover': '#C89E07',
                },
                coral: {
                    50: '#FEF7F2',
                    100: '#FDEEE4',
                    200: '#FBDDCA',
                    300: '#F7C4A1',
                    400: '#F4AC7B',
                    500: '#F29F67',
                    600: '#E08A50',
                    700: '#C06D35',
                },
                obsidian: {
                    950: '#14141E',
                    900: '#1E1E2C',
                    850: '#242436',
                    800: '#2A2A3E',
                    750: '#32324A',
                    700: '#3D3D58',
                }
            },
            boxShadow: {
                'peach-glow': '0 0 20px -5px rgba(242, 159, 103, 0.4)',
                'azure-glow': '0 0 20px -5px rgba(59, 143, 243, 0.4)',
                'teal-glow': '0 0 20px -5px rgba(52, 177, 170, 0.4)',
                'gold-glow': '0 0 20px -5px rgba(224, 181, 15, 0.4)',
            }
        },
    },

    plugins: [forms],
};
